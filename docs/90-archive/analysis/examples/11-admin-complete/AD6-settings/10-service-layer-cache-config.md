# 10 - ConfigCacheService

```php
<?php
// app/Services/Admin/ConfigCacheService.php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;

class ConfigCacheService
{
    /**
     * الحصول على إعداد معين من Cache أو توليده
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    /**
     * الحصول على جميع الإعدادات من Cache
     */
    public function getAll(): array
    {
        return Cache::remember('app_settings', 3600, function () {
            return app(SettingsService::class)->getAll();
        });
    }

    /**
     * الحصول على رسوم معاملة معينة
     */
    public function getFee(string $type): float
    {
        $key = "fee_{$type}";
        return (float) ($this->get($key) ?: config("beza.fees.{$type}", 0));
    }

    /**
     * الحصول على حد يومي
     */
    public function getDailyLimit(string $currency): float
    {
        $key = $currency === 'USD' ? 'max_transfer_usd' : 'max_transfer_syp';
        return (float) ($this->get($key) ?: 2000);
    }

    /**
     * الحصول على سعر الصرف
     */
    public function getExchangeRate(): array
    {
        return [
            'rate'   => (float) ($this->get('exchange_rate') ?: 13000),
            'margin' => (float) ($this->get('exchange_margin') ?: 0.5),
            'effective' => function () {
                $rate = $this->get('exchange_rate', 13000);
                $margin = $this->get('exchange_margin', 0.5);
                return $rate * (1 + $margin / 100);
            },
        ];
    }

    /**
     * هل وضع الصيانة مفعل؟
     */
    public function isMaintenanceMode(): bool
    {
        return (bool) ($this->get('maintenance_mode') ?: false);
    }

    /**
     * هل KYC مطلوب؟
     */
    public function isKycRequired(): bool
    {
        return (bool) ($this->get('kyc_required') ?: true);
    }
}
```

## استخدام الإعدادات في الخدمات الأخرى

```php
// في TransferService
$dailyLimit = app(ConfigCacheService::class)->getDailyLimit($currency);

// في ExchangeService
$rate = app(ConfigCacheService::class)->getExchangeRate()['effective'];

// في Middleware
if (app(ConfigCacheService::class)->isMaintenanceMode()) {
    abort(503, 'تحت الصيانة');
}
```
