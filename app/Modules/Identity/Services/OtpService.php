<?php

declare(strict_types=1);

namespace Modules\Identity\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Auth\Events\OtpGenerated;
use Modules\Identity\Models\OtpCode;
use Modules\Identity\Models\User;

class OtpService
{
    public const PURPOSE_REGISTER = 'register';
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_CHANGE_PHONE = 'change_phone';
    public const PURPOSE_FORGOT_PIN = 'forgot_pin';

    public function generateAndSend(string $phone, string $purpose): OtpCode
    {
        return $this->generate($phone, $purpose);
    }

    public function generate(string $phone, string $purpose, ?string $userId = null): OtpCode
    {
        $this->invalidatePreviousCodes($phone, $purpose);

        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::create([
            'user_id' => $userId,
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => bcrypt($code),
            'attempts' => 0,
            'max_attempts' => OtpCode::MAX_ATTEMPTS,
            'expires_at' => now()->addMinutes(config('beza.otp_expiry_minutes', 5)),
        ]);

        Cache::put("otp_plain_{$otp->id}", $code, now()->addMinutes(config('beza.otp_expiry_minutes', 5)));

        event(new OtpGenerated($phone, $code, $purpose));

        return $otp;
    }

    public function verify(string $phone, string $code, string $purpose): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($otp === null) {
            return false;
        }

        if ($otp->hasReachedMaxAttempts()) {
            return false;
        }

        $plainCode = Cache::get("otp_plain_{$otp->id}");

        $matches = $plainCode !== null
            ? $plainCode === $code
            : password_verify($code, $otp->code_hash);

        if (!$matches) {
            $otp->incrementAttempts();
            return false;
        }

        $otp->markAsVerified();

        Cache::forget("otp_plain_{$otp->id}");

        if ($otp->user_id !== null) {
            User::where('id', $otp->user_id)->update([
                'phone_verified_at' => now(),
                'status' => 'active',
            ]);
        }

        return true;
    }

    public function isRateLimited(string $phone, string $purpose): bool
    {
        $attempts = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->sum('attempts');

        return $attempts >= config('beza.max_otp_attempts', 5);
    }

    private function invalidatePreviousCodes(string $phone, string $purpose): void
    {
        OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->update([
                'expires_at' => now()->subMinute(),
            ]);
    }
}
