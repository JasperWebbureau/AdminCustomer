<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\ReadModel;

use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

/**
 * Beperkte, immutable context voor optionele uitbreidingen op het klantdetail.
 */
final class CustomerDetailContext
{
    /** @var string */ private $publicId;
    /** @var string */ private $displayName;
    /** @var string */ private $status;
    /** @var bool */ private $hasPrimaryBillingAddress;

    public function __construct(
        string $publicId,
        string $displayName,
        string $status,
        bool $hasPrimaryBillingAddress
    ) {
        $publicId = trim($publicId);
        $displayName = trim($displayName);
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Klantdetail vereist een geldige publieke id.');
        }
        if ($displayName === '' || strlen($displayName) > 255) {
            throw new \InvalidArgumentException('Klantdetail vereist een geldige naam.');
        }
        if (!in_array($status, CustomerStatus::values(), true)) {
            throw new \InvalidArgumentException('Klantdetail bevat een ongeldige status.');
        }

        $this->publicId = $publicId;
        $this->displayName = $displayName;
        $this->status = $status;
        $this->hasPrimaryBillingAddress = $hasPrimaryBillingAddress;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getStatus(): string { return $this->status; }
    public function isActive(): bool { return $this->status === CustomerStatus::ACTIVE; }
    public function hasPrimaryBillingAddress(): bool { return $this->hasPrimaryBillingAddress; }
}
