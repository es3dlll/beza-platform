# 09 - طبقة الخدمة (Two-Factor Service) — المصادقة الثنائية (2FA)

```php
<?php
// app/Services/TwoFactorService.php

namespace App\Services;

use App\Exceptions\InvalidTwoFactorCodeException;
use App\Exceptions\TwoFactorAlreadyEnabledException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * تفعيل 2FA — توليد secret و QR code
     *
     * @return array{qr_code: string, secret: string}
     */
    public function enable(User $user): array
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new TwoFactorAlreadyEnabledException();
        }

        // توليد مفتاح سري
        $secret = $this->google2fa->generateSecretKey(32);

        // تخزين المفتاح مشفراً (يتم عبر Accessor/Mutator)
        $user->enableTwoFactor($secret);

        // إنشاء QR Code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            companyName: config('app.name', 'Beza'),
            companyEmail: $user->phone,
            secret: $secret,
        );

        // توليد صورة QR (base64)
        $qrCode = $this->generateQrCodeBase64($qrCodeUrl);

        Log::info('تفعيل 2FA', ['user_id' => $user->id]);

        return [
            'qr_code' => $qrCode,
            'secret'  => $secret,
        ];
    }

    /**
     * تأكيد تفعيل 2FA
     *
     * @throws InvalidTwoFactorCodeException
     */
    public function verify(User $user, string $code): void
    {
        $secret = $user->two_factor_secret;

        if (!$secret) {
            throw new InvalidTwoFactorCodeException('2FA غير مفعل');
        }

        $valid = $this->google2fa->verifyKey($secret, $code, 1);

        if (!$valid) {
            throw new InvalidTwoFactorCodeException();
        }

        $user->confirmTwoFactor();

        // توليد رموز استرداد
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->setRecoveryCodes($recoveryCodes);

        Log::info('تأكيد تفعيل 2FA', ['user_id' => $user->id]);
    }

    /**
     * تعطيل 2FA
     *
     * @throws InvalidCredentialsException
     * @throws InvalidTwoFactorCodeException
     */
    public function disable(User $user, string $password, ?string $code = null): void
    {
        // إعادة المصادقة
        if (!Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        // إذا كان 2FA مفعل — التحقق من الرمز (أو recovery code)
        if ($user->hasTwoFactorEnabled()) {
            if ($code) {
                $secret = $user->two_factor_secret;
                $valid = $this->google2fa->verifyKey($secret, $code, 1);

                if (!$valid) {
                    // التحقق من recovery codes
                    if (!$user->useRecoveryCode($code)) {
                        throw new InvalidTwoFactorCodeException();
                    }
                }
            } else {
                throw new InvalidTwoFactorCodeException('الرمز مطلوب لتعطيل 2FA');
            }
        }

        $user->disableTwoFactor();

        Log::info('تعطيل 2FA', ['user_id' => $user->id]);
    }

    /**
     * التحقق من رمز 2FA أثناء تسجيل الدخول
     */
    public function verifyForLogin(User $user, ?string $code): bool
    {
        if (!$user->hasTwoFactorEnabled()) {
            return true; // 2FA غير مفعل
        }

        if (!$code) {
            return false; // الرمز مطلوب
        }

        $secret = $user->two_factor_secret;

        // التحقق من TOTP
        if ($this->google2fa->verifyKey($secret, $code, 1)) {
            return true;
        }

        // التحقق من recovery codes
        if ($user->useRecoveryCode($code)) {
            return true;
        }

        return false;
    }

    private function generateQrCodeBase64(string $url): string
    {
        // يمكن استخدام مكتبة مثل simplesoftwareio/simple-qrcode
        // return base64_encode(\QrCode::format('png')->size(200)->generate($url));
        return 'data:image/png;base64,' . base64_encode($url);
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(
                'BZFA-' . substr(bin2hex(random_bytes(2)), 0, 4) . '-' . substr(bin2hex(random_bytes(2)), 0, 4)
            );
        }
        return $codes;
    }
}
```

## تدفق TwoFactorService

```
enable():
1. Check 2FA not already enabled
2. Generate secret key (32 chars)
3. Store encrypted in DB
4. Generate QR code URL
5. Return [qr_code, secret]

verify():
1. Get stored secret
2. Google2FA::verifyKey(secret, code)
3. Mark two_factor_confirmed = true
4. Generate recovery codes
5. Return success

verifyForLogin():
1. Check if 2FA is enabled
2. Verify TOTP code
3. Or verify recovery code
4. Return boolean
```
