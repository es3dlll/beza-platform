# 5. الخلفية: Laravel API Core

## 5.1 هيكل المشروع

```
backend-laravel/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CalculateDailyProfits.php
│   │       ├── UpdateExchangeRates.php
│   │       ├── SettleAgents.php
│   │       └── ExpirePendingTransactions.php
│   ├── Exceptions/
│   │   ├── InsufficientBalanceException.php
│   │   ├── InvalidPinException.php
│   │   └── MerchantException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── WalletController.php
│   │   │   │   ├── TransferController.php
│   │   │   │   ├── ExchangeController.php
│   │   │   │   ├── DealController.php

│   │   ├── Deal.php
│   │   ├── DealInvestment.php

│   │   ├── DealService.php

│   │   ├── 2024_01_01_000006_create_deals_table.php
│   │   ├── 2024_01_01_000007_create_deal_investments_table.php

│   │   ├── DealsSeeder.php
│   │   └── SettingsSeeder.php
├── routes/
│   ├── api.php             # مسارات المستخدمين العاديين (auth:api)
│   ├── admin.php           # مسارات المشرفين (auth:api, verified)
│   ├── merchant.php        # مسارات التجار (auth:api, verified)
│   ├── agent.php           # مسارات الوكيل (auth:api, verified)
│   └── webhook.php         # نقاط نهاية للأنظمة الخارجية (بدون مصادقة)
├── config/
│   ├── jwt.php              # إعدادات JWT (tymon/jwt-auth)
│   ├── cors.php
│   ├── queue.php
│   ├── services.php (Stripe, Twilio, etc.)
│   └── beza.php (رسوم، حدود)
└── .env
```

## 5.2 نماذج البيانات الأساسية (Migrations)

### 5.2.1 جدول المستخدمين (users)

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('email')->unique()->nullable();
    $table->string('phone')->unique();
    $table->string('password');
    $table->string('pin_code')->nullable();
    $table->string('avatar')->nullable();
    $table->enum('status', ['pending', 'active', 'suspended', 'blocked'])->default('pending');
    $table->enum('kyc_status', ['not_submitted', 'pending', 'verified', 'rejected'])->default('not_submitted');
    $table->timestamp('phone_verified_at')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('two_factor_secret')->nullable();
    $table->json('two_factor_recovery_codes')->nullable();
    $table->boolean('is_admin')->default(false);
    $table->boolean('is_merchant')->default(false);
    $table->boolean('is_agent')->default(false);
    $table->json('preferences')->nullable();
    $table->string('device_id')->nullable();
    $table->string('fcm_token')->nullable();
    $table->ipAddress('last_login_ip')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['phone', 'status']);
    $table->index(['email']);
});
```

### 5.2.2 جدول المحافظ (wallets)

```php
Schema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('currency', ['SYP', 'USD']);
    $table->string('wallet_number', 20)->unique();
    $table->decimal('balance', 15, 2)->default(0);
    $table->decimal('frozen_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->unique(['user_id', 'currency']);
    $table->index('wallet_number');
});
```

### 5.2.3 جدول المعاملات (transactions)

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('from_wallet_id')->nullable()->constrained('wallets');
    $table->foreignId('to_wallet_id')->nullable()->constrained('wallets');
    $table->decimal('amount', 15, 2);
    $table->decimal('amount_in_usd', 15, 2);
    $table->enum('type', [
        'deposit', 'withdraw', 'transfer', 'exchange',
        'merchant_payment', 'agent_cash_in', 'agent_cash_out',
        'investment', 'investment_profit', 'card_load', 'card_payment',
        'fee'
    ]);
    $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
    $table->string('reference_number', 50)->unique();
    $table->text('description')->nullable();
    $table->decimal('fee', 15, 2)->default(0);
    $table->json('metadata')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->index(['from_wallet_id', 'status']);
    $table->index(['to_wallet_id', 'status']);
    $table->index(['type', 'created_at']);
    $table->index('reference_number');
});
```

### 5.2.4 جدول التجار (merchants)

```php
Schema::create('merchants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('business_name');
    $table->string('business_register_number')->unique();
    $table->string('tax_number')->nullable();
    $table->enum('business_type', ['individual', 'company', 'nonprofit']);
    $table->enum('merchant_type', ['ecommerce', 'physical', 'both']);
    $table->string('website')->nullable();
    $table->string('logo')->nullable();
    $table->text('description')->nullable();
    $table->json('contact_info');
    $table->json('bank_account');
    $table->decimal('settlement_fee_percent', 5, 2)->default(2.5);
    $table->decimal('settlement_fee_fixed', 10, 2)->default(0.30);
    $table->enum('settlement_period', ['daily', 'weekly', 'monthly'])->default('daily');
    $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
    $table->string('api_key', 64)->unique()->nullable();
    $table->string('webhook_url')->nullable();
    $table->timestamps();
});
```

### 5.2.5 جدول منتجات التاجر (merchant_products)

```php
Schema::create('merchant_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 15, 2);
    $table->enum('currency', ['SYP', 'USD'])->default('USD');
    $table->integer('stock')->default(0);
    $table->string('image')->nullable();
    $table->json('variants')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 5.2.6 جدول طلبات التاجر (merchant_orders)

```php
Schema::create('merchant_orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number')->unique();
    $table->foreignId('merchant_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->decimal('total_amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('shipping_fee', 15, 2)->default(0);
    $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
    $table->json('shipping_address');
    $table->json('items');
    $table->foreignId('transaction_id')->nullable()->constrained();
    $table->timestamps();
});
```

### 5.2.7 جدول الوكلاء (agents)

```php
Schema::create('agents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('shop_name');
    $table->string('license_number')->unique();
    $table->string('address');
    $table->string('city');
    $table->string('latitude')->nullable();
    $table->string('longitude')->nullable();
    $table->decimal('cash_balance_syp', 15, 2)->default(0);
    $table->decimal('cash_balance_usd', 15, 2)->default(0);
    $table->decimal('wallet_balance_syp', 15, 2)->default(0);
    $table->decimal('wallet_balance_usd', 15, 2)->default(0);
    $table->decimal('commission_percent', 5, 2)->default(1.0);
    $table->decimal('daily_limit', 15, 2)->default(10000);
    $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
    $table->timestamps();
});
```

### 5.2.8 جدول معاملات الوكيل (agent_transactions)

```php
Schema::create('agent_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agent_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->enum('type', ['cash_in', 'cash_out']);
    $table->decimal('amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->string('reference_code', 10)->unique();
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
    $table->decimal('commission_earned', 15, 2)->default(0);
    $table->foreignId('transaction_id')->nullable()->constrained();
    $table->timestamps();
});
```

### 5.2.9 جدول الصفقات الاستثمارية (deals)

```php
Schema::create('deals', function (Blueprint $table) {
    $table->id();
    $table->string('title'); // عنوان الصفقة (مثلاً: تمويل شحنة إلكترونيات من دبي)
    $table->text('description');
    $table->decimal('target_amount', 15, 2); // المبلغ المستهدف للصفقة
    $table->decimal('invested_amount', 15, 2)->default(0); // المبلغ المحصل
    $table->enum('currency', ['SYP', 'USD'])->default('USD');
    $table->decimal('expected_profit_percent', 5, 2); // الربح المتوقع %
    $table->integer('duration_days'); // مدة الصفقة بالأيام
    $table->enum('status', ['open', 'funded', 'active', 'completed', 'cancelled'])->default('open');
    $table->string('merchant_name')->nullable(); // اسم التاجر المستفيد
    $table->string('merchant_info')->nullable(); // وصف التاجر
    $table->string('image')->nullable(); // صورة الشحنة أو المنتج
    $table->json('documents')->nullable(); // مستندات الصفقة
    $table->timestamp('started_at')->nullable(); // تاريخ بدء الصفقة
    $table->timestamp('completed_at')->nullable(); // تاريخ إكمال الصفقة
    $table->timestamps();
});

Schema::create('deal_investments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('deal_id')->constrained()->onDelete('cascade');
    $table->decimal('amount', 15, 2);
    $table->decimal('profit_percent', 5, 2); // نسبة الربح المتفق عليها للصفقة
    $table->decimal('profit_earned', 15, 2)->default(0); // الربح الفعلي المحقق
    $table->decimal('total_return', 15, 2)->default(0); // إجمالي العائد (رأس المال + الربح)
    $table->enum('status', ['invested', 'completed', 'refunded'])->default('invested');
    $table->timestamp('invested_at');
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

**ملاحظة:** كل صفقة (deal) هي فرصة استثمارية مرتبطة بشحنة أو عملية تمويل تجاري محدد. يشارك المستخدم في الصفقة بنسبة من المبلغ المستهدف، ويحصل على أرباحه بعد إتمام الصفقة (عند بيع الشحنة).

## 5.3 وحدات التحكم الرئيسية (Controllers)

### 5.3.1 AuthController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\ApiController;
use App\Services\KycService;
use App\Services\NotificationService;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|regex:/^09[0-9]{8}$/',
            'password' => 'required|string|min:8|confirmed',
            'pin_code' => 'required|string|size:4|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'pin_code' => Hash::make($validated['pin_code']),
                'status' => 'pending',
                'kyc_status' => 'not_submitted',
            ]);

            $wallets = [];
            foreach (['SYP', 'USD'] as $currency) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'currency' => $currency,
                    'wallet_number' => 'BEZA' . rand(10000000, 99999999),
                    'balance' => $currency === 'USD' ? 5 : 0,
                ]);
                $wallets[] = $wallet;
            }

            DB::commit();

            NotificationService::sendWelcomeSms($user->phone);
            $token = JWTAuth::fromUser($user);

            return $this->success([
                'user' => $user->only(['id', 'name', 'phone', 'status']),
                'wallets' => $wallets,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ], 'تم التسجيل بنجاح. أكمل بياناتك لتوثيق الحساب.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل التسجيل: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('بيانات الدخول غير صحيحة', 401);
        }

        if ($user->status === 'suspended') {
            return $this->error('الحساب معلق. تواصل مع الدعم.', 403);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'device_id' => $request->header('X-Device-ID'),
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->success([
            'user' => $user->only(['id', 'name', 'phone', 'status', 'kyc_status']),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get('otp_' . $request->phone);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return $this->error('رمز التحقق غير صحيح أو منتهي الصلاحية', 400);
        }

        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            $user->update(['phone_verified_at' => now()]);
        }

        Cache::forget('otp_' . $request->phone);

        return $this->success(null, 'تم التحقق من رقم الهاتف بنجاح');
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->phone, $otp, 300);

        return $this->success(null, 'تم إرسال رمز التحقق');
    }

    public function logout(Request $request)
    {
        JWTAuth::parseToken()->invalidate();
        return $this->success(null, 'تم تسجيل الخروج');
    }
}
```

### 5.3.2 TransferController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Services\WalletService;
use App\Services\NotificationService;
use App\Events\TransactionCompleted;

class TransferController extends ApiController
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'to_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:SYP,USD',
            'pin' => 'required|string|size:4',
        ]);

        $fromUser = auth()->user();
        $toUser = User::where('phone', $request->to_phone)->first();

        if ($fromUser->id === $toUser->id) {
            return $this->error('لا يمكن التحويل إلى نفسك', 400);
        }

        if (!Hash::check($request->pin, $fromUser->pin_code)) {
            return $this->error('الرقم السري (PIN) غير صحيح', 400);
        }

        $fromWallet = $fromUser->wallets()->where('currency', $request->currency)->first();
        $toWallet = $toUser->wallets()->where('currency', $request->currency)->first();

        if (!$fromWallet || !$toWallet) {
            return $this->error('محفظة غير موجودة', 404);
        }

        if ($fromWallet->balance < $request->amount) {
            return $this->error('رصيد غير كافٍ', 400);
        }

        DB::beginTransaction();
        try {
            $fromWallet->decrement('balance', $request->amount);
            $toWallet->increment('balance', $request->amount);

            $transaction = Transaction::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $request->amount,
                'amount_in_usd' => $request->currency === 'USD'
                    ? $request->amount
                    : $this->walletService->convertToUsd($request->amount),
                'type' => 'transfer',
                'status' => 'completed',
                'reference_number' => 'TXN' . time() . rand(1000, 9999),
                'fee' => 0,
                'completed_at' => now(),
            ]);

            DB::commit();

            event(new TransactionCompleted($transaction, $fromUser, $toUser));

            return $this->success([
                'transaction' => $transaction,
                'new_balance' => $fromWallet->fresh()->balance,
            ], 'تم التحويل بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل التحويل: ' . $e->getMessage(), 500);
        }
    }
}
```

### 5.3.3 MerchantController (بوابة الدفع للتجار)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class MerchantController extends ApiController
{
    public function createPayment(Request $request)
    {
        $merchant = auth()->user()->merchant;

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:SYP,USD',
            'order_id' => 'required|string',
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'redirect_url' => 'required|url',
            'webhook_url' => 'nullable|url',
        ]);

        $payment = MerchantPayment::create([
            'merchant_id' => $merchant->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'order_id' => $request->order_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'redirect_url' => $request->redirect_url,
            'webhook_url' => $request->webhook_url,
            'status' => 'pending',
            'reference' => 'MP' . time() . rand(10000, 99999),
        ]);

        $paymentUrl = config('app.frontend_url') . '/pay/' . $payment->reference;

        return $this->success([
            'payment_url' => $paymentUrl,
            'reference' => $payment->reference,
        ]);
    }

    public function processPayment($reference, Request $request)
    {
        $payment = MerchantPayment::where('reference', $reference)->firstOrFail();

        if ($payment->status !== 'pending') {
            return $this->error('هذا الدفع تمت معالجته مسبقاً', 400);
        }

        $user = auth()->user();
        $wallet = $user->wallets()->where('currency', $payment->currency)->first();

        if ($wallet->balance < $payment->amount) {
            return $this->error('رصيد غير كافٍ', 400);
        }

        DB::beginTransaction();
        try {
            $wallet->decrement('balance', $payment->amount);

            $merchantWallet = $payment->merchant->user->wallets()->where('currency', $payment->currency)->first();
            $merchantWallet->increment('balance', $payment->amount);

            $transaction = Transaction::create([
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $merchantWallet->id,
                'amount' => $payment->amount,
                'amount_in_usd' => $payment->currency === 'USD' ? $payment->amount : $this->convertToUsd($payment->amount),
                'type' => 'merchant_payment',
                'status' => 'completed',
                'reference_number' => 'PAY' . time() . rand(1000, 9999),
                'fee' => ($payment->amount * $payment->merchant->settlement_fee_percent / 100) + $payment->merchant->settlement_fee_fixed,
                'metadata' => json_encode(['merchant_payment_id' => $payment->id]),
                'completed_at' => now(),
            ]);

            $payment->update([
                'status' => 'completed',
                'transaction_id' => $transaction->id,
            ]);

            DB::commit();

            if ($payment->webhook_url) {
                $this->sendWebhook($payment->webhook_url, $payment);
            }

            return redirect($payment->redirect_url . '?success=true&ref=' . $payment->reference);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل الدفع', 500);
        }
    }
}
```

### 5.3.4 AgentController (للوكيل)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class AgentController extends ApiController
{
    public function cashIn(Request $request)
    {
        $request->validate([
            'user_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:SYP,USD',
        ]);

        $agent = auth()->user()->agent;
        $customer = User::where('phone', $request->user_phone)->first();

        $cashBalanceField = $request->currency === 'SYP' ? 'cash_balance_syp' : 'cash_balance_usd';
        if ($agent->$cashBalanceField < $request->amount) {
            return $this->error('الوكيل لا يملك رصيداً نقدياً كافياً', 400);
        }

        $referenceCode = rand(100000, 999999);

        DB::beginTransaction();
        try {
            $agent->decrement($cashBalanceField, $request->amount);
            $customerWallet = $customer->wallets()->where('currency', $request->currency)->first();
            $customerWallet->increment('balance', $request->amount);

            $agentTransaction = AgentTransaction::create([
                'agent_id' => $agent->id,
                'user_id' => $customer->id,
                'type' => 'cash_in',
                'amount' => $request->amount,
                'currency' => $request->currency,
                'reference_code' => $referenceCode,
                'status' => 'completed',
                'commission_earned' => $request->amount * ($agent->commission_percent / 100),
            ]);

            $transaction = Transaction::create([
                'from_wallet_id' => null,
                'to_wallet_id' => $customerWallet->id,
                'amount' => $request->amount,
                'amount_in_usd' => $request->currency === 'USD' ? $request->amount : $this->convertToUsd($request->amount),
                'type' => 'agent_cash_in',
                'status' => 'completed',
                'reference_number' => 'CIN' . time() . $referenceCode,
                'description' => "إيداع نقدي عبر وكيل: {$agent->shop_name}",
                'fee' => 0,
                'metadata' => json_encode(['agent_transaction_id' => $agentTransaction->id]),
                'completed_at' => now(),
            ]);

            $agentTransaction->update(['transaction_id' => $transaction->id]);

            DB::commit();

            return $this->success([
                'reference_code' => $referenceCode,
                'new_balance' => $customerWallet->fresh()->balance,
            ], 'تم الإيداع النقدي بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل الإيداع: ' . $e->getMessage(), 500);
        }
    }

    public function cashOut(Request $request)
    {
        $request->validate([
            'reference_code' => 'required|string|size:6',
            'pin' => 'required|string|size:4',
        ]);

        $agent = auth()->user()->agent;

        $agentTransaction = AgentTransaction::where('reference_code', $request->reference_code)
            ->where('status', 'pending')
            ->first();

        if (!$agentTransaction) {
            return $this->error('رمز غير صالح أو منتهي الصلاحية', 400);
        }

        $customer = $agentTransaction->user;

        if (!Hash::check($request->pin, $customer->pin_code)) {
            return $this->error('PIN غير صحيح', 400);
        }

        $customerWallet = $customer->wallets()->where('currency', $agentTransaction->currency)->first();

        if ($customerWallet->balance < $agentTransaction->amount) {
            return $this->error('رصيد العميل غير كافٍ', 400);
        }

        DB::beginTransaction();
        try {
            $customerWallet->decrement('balance', $agentTransaction->amount);
            $cashBalanceField = $agentTransaction->currency === 'SYP' ? 'cash_balance_syp' : 'cash_balance_usd';
            $agent->increment($cashBalanceField, $agentTransaction->amount);

            $agentTransaction->update(['status' => 'completed']);

            $transaction = Transaction::create([
                'from_wallet_id' => $customerWallet->id,
                'to_wallet_id' => null,
                'amount' => $agentTransaction->amount,
                'amount_in_usd' => $agentTransaction->currency === 'USD' ? $agentTransaction->amount : $this->convertToUsd($agentTransaction->amount),
                'type' => 'agent_cash_out',
                'status' => 'completed',
                'reference_number' => 'COUT' . time() . $agentTransaction->reference_code,
                'description' => "سحب نقدي من وكيل: {$agent->shop_name}",
                'fee' => $agentTransaction->amount * ($agent->commission_percent / 100),
                'completed_at' => now(),
            ]);

            DB::commit();

            return $this->success(null, 'تم السحب النقدي بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل السحب', 500);
        }
    }

    public function createWithdrawalCode(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:SYP,USD',
        ]);

        $user = auth()->user();
        $wallet = $user->wallets()->where('currency', $request->currency)->first();

        if ($wallet->balance < $request->amount) {
            return $this->error('رصيد غير كافٍ', 400);
        }

        $wallet->decrement('balance', $request->amount);
        $wallet->increment('frozen_balance', $request->amount);

        $referenceCode = rand(100000, 999999);

        $agentTransaction = AgentTransaction::create([
            'agent_id' => null,
            'user_id' => $user->id,
            'type' => 'cash_out',
            'amount' => $request->amount,
            'currency' => $request->currency,
            'reference_code' => $referenceCode,
            'status' => 'pending',
        ]);

        return $this->success([
            'reference_code' => $referenceCode,
            'expires_in' => 3600,
        ], 'رمز السحب جاهز. قدمه لأقرب وكيل Beza.');
    }
}
```

## 5.4 الخدمات (Services)

### 5.4.1 WalletService

```php
<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\InsufficientBalanceException;

class WalletService
{
    public function getBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    public function debit(Wallet $wallet, float $amount, string $reference, string $description = null): Transaction
    {
        if ($wallet->balance < $amount) {
            throw new InsufficientBalanceException();
        }

        $wallet->decrement('balance', $amount);

        return Transaction::create([
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => null,
            'amount' => $amount,
            'amount_in_usd' => $this->convertToUsd($wallet->currency, $amount),
            'type' => 'withdraw',
            'status' => 'completed',
            'reference_number' => $reference,
            'description' => $description,
            'completed_at' => now(),
        ]);
    }

    public function credit(Wallet $wallet, float $amount, string $reference, string $description = null): Transaction
    {
        $wallet->increment('balance', $amount);

        return Transaction::create([
            'from_wallet_id' => null,
            'to_wallet_id' => $wallet->id,
            'amount' => $amount,
            'amount_in_usd' => $this->convertToUsd($wallet->currency, $amount),
            'type' => 'deposit',
            'status' => 'completed',
            'reference_number' => $reference,
            'description' => $description,
            'completed_at' => now(),
        ]);
    }

    public function convertToUsd(string $currency, float $amount): float
    {
        if ($currency === 'USD') {
            return $amount;
        }
        $rate = Cache::get('exchange_rate_syp_usd', 13000);
        return $amount / $rate;
    }

    public function convertFromUsd(string $currency, float $usdAmount): float
    {
        if ($currency === 'USD') {
            return $usdAmount;
        }
        $rate = Cache::get('exchange_rate_syp_usd', 13000);
        return $usdAmount * $rate;
    }
}
```

## 5.5 الأحداث والمستمعين (Events & Listeners)

```php
// حدث TransactionCompleted
class TransactionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public $fromUser;
    public $toUser;

    public function __construct(Transaction $transaction, User $fromUser = null, User $toUser = null)
    {
        $this->transaction = $transaction;
        $this->fromUser = $fromUser;
        $this->toUser = $toUser;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . ($this->toUser?->id ?? $this->fromUser?->id));
    }
}

// مستمع لإرسال الإشعارات
class SendTransactionNotification
{
    public function handle(TransactionCompleted $event)
    {
        if ($event->toUser && $event->toUser->fcm_token) {
            NotificationService::sendPush(
                $event->toUser->fcm_token,
                'استلام تحويل',
                "لقد استلمت {$event->transaction->amount} {$event->transaction->currency}"
            );
        }

        if ($event->fromUser && $event->fromUser->fcm_token) {
            NotificationService::sendPush(
                $event->fromUser->fcm_token,
                'تم التحويل',
                "تم تحويل {$event->transaction->amount} {$event->transaction->currency}"
            );
        }
    }
}
```

## 5.6 المصادقة عبر JWT (JSON Web Token)

تم استبدال نظام Sanctum بـ **JWT** باستخدام الحزمة `tymon/jwt-auth` لتوفير مصادقة عديمة الحالة (stateless) مناسبة لـ REST API.

### 5.6.1 الحزمة والإعدادات

```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 5.6.2 تهيئة الحارس (Guard)

في `config/auth.php`:

```php
'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

يُستخدم الحارس `jwt` للتحقق من التوكنات. يتم تعريف middleware بـ `auth:api` في ملفات المسارات.

### 5.6.3 هيكل التوكن

عند تسجيل الدخول أو التسجيل، يُعاد كائن JSON بالشكل التالي:

```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1Qi...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

- **access_token**: رمز JWT مشفر ذاتياً (self-encoded) يحتوي على claims (sub, iat, exp, ...)
- **token_type**: ثابتاً بقيمة `bearer`
- **expires_in**: عمر التوكن بالثواني (قابل للتعديل عبر `config/jwt.php` → `ttl`)

### 5.6.4 آلية العمل

1. **Stateless**: لا يتم تخزين التوكنات في قاعدة البيانات، بل تُحمل جميع معلومات المصادقة داخل التوكن نفسه (sub: user_id, iat: وقت الإصدار, exp: وقت الانتهاء).
2. **إصدار التوكن**: يتم عبر `JWTAuth::fromUser($user)` أو `JWTAuth::attempt($credentials)`.
3. **التحقق**: Laravel يتحقق من توقيع التوكن وانتهاء صلاحيته تلقائياً عند استخدام middleware `auth:api`.
4. **إضافة Claims مخصصة**: يمكن إضافة صلاحيات إضافية مثل `role`:

```php
$token = JWTAuth::claims(['role' => 'admin'])->fromUser($user);
```

### 5.6.5 آلية تجديد التوكن (Refresh Token)

```php
// تجديد التوكن قبل انتهاء صلاحيته
try {
    $newToken = JWTAuth::parseToken()->refresh();
} catch (TokenExpiredException $e) {
    // التوكن منتهي ولا يمكن تجديده — يجب إعادة تسجيل الدخول
    return response()->json(['error' => 'token_expired'], 401);
}
```

- يتم استخدام `refresh()` للحصول على توكن جديد دون إعادة إدخال بيانات الدخول.
- يمكن ضبط `refresh_ttl` في `config/jwt.php` للتحكم بالمدة المسموح فيها بتجديد التوكن بعد انتهائه.

### 5.6.6 إبطال التوكن (Logout / Blacklist)

عند تسجيل الخروج:

```php
JWTAuth::parseToken()->invalidate();
```

- يتم إضافة التوكن إلى القائمة السوداء (blacklist) داخل Laravel cache.
- التوكن المُبطل لا يمكن استخدامه مجدداً حتى لو كان توقيعه صحيحاً.
- تعتمد المدة القصوى للقائمة السوداء على `blacklist_grace_period` في `config/jwt.php`.

### 5.6.7 معالجة الأخطاء

```php
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

// في App\Exceptions\Handler
public function render($request, Throwable $exception)
{
    if ($exception instanceof TokenExpiredException) {
        return response()->json(['error' => 'token_expired'], 401);
    }
    if ($exception instanceof TokenInvalidException) {
        return response()->json(['error' => 'token_invalid'], 401);
    }
    if ($exception instanceof JWTException) {
        return response()->json(['error' => 'token_absent'], 401);
    }
    return parent::render($request, $exception);
}
```

### 5.6.8 استخدام middleware في ملفات المسارات

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('transfer', [TransferController::class, 'transfer']);
});

// routes/admin.php
Route::middleware(['auth:api', 'verified'])->prefix('admin')->group(function () {
    Route::get('users', [AdminController::class, 'listUsers']);
});

// routes/webhook.php (دون مصادقة — تستخدم API Key بدلاً من JWT)
Route::post('stripe/webhook', [WebhookController::class, 'handleStripe']);
```

**ملاحظة:** يتم تعيين middleware `auth:api` في `app/Http/Kernel.php` ضمن `$routeMiddleware`، ويستخدم الحارس `jwt` للتحقق من صحة التوكن.
```
