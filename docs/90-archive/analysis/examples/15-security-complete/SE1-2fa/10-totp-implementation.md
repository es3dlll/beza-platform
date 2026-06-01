# 10 - تنفيذ TOTP (TOTP Implementation)

## كيف يعمل TOTP

```
TOTP = Time-based One-Time Password

1. Secret Key (مشترك بين الخادم وتطبيق Authenticator)
2. الوقت الحالي (Unix timestamp / 30)
3. HMAC-SHA1(secret, time_counter)
4. truncate → 6 أرقام

يتغير كل 30 ثانية ← رمز جديد
```

## التكامل مع Google Authenticator

```php
// توليد Secret
$secret = $google2fa->generateSecretKey(32);
// مثال: "4S2J5SJ4N5HUI5KJN4QGM3DNXUZAS57D"

// رابط QR
$qrUrl = $google2fa->getQRCodeUrl('Beza', 'user@beza.example', $secret);
// otpauth://totp/Beza:user@beza.example?secret=...&issuer=Beza

// التحقق
$valid = $google2fa->verifyKey($secret, $inputCode);
```

## معالجة فرق التوقيت

```php
// السماح بانحراف دقيقة واحدة (±2 رمز)
$valid = $google2fa->verifyKeyNewer($secret, $code, $lastTimestamp);

// أو يدوياً
$window = 1; // ±1 رمز (30 ثانية)
for ($i = -$window; $i <= $window; $i++) {
    $timeSlice = $this->google2fa->getTimestamp() + $i;
    if (hash_equals($this->google2fa->oathHotp($secret, $timeSlice), $code)) {
        return true;
    }
}
```

## طباعة QR Code كـ SVG

```php
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

$renderer = new ImageRenderer(
    new RendererStyle(400),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);
$svg = $writer->writeString($otpauthUrl);
```
