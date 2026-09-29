<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Domain\Model;

use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;

final class Address
{
    /** @var string */ private $publicId;
    /** @var AddressType */ private $type;
    /** @var string */ private $label;
    /** @var string */ private $addressee;
    /** @var string */ private $line1;
    /** @var string */ private $line2;
    /** @var string */ private $postalCode;
    /** @var string */ private $city;
    /** @var string */ private $region;
    /** @var string */ private $countryCode;
    /** @var bool */ private $primary;
    /** @var int */ private $position;

    public function __construct(
        string $publicId,
        AddressType $type,
        string $line1,
        string $postalCode,
        string $city,
        string $countryCode,
        string $label = '',
        string $addressee = '',
        string $line2 = '',
        string $region = '',
        bool $primary = false,
        int $position = 10
    ) {
        $publicId = trim($publicId);
        $line1 = trim($line1);
        $postalCode = trim($postalCode);
        $city = trim($city);
        $countryCode = strtoupper(trim($countryCode));
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Adres vereist een geldige publieke id.');
        }
        if ($line1 === '' || $postalCode === '' || $city === '') {
            throw new \InvalidArgumentException('Adresregel, postcode en plaats zijn verplicht.');
        }
        if (strlen($line1) > 255 || strlen($postalCode) > 32 || strlen($city) > 128) {
            throw new \InvalidArgumentException('Adresvelden zijn te lang.');
        }
        if (preg_match('/^[A-Z]{2}$/D', $countryCode) !== 1) {
            throw new \InvalidArgumentException('Landcode moet uit twee letters bestaan.');
        }
        if (strlen($label) > 128 || strlen($addressee) > 255 || strlen($line2) > 255
            || strlen($region) > 128 || $position < 0
        ) {
            throw new \InvalidArgumentException('Aanvullende adresgegevens of positie zijn ongeldig.');
        }

        $this->publicId = $publicId;
        $this->type = $type;
        $this->label = trim($label);
        $this->addressee = trim($addressee);
        $this->line1 = $line1;
        $this->line2 = trim($line2);
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->region = trim($region);
        $this->countryCode = $countryCode;
        $this->primary = $primary;
        $this->position = $position;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getType(): AddressType { return $this->type; }
    public function getLabel(): string { return $this->label; }
    public function getAddressee(): string { return $this->addressee; }
    public function getLine1(): string { return $this->line1; }
    public function getLine2(): string { return $this->line2; }
    public function getPostalCode(): string { return $this->postalCode; }
    public function getCity(): string { return $this->city; }
    public function getRegion(): string { return $this->region; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function isPrimary(): bool { return $this->primary; }
    public function getPosition(): int { return $this->position; }
}
