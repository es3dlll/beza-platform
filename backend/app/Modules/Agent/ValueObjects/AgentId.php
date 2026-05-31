<?php

declare(strict_types=1);

namespace App\Modules\Agent\ValueObjects;

use Illuminate\Support\Str;

final readonly class AgentId
{
    private const PREFIX = 'AGT-';

    public function __construct(private string $value)
    {
        if (!str_starts_with($value, self::PREFIX)) {
            throw new \InvalidArgumentException("AgentId must start with '" . self::PREFIX . "', got: {$value}");
        }
    }

    public static function generate(): self
    {
        return new self(self::PREFIX . Str::ulid()->toBase32());
    }

    public function toString(): string
    {
        return $this->value;
    }
}
