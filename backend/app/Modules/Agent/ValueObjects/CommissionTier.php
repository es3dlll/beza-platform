<?php

declare(strict_types=1);

namespace App\Modules\Agent\ValueObjects;

final readonly class CommissionTier
{
    public function __construct(
        private string $tier,
        private int $cashInBps,
        private int $cashOutBps,
        private int $transferBps,
        private int $dailyCap,
    ) {}

    const TIERS = ['Bronze', 'Silver', 'Gold', 'Platinum'];

    const DEFAULT_TIERS = [
        'Bronze' => ['cash_in_bps' => 50, 'cash_out_bps' => 75, 'transfer_bps' => 100, 'daily_cap' => 50000],
        'Silver' => ['cash_in_bps' => 75, 'cash_out_bps' => 100, 'transfer_bps' => 125, 'daily_cap' => 100000],
        'Gold' => ['cash_in_bps' => 100, 'cash_out_bps' => 125, 'transfer_bps' => 150, 'daily_cap' => 250000],
        'Platinum' => ['cash_in_bps' => 125, 'cash_out_bps' => 150, 'transfer_bps' => 175, 'daily_cap' => 500000],
    ];

    public function calculateCommission(string $txnType, int $amount): int
    {
        $bps = match ($txnType) {
            'cash_in' => $this->cashInBps,
            'cash_out' => $this->cashOutBps,
            'transfer' => $this->transferBps,
            default => 0,
        };

        $commission = (int) round(($amount * $bps) / 10000);

        return min($commission, $this->dailyCap);
    }

    public function tier(): string { return $this->tier; }
    public function cashInBps(): int { return $this->cashInBps; }
    public function cashOutBps(): int { return $this->cashOutBps; }
    public function transferBps(): int { return $this->transferBps; }
    public function dailyCap(): int { return $this->dailyCap; }

    public static function fromString(string $tier): self
    {
        $config = self::DEFAULT_TIERS[$tier] ?? self::DEFAULT_TIERS['Bronze'];
        return new self(
            tier: $tier,
            cashInBps: $config['cash_in_bps'],
            cashOutBps: $config['cash_out_bps'],
            transferBps: $config['transfer_bps'],
            dailyCap: $config['daily_cap'],
        );
    }
}
