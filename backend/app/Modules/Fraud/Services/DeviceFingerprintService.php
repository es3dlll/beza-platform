<?php

declare(strict_types=1);

namespace Modules\Fraud\Services;

use Modules\Fraud\Repositories\FraudBlacklistRepository;

class DeviceFingerprintService
{
    public function __construct(
        private readonly FraudBlacklistRepository $blacklistRepository,
    ) {}

    public function check(?string $deviceId, ?string $ipAddress): int
    {
        $riskScore = 0;

        if ($deviceId && $this->blacklistRepository->isBlocked('device', $deviceId)) {
            $riskScore += 500;
        }

        if ($ipAddress && $this->blacklistRepository->isBlocked('ip', $ipAddress)) {
            $riskScore += 400;
        }

        return $riskScore;
    }
}
