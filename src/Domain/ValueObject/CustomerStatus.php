<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Domain\ValueObject;

final class CustomerStatus
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';

    /** @var string */ private $value;

    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (!in_array($value, self::values(), true)) {
            throw new \InvalidArgumentException('Ongeldige klantstatus.');
        }
        $this->value = $value;
    }

    /** @return string[] */
    public static function values(): array
    {
        return [self::ACTIVE, self::INACTIVE];
    }

    public function getValue(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}
