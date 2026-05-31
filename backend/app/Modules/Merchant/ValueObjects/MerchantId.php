<?php

declare(strict_types=1);

namespace App\Modules\Merchant\ValueObjects;

use Illuminate\Support\Str;

final readonly class MerchantId
{
    private const PREFIX = 'MER-';

    public function __construct(private string $ulid)
    {
        if (!str_starts_with($ulid, self::PREFIX)) {
            throw new \InvalidArgumentException('MerchantId must start with MER-');
        }
    }

    public static function generate(): self
    {
        return new self(self::PREFIX . strtoupper(Str::ulid()->toString()));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string { return $this->ulid; }
    public function __toString(): string { return $this->ulid; }
}
