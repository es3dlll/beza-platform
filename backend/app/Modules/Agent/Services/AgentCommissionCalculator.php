<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\ValueObjects\Money;

final class AgentCommissionCalculator
{
    private array $settings;

    public function __construct()
    {
        $this->settings = [
            'rates' => [
                'retail' => [0.01, 0.015, 0.02],
                'business' => [0.005, 0.01, 0.015],
                'premium' => [0.002, 0.005, 0.01],
            ],
            'tiers' => [1_000_000, 5_000_000, 10_000_000],
        ];
    }

    public function updateSettings(array $rates, array $tiers): void
    {
        $this->settings['rates'] = $rates;
        $this->settings['tiers'] = $tiers;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function calculate(Agent $agent, Money $transferAmount, string $clientType = 'retail'): Money
    {
        $rate = $this->resolveRate($clientType, $transferAmount->fils());
        $commissionFils = (int) round($transferAmount->fils() * $rate);

        if ($commissionFils < 1) {
            $commissionFils = 1;
        }

        return Money::fromFils($commissionFils, $transferAmount->currency());
    }

    public function previewCommission(Money $transferAmount, string $clientType = 'retail'): array
    {
        $rate = $this->resolveRate($clientType, $transferAmount->fils());
        $commission = (int) round($transferAmount->fils() * $rate);

        if ($commission < 1) {
            $commission = 1;
        }

        return [
            'rate' => $rate,
            'commission_fils' => $commission,
            'currency' => $transferAmount->currency()->value,
        ];
    }

    private function resolveRate(string $clientType, int $amountFils): float
    {
        $clientRates = $this->settings['rates'][$clientType] ?? $this->settings['rates']['retail'];
        $tiers = $this->settings['tiers'];

        for ($i = count($tiers) - 1; $i >= 0; $i--) {
            if ($amountFils >= $tiers[$i]) {
                return $clientRates[$i] ?? end($clientRates);
            }
        }

        return $clientRates[0];
    }
}
