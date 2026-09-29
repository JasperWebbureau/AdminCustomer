<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\ReadModel;

final class CustomerListItem
{
    /** @var string */ private $publicId;
    /** @var string */ private $displayName;
    /** @var string */ private $companyName;
    /** @var string */ private $contactName;
    /** @var string */ private $contactEmail;
    /** @var string */ private $contactPhone;
    /** @var string */ private $city;
    /** @var string */ private $countryCode;
    /** @var string */ private $status;
    /** @var int */ private $createdAt;

    public function __construct(
        string $publicId,
        string $displayName,
        string $companyName,
        string $contactName,
        string $contactEmail,
        string $contactPhone,
        string $city,
        string $countryCode,
        string $status,
        int $createdAt
    ) {
        $this->publicId = $publicId;
        $this->displayName = $displayName;
        $this->companyName = $companyName;
        $this->contactName = $contactName;
        $this->contactEmail = $contactEmail;
        $this->contactPhone = $contactPhone;
        $this->city = $city;
        $this->countryCode = $countryCode;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getContactName(): string { return $this->contactName; }
    public function getContactEmail(): string { return $this->contactEmail; }
    public function getContactPhone(): string { return $this->contactPhone; }
    public function getCity(): string { return $this->city; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): int { return $this->createdAt; }
}
