<?php

declare(strict_types=1);

namespace Modules\Identity\Repositories;

use Illuminate\Support\Str;
use Modules\Identity\Models\OtpCode;

final class OtpRepository
{
    public function create(string $phone, string $purpose, ?string $userId = null): OtpCode
    {
        $plainCode = (string) random_int(100000, 999999);

        $otp = OtpCode::create([
            'user_id' => $userId,
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => bcrypt($plainCode),
            'attempts' => 0,
            'max_attempts' => OtpCode::MAX_ATTEMPTS,
            'expires_at' => now()->addMinutes(OtpCode::EXPIRY_MINUTES),
            'verified_at' => null,
        ]);

        $otp->plain_code = $plainCode;

        return $otp;
    }

    public function findValidOtp(string $phone, string $code, string $purpose): ?OtpCode
    {
        $otps = OtpCode::byPhone($phone)
            ->byPurpose($purpose)
            ->valid()
            ->latest()
            ->get();

        foreach ($otps as $otp) {
            if (password_verify($code, $otp->code_hash)) {
                return $otp;
            }
        }

        return null;
    }

    public function invalidateOtps(string $phone): void
    {
        OtpCode::byPhone($phone)
            ->unverified()
            ->update([
                'expires_at' => now()->subMinute(),
            ]);
    }

    public function countAttemptsToday(string $phone): int
    {
        return OtpCode::byPhone($phone)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('attempts');
    }

    public function countSentToday(string $phone): int
    {
        return OtpCode::byPhone($phone)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function findLatestByPhone(string $phone, string $purpose): ?OtpCode
    {
        return OtpCode::byPhone($phone)
            ->byPurpose($purpose)
            ->latest()
            ->first();
    }

    public function deleteExpired(): int
    {
        return OtpCode::where('expires_at', '<', now())->delete();
    }
}
