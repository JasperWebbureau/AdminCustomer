<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\Command\UpdateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Exception\DuplicateCustomerReferenceException;

/** Synchroniseert gegevens uit een externe bron via de publieke Customer-use-cases. */
final class SyncExternalCustomer
{
    /** @var TenantContext */ private $tenant;
    /** @var CustomerRepositoryInterface */ private $repository;
    /** @var CreateCustomer */ private $create;
    /** @var UpdateCustomer */ private $update;

    public function __construct(
        TenantContext $tenant,
        CustomerRepositoryInterface $repository,
        CreateCustomer $create,
        UpdateCustomer $update
    ) {
        $this->tenant = $tenant;
        $this->repository = $repository;
        $this->create = $create;
        $this->update = $update;
    }

    public function execute(CreateCustomerCommand $command, string $status = CustomerStatus::ACTIVE): Customer
    {
        if (trim($command->getSource()) === '' || trim($command->getExternalId()) === '') {
            throw new \InvalidArgumentException('Synchronisatie vereist een bron en externe id.');
        }
        new CustomerStatus($status);

        try {
            $customer = $this->create->execute($command);
            return $customer->getStatus()->getValue() === $status
                ? $customer
                : $this->replace($command, $customer, $status);
        } catch (DuplicateCustomerReferenceException $exception) {
            $existing = $this->repository->findByExternalReference(
                $this->tenant->getTenantId(),
                $command->getSource(),
                $command->getExternalId()
            );
            if ($existing === null) {
                throw $exception;
            }

            return $this->replace($command, $existing, $status);
        }
    }

    public function find(string $source, string $externalId): ?Customer
    {
        return $this->repository->findByExternalReference(
            $this->tenant->getTenantId(), $source, $externalId
        );
    }

    private function replace(CreateCustomerCommand $command, Customer $existing, string $status): Customer
    {
        return $this->update->execute(new UpdateCustomerCommand(
                $existing->getPublicId(),
                $command->getDisplayName(),
                $command->getCompanyName(),
                $command->getRegistrationNumber(),
                $command->getTaxNumber(),
                $status,
                $existing->getNotes(),
                $this->contacts($command, $existing),
                $this->addresses($command, $existing)
            ));
    }

    private function contacts(CreateCustomerCommand $command, Customer $existing): array
    {
        $previous = $existing->getContacts();
        $primary = $existing->getPrimaryContact();
        $rows = [];
        foreach (array_values($command->getContacts()) as $index => $row) {
            $row['public_id'] = $index === 0 && $primary !== null
                ? $primary->getPublicId()
                : (isset($previous[$index]) ? $previous[$index]->getPublicId() : '');
            $rows[] = $row;
        }
        return $rows;
    }

    private function addresses(CreateCustomerCommand $command, Customer $existing): array
    {
        $previousByType = [];
        foreach ($existing->getAddresses() as $address) {
            $previousByType[$address->getType()->getValue()][] = $address->getPublicId();
        }
        $rows = [];
        foreach ($command->getAddresses() as $row) {
            $type = strtolower(trim((string)($row['type'] ?? 'billing')));
            $row['public_id'] = !empty($previousByType[$type])
                ? (string)array_shift($previousByType[$type])
                : '';
            $rows[] = $row;
        }
        return $rows;
    }
}
