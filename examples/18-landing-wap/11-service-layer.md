# Service Layer — WAP

WAP يعيد استخدام Services الموجودة:

| الخدمة الحالية | الاستخدام في WAP |
|---------------|-----------------|
| `AuthService` | login, logout, refresh, me |
| `WalletService` | balance (عرض الرصيد) |
| `TransferService` | transfer (تحويل P2P مع idempotency) |
| `MerchantService` | summary, qr, settlements |
| `AgentService` | limits, commissions, pending |

## تغييرات مطلوبة على الخدمات الحالية
- `TransferService::transfer()`: دعم `idempotency_key` (قد يكون موجوداً)
- `AuthService::login()`: إرجاع JWT + Refresh Token (يدعم Cookie mode)
- إضافة `device_type` field: `"wap"` في الـ response للتمييز

## مثال — WalletController::balance مع ?format=minimal
```php
public function balance(Request $request, WalletService $walletService)
{
    $wallets = $walletService->getUserWallets($request->user()->id);

    if ($request->query('format') === 'minimal') {
        return response()->json([
            'success' => true,
            'data' => $wallets->map(fn($w) => [
                'balance'    => $w->balance,
                'currency'   => $w->currency,
                'updated_at' => $w->updated_at,
            ]),
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => $wallets,
    ]);
}
```
