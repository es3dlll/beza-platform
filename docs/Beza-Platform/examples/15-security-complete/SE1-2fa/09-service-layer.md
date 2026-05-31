# 09 - خدمة 2FA (TwoFactorService)

```php
<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * توليد Secret جديد
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * إنشاء رابط QR Code (يمكن تحويله لصورة)
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            'Beza Platform',
            $user->email ?: $user->phone,
            $secret
        );
    }

    /**
     * إنشاء SVG لـ QR Code
     */
    public function getQrCodeSvg(string $secret): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($secret);
    }

    /**
     * التحقق من رمز TOTP
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // السماح بانحراف (±1 دقيقة) لتفاوت الوقت
        return $this->google2fa->verifyKeyNewer(
            $secret,
            $code,
            optional(session('last_2fa_timestamp'))->timestamp
        ) !== false;
    }

    /**
     * توليد 8 رموز استرداد
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(
                implode('-', [
                    substr(bin2hex(random_bytes(3)), 0, 4),
                    substr(bin2hex(random_bytes(3)), 0, 4),
                    substr(bin2hex(random_bytes(3)), 0, 4),
                ])
            );
        }
        return $codes;
    }

    /**
     * استخدام رمز استرداد
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->recoveryCodes();

        if (!$codes) return false;

        $index = array_search(strtoupper($code), $codes);

        if ($index === false) return false;

        // إزالة الرمز المستخدم
        unset($codes[$index]);
        $user->setRecoveryCodes(array_values($codes));
        $user->save();

        return true;
    }

    /**
     * التحقق مما إذا كانت 2FA مطلوبة
     */
    public function isRequired(User $user, ?float $amount = null, string $currency = 'USD'): bool
    {
        if (!$user->hasTwoFactorEnabled()) return false;
        return $user->requiresTwoFactor($amount ?? 0, $currency);
    }
}
```

## التثبيت

```bash
composer require pragmarx/google2fa
composer require bacon/bacon-qr-code
```
