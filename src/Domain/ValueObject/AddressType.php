<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Domain\ValueObject;

final class AddressType
{
    public const BILLING = 'billing';
    public const SHIPPING = 'shipping';
    public const VISITING = 'visiting';
    public const OTHER = 'other';

    /** @var string */ private $value;

    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (!in_array($value, self::values(), true)) {
            throw new \InvalidArgumentException('Ongeldig adrestype.');
        }
        $this->value = $value;
    }

    /** @return string[] */
    public static function values(): array
    {
        return [self::BILLING, self::SHIPPING, self::VISITING, self::OTHER];
    }

    public function getValue(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}
