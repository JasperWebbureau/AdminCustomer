<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Exception\DuplicateCustomerReferenceException;
use Flexgrid\Utils\_Time;

final class CustomerTestTransactions implements TransactionManagerInterface
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

final class CustomerTestIds implements PublicIdGeneratorInterface
{
    /** @var int */ private $next = 1;
    public function generate(): string { return 'customer-test-' . $this->next++; }
}

final class MemoryCustomers implements CustomerRepositoryInterface
{
    /** @var CustomerTestTransactions */ private $transactions;
    /** @var Customer[] */ public $customers = [];
    public function __construct(CustomerTestTransactions $transactions) { $this->transactions = $transactions; }
    public function insert(Customer $customer, int $createdAt): void
    {
        adminCustomerAssert($this->transactions->active, 'Klant moet binnen de transactie worden opgeslagen.');
        adminCustomerAssert($createdAt === 1770000000, 'Klantopslag moet de gedeelde klok gebruiken.');
        $this->customers[] = $customer;
    }
    public function update(Customer $customer, int $updatedAt): void
    {
        foreach ($this->customers as $index => $existing) {
            if ($existing->getTenantId()->equals($customer->getTenantId())
                && $existing->getPublicId() === $customer->getPublicId()
            ) {
                $this->customers[$index] = $customer;
                return;
            }
        }
    }
    public function findByPublicId(TenantId $tenantId, string $publicId): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getTenantId()->equals($tenantId) && $customer->getPublicId() === $publicId) {
                return $customer;
            }
        }
        return null;
    }
    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Customer
    {
        return $this->findByPublicId($tenantId, $publicId);
    }
    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getTenantId()->equals($tenantId)
                && $customer->getSource() === $source
                && $customer->getExternalId() === $externalId
            ) {
                return $customer;
            }
        }
        return null;
    }
}

$tenantId = new TenantId('customer-usecase-tenant');
$transactions = new CustomerTestTransactions();
$repository = new MemoryCustomers($transactions);
$useCase = new CreateCustomer(
    new TenantContext($tenantId),
    new CustomerTestIds(),
    $transactions,
    $repository,
    new _Time(1770000000)
);
$command = new CreateCustomerCommand(
    'Acme administratie',
    'Acme B.V.',
    '12345678',
    'NL001234567B01',
    [
        ['name' => 'Jasper Jansen', 'email' => 'jasper@example.test', 'phone' => '+31 6 12345678', 'role' => 'Inkoop'],
        ['name' => 'Financiële administratie', 'email' => 'finance@example.test'],
    ],
    [
        [
            'type' => 'billing', 'label' => 'Facturatie', 'addressee' => 'Acme B.V.',
            'line_1' => 'Markt 1', 'postal_code' => '1000 AA', 'city' => 'Utrecht', 'country_code' => 'nl',
        ],
        [
            'type' => 'shipping', 'line_1' => 'Haven 2', 'postal_code' => '3000 BB',
            'city' => 'Rotterdam', 'country_code' => 'NL',
        ],
    ],
    'Belangrijke klant',
    'webshop',
    'shop-customer-42'
);

$customer = $useCase->execute($command);
adminCustomerAssert(count($repository->customers) === 1, 'CreateCustomer moet precies één aggregate opslaan.');
adminCustomerAssert($customer->getTenantId()->equals($tenantId), 'CreateCustomer moet altijd de actieve tenant gebruiken.');
adminCustomerAssert($customer->getPrimaryContact()->getName() === 'Jasper Jansen', 'Eerste contact moet standaard primair worden.');
adminCustomerAssert($customer->getContacts()[1]->getPosition() === 20, 'Contactposities moeten deterministisch worden toegekend.');
adminCustomerAssert($customer->getAddresses()[0]->getCountryCode() === 'NL', 'Landcode moet worden genormaliseerd.');
adminCustomerAssert($transactions->trace === ['begin', 'commit'], 'Klantcreatie moet één transactie gebruiken.');

$retry = $useCase->execute($command);
adminCustomerAssert($retry->getPublicId() === $customer->getPublicId(), 'Identieke externe retry moet dezelfde klant teruggeven.');
adminCustomerAssert(count($repository->customers) === 1, 'Identieke retry mag geen dubbele klant maken.');

adminCustomerAssertThrows(DuplicateCustomerReferenceException::class, function () use ($useCase): void {
    $useCase->execute(new CreateCustomerCommand(
        'Andere naam', '', '', '', [], [], '', 'webshop', 'shop-customer-42'
    ));
}, 'Afwijkende payload onder dezelfde externe referentie moet worden geweigerd.');

$manualOne = $useCase->execute(new CreateCustomerCommand('Handmatige klant één'));
$manualTwo = $useCase->execute(new CreateCustomerCommand('Handmatige klant twee'));
adminCustomerAssert($manualOne->getPublicId() !== $manualTwo->getPublicId(), 'Handmatige klanten zonder externe id moeten naast elkaar kunnen bestaan.');

$traceBeforeInvalidInput = $transactions->trace;
adminCustomerAssertThrows(InvalidArgumentException::class, function () use ($useCase): void {
    $useCase->execute(new CreateCustomerCommand(
        'Ongeldige boolean', '', '', '', [['name' => 'Contact', 'is_primary' => 'ja']]
    ));
}, 'Vrije tekst mag niet als boolean worden geaccepteerd.');
adminCustomerAssert(
    $transactions->trace === $traceBeforeInvalidInput,
    'Ongeldige invoer moet vóór het openen van een transactie worden afgewezen.'
);

echo "AdminCustomer CreateCustomer tests passed.\n";
