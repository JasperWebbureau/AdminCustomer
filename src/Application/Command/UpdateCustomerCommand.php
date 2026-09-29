<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\Command;

final class UpdateCustomerCommand
{
    /** @var string */ private $publicId;
    /** @var string */ private $displayName;
    /** @var string */ private $companyName;
    /** @var string */ private $registrationNumber;
    /** @var string */ private $taxNumber;
    /** @var string */ private $status;
    /** @var string */ private $notes;
    /** @var array */ private $contacts;
    /** @var array */ private $addresses;

    public function __construct(
        string $publicId,
        string $displayName,
        string $companyName,
        string $registrationNumber,
        string $taxNumber,
        string $status,
        string $notes,
        array $contacts,
        array $addresses
    ) {
        $this->publicId = trim($publicId);
        $this->displayName = trim($displayName);
        $this->companyName = trim($companyName);
        $this->registrationNumber = trim($registrationNumber);
        $this->taxNumber = trim($taxNumber);
        $this->status = strtolower(trim($status));
        $this->notes = trim($notes);
        $this->contacts = $contacts;
        $this->addresses = $addresses;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getRegistrationNumber(): string { return $this->registrationNumber; }
    public function getTaxNumber(): string { return $this->taxNumber; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): string { return $this->notes; }
    public function getContacts(): array { return $this->contacts; }
    public function getAddresses(): array { return $this->addresses; }
}
