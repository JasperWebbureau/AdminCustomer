<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Command\UpdateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\GetCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\UpdateCustomer;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Exception\CustomerNotFoundException;
use Flexgrid\Utils\_Time;

final class UpdateCustomerTransactions implements TransactionManagerInterface
{
    /** @var bool */ public $active = false;
    /** @var string[] */ public $trace = [];
    public function transactional(callable $operation)
    {
        $this->active = true;
        $this->trace[] = 'begin';
        try {
            $result = $operation();
            $this->trace[] = 'commit';
            return $result;
        } catch (Throwable $throwable) {
            $this->trace[] = 'rollback';
            throw $throwable;
        } finally {
            $this->active = false;
        }
    }
}

final class UpdateCustomerIds implements PublicIdGeneratorInterface
{
    /** @var int */ private $next = 1;
    public function generate(): string { return 'new-child-' . $this->next++; }
}

final class UpdateCustomerRepository implements CustomerRepositoryInterface
{
    /** @var UpdateCustomerTransactions */ private $transactions;
    /** @var Customer */ public $customer;
    /** @var int */ public $updatedAt = 0;
    public function __construct(UpdateCustomerTransactions $transactions, Customer $customer)
    {
        $this->transactions = $transactions;
        $this->customer = $customer;
    }
    public function insert(Customer $customer, int $createdAt): void { throw new LogicException('Niet gebruikt.'); }
    public function update(Customer $customer, int $updatedAt): void
    {
        adminCustomerAssert($this->transactions->active, 'Klantupdate moet binnen de transactie worden opgeslagen.');
        $this->customer = $customer;
        $this->updatedAt = $updatedAt;
    }
    public function findByPublicId(TenantId $tenantId, string $publicId): ?Customer
    {
        return $this->customer->getTenantId()->equals($tenantId) && $this->customer->getPublicId() === $publicId
            ? $this->customer
            : null;
    }
    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Customer
    {
        adminCustomerAssert($this->transactions->active, 'Klant moet binnen de transactie worden vergrendeld.');
        return $this->findByPublicId($tenantId, $publicId);
    }
    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Customer
    {
        return null;
    }
}

$tenantId = new TenantId('update-customer-tenant');
$original = new Customer('customer-update-1', $tenantId, 'Oude naam', 'Oud B.V.');
$original->addContact(new Contact('contact-existing', 'Oud contact', 'old@example.test', '', '', true, 10));
$original->addAddress(new Address(
    'address-existing', new AddressType('billing'), 'Oud 1', '1000 AA', 'Utrecht', 'NL', '', '', '', '', true, 10
));
$transactions = new UpdateCustomerTransactions();
$repository = new UpdateCustomerRepository($transactions, $original);
$useCase = new UpdateCustomer(
    new TenantContext($tenantId),
    new UpdateCustomerIds(),
    $transactions,
    $repository,
    new _Time(1770001000)
);

$updated = $useCase->execute(new UpdateCustomerCommand(
    'customer-update-1',
    'Nieuwe naam',
    'Nieuw B.V.',
    '12345678',
    'NL001234567B01',
    'inactive',
    'Nieuwe notitie',
    [
        ['public_id' => 'contact-existing', 'name' => 'Bewaard contact', 'email' => 'kept@example.test', 'is_primary' => '0'],
        ['public_id' => '', 'name' => 'Nieuw primair', 'phone' => '06-12345678', 'is_primary' => '1'],
    ],
    [
        [
            'public_id' => 'address-existing', 'type' => 'billing', 'line_1' => 'Nieuw 2',
            'postal_code' => '2000 BB', 'city' => 'Eindhoven', 'country_code' => 'nl', 'is_primary' => '1',
        ],
        [
            'public_id' => '', 'type' => 'shipping', 'line_1' => 'Haven 3',
            'postal_code' => '3000 CC', 'city' => 'Rotterdam', 'country_code' => 'NL', 'is_primary' => '1',
        ],
    ]
));

adminCustomerAssert($updated->getDisplayName() === 'Nieuwe naam', 'Algemene klantgegevens moeten worden bijgewerkt.');
adminCustomerAssert($updated->getStatus()->getValue() === 'inactive', 'Klantstatus moet worden bijgewerkt.');
adminCustomerAssert(count($updated->getContacts()) === 2, 'Editor moet bestaande en nieuwe contacten samen opslaan.');
adminCustomerAssert($updated->getPrimaryContact()->getName() === 'Nieuw primair', 'Editor moet exact één primair contact bewaren.');
adminCustomerAssert($updated->getContacts()[0]->getPublicId() === 'contact-existing', 'Bestaande child-id moet stabiel blijven.');
adminCustomerAssert($updated->getAddresses()[0]->getCountryCode() === 'NL', 'Landcode moet worden genormaliseerd.');
adminCustomerAssert($repository->updatedAt === 1770001000, 'Update moet de gedeelde klok gebruiken.');
adminCustomerAssert($transactions->trace === ['begin', 'commit'], 'Update moet één transactie gebruiken.');

$loaded = (new GetCustomer(new TenantContext($tenantId), $repository))->execute('customer-update-1');
adminCustomerAssert($loaded->getDisplayName() === 'Nieuwe naam', 'GetCustomer moet tenantgebonden de bijgewerkte klant leveren.');
adminCustomerAssertThrows(CustomerNotFoundException::class, function () use ($repository): void {
    (new GetCustomer(new TenantContext(new TenantId('other-tenant')), $repository))->execute('customer-update-1');
}, 'Andere tenant mag de klant niet ophalen.');

$beforeInvalid = $repository->customer;
adminCustomerAssertThrows(InvalidArgumentException::class, function () use ($useCase): void {
    $useCase->execute(new UpdateCustomerCommand(
        'customer-update-1', 'Manipulatie', '', '', '', 'active', '',
        [['public_id' => 'foreign-contact', 'name' => 'Onbekend', 'is_primary' => '1']],
        []
    ));
}, 'Een child-id buiten het bestaande aggregate moet worden geweigerd.');
adminCustomerAssert($repository->customer === $beforeInvalid, 'Mislukte update mag de opgeslagen aggregate niet vervangen.');
adminCustomerAssert(end($transactions->trace) === 'rollback', 'Mislukte update moet rollback uitvoeren.');

echo "AdminCustomer update tests passed.\n";
