<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Modules\Wallet\Exceptions\InvalidStateTransitionException;
use Modules\Wallet\Models\Wallet;

final class WalletStateMachine
{
    private const TRANSITIONS = [
        'pending' => ['activate' => 'active', 'close' => 'closed'],
        'active' => ['suspend' => 'suspended', 'limit' => 'limited', 'freeze' => 'frozen', 'close' => 'closed'],
        'limited' => ['activate' => 'active', 'suspend' => 'suspended', 'freeze' => 'frozen', 'close' => 'closed'],
        'suspended' => ['activate' => 'active', 'freeze' => 'frozen', 'close' => 'closed'],
        'frozen' => ['activate' => 'active', 'close' => 'closed'],
        'closed' => [],
    ];

    public function canTransition(Wallet $wallet, string $action): bool
    {
        $transitions = self::TRANSITIONS[$wallet->status] ?? [];
        return isset($transitions[$action]);
    }

    public function transition(Wallet $wallet, string $action, ?string $reason = null): Wallet
    {
        $transitions = self::TRANSITIONS[$wallet->status] ?? [];

        if (!isset($transitions[$action])) {
            throw new InvalidStateTransitionException(
                $wallet->status,
                $action,
                $wallet->id
            );
        }

        $wallet->status = $transitions[$action];

        $metadata = $wallet->metadata ?? [];
        $metadata['state_history'][] = [
            'from' => $wallet->getOriginal('status'),
            'to' => $wallet->status,
            'action' => $action,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];
        $wallet->metadata = $metadata;

        $wallet->save();

        return $wallet;
    }

    public static function allowedActions(string $currentStatus): array
    {
        return array_keys(self::TRANSITIONS[$currentStatus] ?? []);
    }

    public static function isValidTransition(string $from, string $to): bool
    {
        $transitions = self::TRANSITIONS[$from] ?? [];
        return in_array($to, $transitions, true);
    }
}
