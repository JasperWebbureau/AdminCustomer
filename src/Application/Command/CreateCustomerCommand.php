<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\Command;

final class CreateCustomerCommand
{
    /** @var string */ private $displayName;
    /** @var string */ private $companyName;
    /** @var string */ private $registrationNumber;
    /** @var string */ private $taxNumber;
    /** @var array */ private $contacts;
    /** @var array */ private $addresses;
    /** @var string */ private $notes;
    /** @var string */ private $source;
    /** @var string */ private $externalId;

    public function __construct(
        string $displayName,
        string $companyName = '',
        string $registrationNumber = '',
        string $taxNumber = '',
        array $contacts = [],
        array $addresses = [],
        string $notes = '',
        string $source = '',
        string $externalId = ''
    ) {
        $this->displayName = $displayName;
        $this->companyName = $companyName;
        $this->registrationNumber = $registrationNumber;
        $this->taxNumber = $taxNumber;
        $this->contacts = $contacts;
        $this->addresses = $addresses;
        $this->notes = $notes;
        $this->source = $source;
        $this->externalId = $externalId;
    }

    public function getDisplayName(): string { return $this->displayName; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getRegistrationNumber(): string { return $this->registrationNumber; }
    public function getTaxNumber(): string { return $this->taxNumber; }
    public function getContacts(): array { return $this->contacts; }
    public function getAddresses(): array { return $this->addresses; }
    public function getNotes(): string { return $this->notes; }
    public function getSource(): string { return $this->source; }
    public function getExternalId(): string { return $this->externalId; }
}
