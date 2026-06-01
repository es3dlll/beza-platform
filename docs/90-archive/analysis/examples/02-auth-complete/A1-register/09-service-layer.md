# 09 - سيرفس لير العملية — AuthService (Register)

```php
<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Events\UserRegistered;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * تسجيل مستخدم جديد
     *
     * 1. إنشاء المستخدم (uuid, name, phone, password, pin)
     * 2. إنشاء محفظتين (SYP برصيد 0، USD برصيد 5)
     * 3. إنشاء توكن JWT
     * 4. إرسال إشعار ترحيبي (async)
     */
    public function register(
        string  $name,
        string  $phone,
        string  $password,
        string  $pinCode,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array {

        return DB::transaction(function () use (
            $name, $phone, $password, $pinCode, $deviceId, $ip
        ) {
            // ─── 1. إنشاء المستخدم ───
            $user = User::create([
                'uuid'       => (string) Str::uuid(),
                'name'       => $name,
                'phone'      => $phone,
                'password'   => Hash::make($password),
                'pin_code'   => Hash::make($pinCode),
                'status'     => 'pending',
                'kyc_status' => 'not_submitted',
                'device_id'  => $deviceId,
                'last_login_ip' => $ip,
                'last_login_at' => now(),
            ]);

            // ─── 2. إنشاء المحافظ ───
            $wallets = $this->createWallets($user);

            // ─── 3. إنشاء التوكن ───
            $token = JWTAuth::fromUser($user);

            // ─── 4. حدث التسجيل (async) ───
            try {
                UserRegistered::dispatch($user);
            } catch (\Throwable $e) {
                Log::warning('فشل إرسال حدث UserRegistered', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return [
                'user'    => $user,
                'wallets' => $wallets,
                'token'   => $token,
            ];
        }, attempts: 3);
    }

    /**
     * إنشاء محفظتين للمستخدم الجديد
     */
    private function createWallets(User $user): array
    {
        $wallets = [];

        foreach (['SYP', 'USD'] as $currency) {
            $wallet = Wallet::create([
                'user_id'       => $user->id,
                'currency'      => $currency,
                'wallet_number' => $this->generateWalletNumber($currency),
                'balance'       => $currency === 'USD' ? 5.00 : 0.00,
                'frozen_balance'=> 0.00,
                'is_active'     => true,
            ]);

            $wallets[] = $wallet;
        }

        return $wallets;
    }

    private function generateWalletNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '62' : '63';
        do {
            $number = $prefix . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (Wallet::where('wallet_number', $number)->exists());

        return $number;
    }
}
```

## تدفق AuthService::register()

```
1. DB::transaction {
     ├── User::create()
     ├── Wallet::create(SYP, 0)
     ├── Wallet::create(USD, 5)
     ├── Token::create()
     └── UserRegistered::dispatch()
   }
2. Return [user, wallets, token]
```
