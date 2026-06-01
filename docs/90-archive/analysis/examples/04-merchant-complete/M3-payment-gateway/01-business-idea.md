# 01 - فكرة المشروع (Business Idea) - بوابة الدفع (Payment Gateway)

## الفكرة الأساسية
تاجر يريد إرسال رابط دفع لعميل عبر واتساب. العميل يضغط الرابط ويدفع مباشرة. هذا الحل يسمح للتجار بقبول المدفوعات الرقمية دون الحاجة لموقع إلكتروني متكامل، فقط عبر مشاركة رابط.

## سيناريو المستخدم
```
بصفتي: تاجر
أريد: إنشاء رابط دفع وإرساله للعميل
لكي: يتمكن العميل من الدفع إلكترونياً
```

## قبول السيناريو (Acceptance Criteria)
| # | الشرط | الوصف |
|---|--------|-------|
| 1 | إنشاء رابط دفع بمبلغ محدد (SYP/USD) | المبلغ والعملة يحددان وقت الإنشاء |
| 2 | إضافة وصف ورابط إعادة توجيه | وصف الدفع ورابط العودة بعد الدفع |
| 3 | صلاحية الرابط (expiry_hours) | مدة صلاحية الرابط بالساعات |
| 4 | تجميد المبلغ في محفظة التاجر | ضمان وجود رصيد كافٍ |
| 5 | Webhook لإشعار التاجر بالدفع | إشعار فوري عند إتمام الدفع |

## تفاصيل العملية
بوابة الدفع هي عملية حرجة (P0) تتيح إنشاء روابط دفع آمنة. كل رابط يتم إنشاؤه مع تجميد المبلغ في محفظة التاجر لضمان عدم إنشاء روابط بدون رصيد. الرابط له صلاحية محددة وبعد انتهائها يعاد المبلغ للتاجر تلقائياً.

## مثال على إنشاء رابط دفع (Create Payment Link)
```php
<?php
namespace App\Services\Merchant;

class PaymentLinkService
{
    public function create(
        Merchant $merchant,
        float $amount,
        string $currency,
        ?string $description = null,
        ?string $redirectUrl = null,
        int $expiryHours = 24
    ): PaymentLink {
        return DB::transaction(function () use ($merchant, $amount, $currency, $description, $redirectUrl, $expiryHours) {
            $wallet = MerchantWallet::where('merchant_id', $merchant->id)
                ->where('currency', $currency)
                ->where('is_active', true)
                ->firstOrFail();

            // تجميد المبلغ لضمان عدم إنشاء رابط بدون رصيد
            $wallet->decrement('balance', $amount);
            $wallet->increment('frozen_balance', $amount);

            return PaymentLink::create([
                'merchant_id' => $merchant->id,
                'token' => bin2hex(random_bytes(32)),
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'redirect_url' => $redirectUrl,
                'status' => 'active',
                'expires_at' => now()->addHours($expiryHours),
            ]);
        }, attempts: 3);
    }
}
```

## مثال على نموذج رابط الدفع (Payment Link Model)
```php
<?php
namespace App\Models;

class PaymentLink extends Model
{
    protected $fillable = [
        'merchant_id', 'token', 'amount', 'currency',
        'description', 'redirect_url', 'status',
        'expires_at', 'paid_at',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'used', 'paid_at' => now()]);
    }
}
```

هذا المثال يوضح الآلية الكاملة لإنشاء رابط دفع: التحقق من المحفظة، تجميد المبلغ، إنشاء الرابط مع توكن عشوائي آمن، وتحديد صلاحية زمنية للرابط.
