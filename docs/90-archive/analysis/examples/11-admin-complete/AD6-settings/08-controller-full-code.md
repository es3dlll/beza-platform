# 08 - المتحكم الكامل (Controller)

## SettingsController

```php
<?php
// app/Http/Controllers/Api/Admin/SettingsController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExchangeRateRequest;
use App\Http\Requests\Admin\FeeSettingsRequest;
use App\Http\Requests\Admin\LimitSettingsRequest;
use App\Http\Requests\Admin\SettingsRequest;
use App\Http\Resources\Admin\SettingsResource;
use App\Services\Admin\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new SettingsResource($this->settingsService->getAll()),
        ]);
    }

    public function update(SettingsRequest $request): JsonResponse
    {
        $this->settingsService->updateGeneral(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعدادات العامة',
        ]);
    }

    public function fees(FeeSettingsRequest $request): JsonResponse
    {
        $this->settingsService->updateFees(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رسوم المعاملات',
        ]);
    }

    public function limits(LimitSettingsRequest $request): JsonResponse
    {
        $this->settingsService->updateLimits(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حدود المعاملات',
        ]);
    }

    public function exchangeRate(ExchangeRateRequest $request): JsonResponse
    {
        $this->settingsService->updateExchangeRate(
            rate: $request->input('rate'),
            margin: $request->input('margin'),
            updatedBy: $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سعر الصرف',
        ]);
    }
}
```

## SettingsResource

```php
<?php
// app/Http/Resources/Admin/SettingsResource.php

class SettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'general' => [
                'maintenance_mode' => (bool) ($this['maintenance_mode'] ?? false),
                'kyc_required'     => (bool) ($this['kyc_required'] ?? true),
            ],
            'fees' => [
                'transfer'       => (float) ($this['fee_transfer'] ?? 0),
                'exchange'       => (float) ($this['fee_exchange'] ?? 0.5),
                'card_load'      => (float) ($this['fee_card_load'] ?? 1.5),
                'merchant'       => [
                    'percent' => (float) ($this['fee_merchant_percent'] ?? 2.5),
                    'fixed'   => (float) ($this['fee_merchant_fixed'] ?? 0.30),
                ],
                'agent_cash_out' => (float) ($this['fee_agent_cash_out'] ?? 1.0),
                'withdraw_bank'  => (float) ($this['fee_withdraw_bank'] ?? 1.0),
                'deposit_card'   => (float) ($this['fee_deposit_card'] ?? 2.5),
            ],
            'limits' => [
                'daily_transfer_usd' => (float) ($this['max_transfer_usd'] ?? 2000),
                'daily_transfer_syp' => (float) ($this['max_transfer_syp'] ?? 2000000),
                'min_deposit_usd'    => (float) ($this['min_deposit_usd'] ?? 10),
                'min_deposit_syp'    => (float) ($this['min_deposit_syp'] ?? 10000),
            ],
            'exchange' => [
                'rate'   => (float) ($this['exchange_rate'] ?? 13000),
                'margin' => (float) ($this['exchange_margin'] ?? 0.5),
            ],
            'updated_at' => $this['updated_at'] ?? now()->toIso8601String(),
        ];
    }
}
```

## المسارات (Routes)

```php
Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/settings')
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::put('/fees', [SettingsController::class, 'fees']);
        Route::put('/limits', [SettingsController::class, 'limits']);
        Route::put('/exchange-rate', [SettingsController::class, 'exchangeRate']);
    });
```
