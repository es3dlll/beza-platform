<?php

declare(strict_types=1);

namespace Modules\Fraud\DTOs;

final class FraudRuleDto
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $ruleType = '',
        public readonly ?string $description = null,
        public readonly array $parameters = [],
        public readonly int $riskScore = 0,
        public readonly bool $isActive = true,
        public readonly string $severity = 'medium',
    ) {}
}
