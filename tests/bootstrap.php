<?php

declare(strict_types=1);

$moduleRoot = dirname(__DIR__);
$modulesRoot = dirname($moduleRoot);

require_once $modulesRoot . '/AdminCore/tests/bootstrap.php';
foreach ([
    'Domain/ValueObject/CustomerStatus.php',
    'Domain/ValueObject/AddressType.php',
    'Domain/Model/Contact.php',
    'Domain/Model/Address.php',
    'Domain/Model/Customer.php',
    'Application/Command/CreateCustomerCommand.php',
    'Application/Command/UpdateCustomerCommand.php',
    'Contract/CustomerRepositoryInterface.php',
    'Exception/DuplicateCustomerReferenceException.php',
    'Exception/CustomerNotFoundException.php',
    'Application/UseCase/CreateCustomer.php',
    'Application/UseCase/GetCustomer.php',
    'Application/UseCase/UpdateCustomer.php',
    'Infrastructure/Persistence/PdoCustomerRepository.php',
    'Application/Query/CustomerListQuery.php',
    'Application/ReadModel/CustomerListItem.php',
    'Application/ReadModel/CustomerListResult.php',
    'Application/ReadModel/CustomerOverviewSummary.php',
    'Application/ReadModel/CustomerDetailContext.php',
    'Contract/CustomerDetailExtensionInterface.php',
    'Contract/CustomerListRepositoryInterface.php',
    'Application/UseCase/ListCustomers.php',
    'Infrastructure/Persistence/PdoCustomerListRepository.php',
    'Service/CustomerOverviewPresenter.php',
    'Service/CustomerEditorPresenter.php',
    'Service/CustomerDetailExtensionLoader.php',
] as $file) {
    require_once $moduleRoot . '/src/' . $file;
}

function adminCustomerAssert($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function adminCustomerAssertThrows(string $exceptionClass, callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        if ($throwable instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException($message . ' Ontvangen: ' . get_class($throwable));
    }
    throw new RuntimeException($message . ' Er werd geen exception gegooid.');
}
