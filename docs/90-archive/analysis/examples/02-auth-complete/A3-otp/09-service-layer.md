# 09 - طبقة الخدمة (Service Layer) — رمز التحقق (OTP)

```php
<?php
// app/Services/OtpService.php

namespace App\Services;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpAttemptsExceededException;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const OTP_TTL = 300; // 5 دقائق
    private const MAX_ATTEMPTS = 5;

    /**
     * توليد وإرسال OTP
     *
     * @return OtpCode
     */
    public function generate(string $phone): OtpCode
    {
        // إلغاء أي OTP سابق
        Cache::forget($this->cacheKey($phone));

        // توليد رمز جديد
        $otp = OtpCode::generate($phone);

        // تخزين في Redis
        Cache::put(
            $this->cacheKey($phone),
            $otp->toArray(),
            now()->addSeconds(self::OTP_TTL)
        );

        // إرسال SMS
        try {
            app(SmsService::class)->sendOtp($phone, $otp->code);
        } catch (\Throwable $e) {
            Log::error('فشل إرسال SMS OTP', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('تم إرسال OTP', [
            'phone' => $phone,
            'otp'   => app()->environment('local') ? $otp->code : '***',
        ]);

        return $otp;
    }

    /**
     * التحقق من OTP
     *
     * @throws InvalidOtpException
     * @throws OtpExpiredException
     * @throws OtpAttemptsExceededException
     */
    public function verify(string $phone, string $code): void
    {
        $cached = Cache::get($this->cacheKey($phone));

        // التحقق من وجود OTP
        if (!$cached) {
            throw new OtpExpiredException();
        }

        $otp = OtpCode::fromArray($phone, $cached);

        // التحقق من الصلاحية
        if ($otp->isExpired()) {
            Cache::forget($this->cacheKey($phone));
            throw new OtpExpiredException();
        }

        // التحقق من عدد المحاولات
        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->cacheKey($phone));
            throw new OtpAttemptsExceededException();
        }

        // مقارنة الرمز
        if ($otp->code !== $code) {
            $otp->incrementAttempts();
            Cache::put(
                $this->cacheKey($phone),
                $otp->toArray(),
                now()->addSeconds(self::OTP_TTL)
            );
            throw new InvalidOtpException();
        }

        // نجاح — تحديث DB وحذف Cache
        User::where('phone', $phone)
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => now()]);

        Cache::forget($this->cacheKey($phone));

        Log::info('تم التحقق من OTP بنجاح', ['phone' => $phone]);
    }

    private function cacheKey(string $phone): string
    {
        return 'otp_' . $phone;
    }
}
```

## SmsService

```php
<?php
// app/Services/SmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $phone, string $code): void
    {
        $message = "رمز التحقق الخاص بك في Beza: {$code}\nالرمز صالح لمدة 5 دقائق.";

        // استخدام واجهة SMS Gateway
        // Http::withHeaders([...])->post(config('services.sms.url'), [
        //     'to'      => $phone,
        //     'message' => $message,
        // ]);

        Log::info('SMS Service: OTP sent', [
            'phone' => $phone,
            'code'  => $code,
        ]);
    }
}
```

## تدفق OtpService

```
generate():
1. Delete old OTP from Cache
2. Generate 6-digit random code
3. Store in Redis (TTL: 300s)
4. Send SMS via SmsService
5. Return OtpCode

verify():
1. Get OTP from Cache
2. Check if OTP exists (else: expired)
3. Check if OTP not expired (else: expired)
4. Check attempts < 5 (else: exceeded)
5. Compare codes (else: invalid + increment)
6. Update phone_verified_at in DB
7. Delete OTP from Cache
```
