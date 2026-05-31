<?php

declare(strict_types=1);

namespace App\Modules\Remittance\ValueObjects;

use Illuminate\Support\Str;

final readonly class RemittanceId
{
    private const PREFIX = 'REM-';

    public function __construct(
        private string $ulid,
    ) {
        if (!str_starts_with($ulid, self::PREFIX)) {
            throw new \InvalidArgumentException('RemittanceId must start with REM-');
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

    public function toString(): string
    {
        return $this->ulid;
    }

    public function __toString(): string
    {
        return $this->ulid;
    }
}
