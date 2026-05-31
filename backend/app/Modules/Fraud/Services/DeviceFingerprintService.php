<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Services;

use App\Modules\Fraud\Models\DeviceFingerprint;
use Illuminate\Support\Str;

final class DeviceFingerprintService
{
    public function computeHash(array $attributes): string
    {
        $raw = implode('|', [
            $attributes['user_agent'] ?? '',
            $attributes['os'] ?? '',
            $attributes['screen_resolution'] ?? '',
            $attributes['app_version'] ?? '',
            $attributes['ip_address'] ?? '',
        ]);

        return hash('sha256', $raw);
    }

    public function registerOrVerify(string $walletId, array $deviceData): DeviceFingerprint
    {
        $hash = $this->computeHash($deviceData);

        $fingerprint = DeviceFingerprint::firstOrCreate(
            ['wallet_id' => $walletId, 'fingerprint_hash' => $hash],
            [
                'user_agent' => $deviceData['user_agent'] ?? null,
                'ip_address' => $deviceData['ip_address'] ?? null,
                'device_type' => $deviceData['device_type'] ?? 'web',
                'app_version' => $deviceData['app_version'] ?? null,
                'os' => $deviceData['os'] ?? null,
                'screen_resolution' => $deviceData['screen_resolution'] ?? null,
                'trust_score' => 500,
                'txn_count' => 0,
                'is_trusted' => false,
                'last_seen_at' => now(),
            ],
        );

        $fingerprint->markSeen();
        return $fingerprint;
    }

    public function getDeviceCount(string $walletId): int
    {
        return DeviceFingerprint::where('wallet_id', $walletId)->count();
    }

    public function getTrustScore(string $walletId, string $hash): int
    {
        $device = DeviceFingerprint::where('wallet_id', $walletId)
            ->where('fingerprint_hash', $hash)
            ->first();

        return $device?->trust_score ?? 0;
    }

    public function adjustTrustScore(string $walletId, string $hash, int $delta): void
    {
        $device = DeviceFingerprint::where('wallet_id', $walletId)
            ->where('fingerprint_hash', $hash)
            ->first();

        if ($device !== null) {
            $oldScore = $device->trust_score;
            $device->updateTrustScore($delta);

            event(new \App\Modules\Fraud\Events\TrustScoreUpdated(
                deviceFingerprintId: $device->id,
                walletId: $walletId,
                oldScore: $oldScore,
                newScore: $device->fresh()->trust_score,
                delta: $delta,
            ));
        }
    }
}
