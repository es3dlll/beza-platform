<?php

declare(strict_types=1);

namespace App\Modules\Compliance\ValueObjects;

use App\Modules\Compliance\Exceptions\InvalidRiskScoreException;

final readonly class RiskScore
{
    const LOW = 'LOW';
    const MEDIUM = 'MEDIUM';
    const HIGH = 'HIGH';
    const CRITICAL = 'CRITICAL';

    const THRESHOLDS = [
        self::LOW => 30,
        self::MEDIUM => 40,
        self::HIGH => 70,
        self::CRITICAL => 90,
    ];

    public function __construct(private int $score)
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidRiskScoreException("Risk score must be between 0 and 100, got {$score}");
        }
    }

    public function score(): int { return $this->score; }

    public function level(): string
    {
        return match (true) {
            $this->score >= self::THRESHOLDS[self::CRITICAL] => self::CRITICAL,
            $this->score >= self::THRESHOLDS[self::HIGH] => self::HIGH,
            $this->score >= self::THRESHOLDS[self::MEDIUM] => self::MEDIUM,
            default => self::LOW,
        };
    }

    public function requiresAction(): bool
    {
        return $this->score >= self::THRESHOLDS[self::MEDIUM];
    }

    public function requiresBlock(): bool
    {
        return $this->score >= self::THRESHOLDS[self::HIGH];
    }

    public function toArray(): array
    {
        return ['score' => $this->score, 'level' => $this->level()];
    }
}
