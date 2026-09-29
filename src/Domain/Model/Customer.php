<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Domain\Model;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

final class Customer
{
    /** @var string */ private $publicId;
    /** @var TenantId */ private $tenantId;
    /** @var string */ private $displayName;
    /** @var string */ private $companyName;
    /** @var string */ private $registrationNumber;
    /** @var string */ private $taxNumber;
    /** @var CustomerStatus */ private $status;
    /** @var string */ private $notes;
    /** @var string */ private $source;
    /** @var string */ private $externalId;
    /** @var Contact[] */ private $contacts = [];
    /** @var Address[] */ private $addresses = [];

    public function __construct(
        string $publicId,
        TenantId $tenantId,
        string $displayName,
        string $companyName = '',
        string $registrationNumber = '',
        string $taxNumber = '',
        ?CustomerStatus $status = null,
        string $notes = '',
        string $source = '',
        string $externalId = ''
    ) {
        $publicId = trim($publicId);
        $displayName = trim($displayName);
        $companyName = trim($companyName);
        $registrationNumber = trim($registrationNumber);
        $taxNumber = trim($taxNumber);
        $notes = trim($notes);
        $source = strtolower(trim($source));
        $externalId = trim($externalId);
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Klant vereist een geldige publieke id.');
        }
        if ($displayName === '' || strlen($displayName) > 255) {
            throw new \InvalidArgumentException('Klantnaam is verplicht en maximaal 255 tekens.');
        }
        if (strlen($companyName) > 255 || strlen($registrationNumber) > 64 || strlen($taxNumber) > 64) {
            throw new \InvalidArgumentException('Bedrijfsgegevens zijn te lang.');
        }
        if (strlen($notes) > 10000) {
            throw new \InvalidArgumentException('Klantnotitie is maximaal 10000 tekens.');
        }
        if (($source === '') !== ($externalId === '')) {
            throw new \InvalidArgumentException('Bron en externe id moeten samen worden opgegeven.');
        }
        if ($source !== '' && preg_match('/^[a-z][a-z0-9_-]{1,63}$/D', $source) !== 1) {
            throw new \InvalidArgumentException('Klantbron is ongeldig.');
        }
        if (strlen($externalId) > 128) {
            throw new \InvalidArgumentException('Externe klant-id is maximaal 128 tekens.');
        }

        $this->publicId = $publicId;
        $this->tenantId = $tenantId;
        $this->displayName = $displayName;
        $this->companyName = $companyName;
        $this->registrationNumber = $registrationNumber;
        $this->taxNumber = $taxNumber;
        $this->status = $status ?: new CustomerStatus(CustomerStatus::ACTIVE);
        $this->notes = $notes;
        $this->source = $source;
        $this->externalId = $externalId;
    }

    public function addContact(Contact $contact): void
    {
        foreach ($this->contacts as $existing) {
            if ($existing->getPublicId() === $contact->getPublicId()) {
                throw new \DomainException('Contact-id bestaat al binnen deze klant.');
            }
            if ($existing->getPosition() === $contact->getPosition()) {
                throw new \DomainException('Contactpositie bestaat al binnen deze klant.');
            }
            if ($existing->isPrimary() && $contact->isPrimary()) {
                throw new \DomainException('Klant kan maar één primair contact hebben.');
            }
        }
        if ($this->contacts === [] && !$contact->isPrimary()) {
            throw new \DomainException('Het eerste contact moet primair zijn.');
        }
        $this->contacts[] = $contact;
        usort($this->contacts, function (Contact $left, Contact $right): int {
            return $left->getPosition() <=> $right->getPosition();
        });
    }

    public function addAddress(Address $address): void
    {
        $sameTypeExists = false;
        foreach ($this->addresses as $existing) {
            if ($existing->getPublicId() === $address->getPublicId()) {
                throw new \DomainException('Adres-id bestaat al binnen deze klant.');
            }
            if ($existing->getType()->getValue() === $address->getType()->getValue()) {
                $sameTypeExists = true;
                if ($existing->getPosition() === $address->getPosition()) {
                    throw new \DomainException('Adrespositie bestaat al binnen dit adrestype.');
                }
                if ($existing->isPrimary() && $address->isPrimary()) {
                    throw new \DomainException('Klant kan per adrestype maar één primair adres hebben.');
                }
            }
        }
        if (!$sameTypeExists && !$address->isPrimary()) {
            throw new \DomainException('Het eerste adres van ieder type moet primair zijn.');
        }
        $this->addresses[] = $address;
        usort($this->addresses, function (Address $left, Address $right): int {
            $type = strcmp($left->getType()->getValue(), $right->getType()->getValue());
            return $type !== 0 ? $type : ($left->getPosition() <=> $right->getPosition());
        });
    }

    public function getPrimaryContact(): ?Contact
    {
        foreach ($this->contacts as $contact) {
            if ($contact->isPrimary()) {
                return $contact;
            }
        }
        return null;
    }

    public function getPrimaryAddress(AddressType $type): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->getType()->getValue() === $type->getValue() && $address->isPrimary()) {
                return $address;
            }
        }
        return null;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getRegistrationNumber(): string { return $this->registrationNumber; }
    public function getTaxNumber(): string { return $this->taxNumber; }
    public function getStatus(): CustomerStatus { return $this->status; }
    public function getNotes(): string { return $this->notes; }
    public function getSource(): string { return $this->source; }
    public function getExternalId(): string { return $this->externalId; }
    /** @return Contact[] */ public function getContacts(): array { return $this->contacts; }
    /** @return Address[] */ public function getAddresses(): array { return $this->addresses; }
}
