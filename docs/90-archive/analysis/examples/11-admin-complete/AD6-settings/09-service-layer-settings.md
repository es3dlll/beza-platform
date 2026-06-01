# 09 - SettingsService

```php
<?php
// app/Services/Admin/SettingsService.php

namespace App\Services\Admin;

use App\Events\Admin\SettingsUpdated;
use App\Models\Admin\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    private const CACHE_KEY = 'app_settings';

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $dbSettings = Setting::all()->keyBy('key');
            $config = config('beza');

            return array_merge(
                $this->flattenConfig($config),
                $dbSettings->map(fn($s) => $s->typed_value)->toArray()
            );
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }

    public function updateGeneral(array $data, int $updatedBy): void
    {
        foreach ($data as $key => $value) {
            Setting::setValue($key, $value, $updatedBy);
        }

        $this->clearCache();
        SettingsUpdated::dispatch($data);
    }

    public function updateFees(array $data, int $updatedBy): void
    {
        $map = [
            'transfer'          => 'fee_transfer',
            'exchange'          => 'fee_exchange',
            'card_load'         => 'fee_card_load',
            'merchant_percent'  => 'fee_merchant_percent',
            'merchant_fixed'    => 'fee_merchant_fixed',
            'agent_cash_out'    => 'fee_agent_cash_out',
            'withdraw_bank'     => 'fee_withdraw_bank',
            'deposit_card'      => 'fee_deposit_card',
        ];

        foreach ($data as $key => $value) {
            if (isset($map[$key])) {
                Setting::setValue($map[$key], $value, $updatedBy);
            }
        }

        $this->clearCache();
        SettingsUpdated::dispatch(['group' => 'fees', 'data' => $data]);
    }

    public function updateLimits(array $data, int $updatedBy): void
    {
        $map = [
            'daily_transfer_usd' => 'max_transfer_usd',
            'daily_transfer_syp' => 'max_transfer_syp',
            'min_deposit_usd'    => 'min_deposit_usd',
            'min_deposit_syp'    => 'min_deposit_syp',
        ];

        foreach ($data as $key => $value) {
            if (isset($map[$key])) {
                Setting::setValue($map[$key], $value, $updatedBy);
            }
        }

        $this->clearCache();
        SettingsUpdated::dispatch(['group' => 'limits', 'data' => $data]);
    }

    public function updateExchangeRate(float $rate, float $margin, int $updatedBy): void
    {
        Setting::setValue('exchange_rate', $rate, $updatedBy);
        Setting::setValue('exchange_margin', $margin, $updatedBy);

        $this->clearCache();
        SettingsUpdated::dispatch([
            'group' => 'exchange',
            'rate'  => $rate,
            'margin'=> $margin,
        ]);

        Log::info("Exchange rate updated", [
            'rate'   => $rate,
            'margin' => $margin,
            'by'     => $updatedBy,
        ]);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function flattenConfig(array $config, string $prefix = ''): array
    {
        $result = [];
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenConfig($value, $fullKey));
            } else {
                $result[str_replace('.', '_', $fullKey)] = $value;
            }
        }
        return $result;
    }
}
```
