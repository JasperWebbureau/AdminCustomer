<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Domain\Model;

final class Contact
{
    /** @var string */ private $publicId;
    /** @var string */ private $name;
    /** @var string */ private $email;
    /** @var string */ private $phone;
    /** @var string */ private $role;
    /** @var bool */ private $primary;
    /** @var int */ private $position;

    public function __construct(
        string $publicId,
        string $name,
        string $email = '',
        string $phone = '',
        string $role = '',
        bool $primary = false,
        int $position = 10
    ) {
        $publicId = trim($publicId);
        $name = trim($name);
        $email = trim($email);
        $phone = trim($phone);
        $role = trim($role);
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Contact vereist een geldige publieke id.');
        }
        if ($name === '' || strlen($name) > 255) {
            throw new \InvalidArgumentException('Contactnaam is verplicht en maximaal 255 tekens.');
        }
        if ($email !== '' && (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new \InvalidArgumentException('Contact-e-mailadres is ongeldig.');
        }
        if (strlen($phone) > 64 || strlen($role) > 128 || $position < 0) {
            throw new \InvalidArgumentException('Contactgegevens of positie zijn ongeldig.');
        }

        $this->publicId = $publicId;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
        $this->primary = $primary;
        $this->position = $position;
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getRole(): string { return $this->role; }
    public function isPrimary(): bool { return $this->primary; }
    public function getPosition(): int { return $this->position; }
}
