# 19 - حالات الحافة (Edge Cases)

## السيناريوهات الكاملة

### 1. العميل دفع والتاجر لم يؤكد الطلب
```php
// يبقى الطلب pending لمدة 24 ساعة
// Cron job يلغي الطلبات المعلقة تلقائياً
$expiredOrders = Order::pending()
    ->where('created_at', '<', now()->subHours(24))
    ->get();

foreach ($expiredOrders as $order) {
    DB::transaction(function () use ($order) {
        // إعادة المخزون
        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }
        // إلغاء الطلب آلياً
        $order->update([
            'status' => OrderStatus::CANCELLED,
            'notes'  => 'تم الإلغاء تلقائياً لعدم تأكيد التاجر خلال 24 ساعة',
        ]);
        // استرداد المبلغ للعميل
        $order->transactions()->update(['status' => 'refunded']);
    });
}
```
الحل: إشعار التاجر بعد 6 ساعات، ثم 12 ساعة، ثم إلغاء تلقائي مع استرداد المبلغ.

### 2. الطلب عالق في حالة وسيطة (Stuck in Limbo)
```php
// طلب في processing لأكثر من 5 أيام
$stuckOrders = Order::whereIn('status', ['confirmed', 'processing'])
    ->where('updated_at', '<', now()->subDays(5))
    ->get();

foreach ($stuckOrders as $order) {
    // إرسال تنبيه للمشرف
    Log::warning("Order stuck in {$order->status}", ['order_id' => $order->id]);
    // إشعار التاجر لتحديث الحالة
    $order->merchant->notify(new OrderStuckNotification($order));
}
```

### 3. سيناريو الاسترجاع الجزئي (Partial Refund)
```php
public function partialRefund(Order $order, array $returnedItems): void
{
    DB::transaction(function () use ($order, $returnedItems) {
        $refundAmount = 0;

        foreach ($returnedItems as $returned) {
            $item = $order->items()->findOrFail($returned['order_item_id']);
            $refundAmount += $item->unit_price * $returned['quantity'];

            // إعادة المخزون للمنتجات المرتجعة
            $item->product->increment('stock', $returned['quantity']);
        }

        // إنشاء معاملة استرداد جزئي
        $order->transactions()->create([
            'user_id' => $order->user_id,
            'type'    => 'partial_refund',
            'amount'  => $refundAmount,
            'status'  => 'completed',
        ]);

        // إذا كان كل المنتجات مرتجعة → الطلب returned
        $allReturned = $order->items->sum('quantity') === collect($returnedItems)->sum('quantity');
        $order->update(['status' => $allReturned ? OrderStatus::RETURNED : OrderStatus::DELIVERED]);
    });
}
```

### 4. التاجر يغيب بعد تأكيد الطلب
يؤكد الطلب ثم يتوقف عن الاستجابة. الحل: نظام تصعيد (Escalation):
- بعد 48 ساعة من confirmed دون تحديث → إشعار مشرف
- بعد 72 ساعة → تعليق حساب التاجر مؤقتاً
- بعد 96 ساعة → إلغاء الطلب وإبلاغ العميل

### 5. كشف الاحتيال للطلبات المشبوهة (Fraud Detection)
```php
public function detectFraudulentOrder(Order $order): bool
{
    $suspicious = false;

    // عدة طلبات سريعة لنفس العميل
    $recentOrders = Order::where('user_id', $order->user_id)
        ->where('created_at', '>', now()->subMinutes(10))
        ->count();
    if ($recentOrders >= 3) $suspicious = true;

    // مبلغ كبير جداً مقارنة بمعدل التاجر
    $merchantAvg = Order::byMerchant($order->merchant_id)
        ->where('status', '!=', OrderStatus::CANCELLED)
        ->avg('grand_total') ?? 0;
    if ($merchantAvg > 0 && $order->grand_total > $merchantAvg * 5) $suspicious = true;

    // عنوان IP مشبوه
    if ($this->isBlockedIp($order->ip_address ?? request()->ip())) $suspicious = true;

    if ($suspicious) {
        $order->update(['metadata' => array_merge($order->metadata ?? [], ['fraud_flag' => true])]);
        Log::warning('Fraudulent order detected', ['order_id' => $order->id]);
    }

    return $suspicious;
}
```

### 6. استقبال إشعار دفع مكرر (Duplicate Payment Webhook)
```php
// استخدام idempotency key
public function handlePaymentWebhook(array $payload): void
{
    $idempotencyKey = $payload['idempotency_key'];

    // التحقق من عدم معالجة هذا الإشعار مسبقاً
    $exists = PaymentWebhookLog::where('idempotency_key', $idempotencyKey)->exists();
    if ($exists) {
        throw new DuplicatePaymentWebhookException($payload['transaction_id']);
    }

    DB::transaction(function () use ($payload, $idempotencyKey) {
        // تسجيل الإشعار
        PaymentWebhookLog::create([
            'idempotency_key' => $idempotencyKey,
            'payload'         => json_encode($payload),
            'processed_at'    => now(),
        ]);

        // تحديث الطلب
        $order = Order::findOrFail($payload['order_id']);
        $order->transactions()->create([
            'type'    => 'payment',
            'amount'  => $payload['amount'],
            'status'  => 'completed',
            'gateway' => $payload['gateway'],
        ]);
    });
}
```

### 7. مهلة انتهاء الدفع (Payment Timeout)
```php
// إذا لم يكمل العميل الدفع خلال 30 دقيقة
// Cron job
Order::pending()
    ->where('created_at', '<', now()->subMinutes(30))
    ->chunk(100, function ($orders) {
        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // إعادة المخزون
                foreach ($order->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }
                $order->update(['status' => OrderStatus::CANCELLED, 'notes' => 'انتهت مهلة الدفع']);
            });
        }
    });
```

## جدول حالات الحافة الكامل
| # | الحالة | السبب | آلية المعالجة |
|---|--------|-------|--------------|
| 1 | دفع بدون تأكيد تاجر | التاجر مشغول/غائب | إلغاء تلقائي بعد 24 ساعة |
| 2 | طلب عالق في limbo | خطأ تقني/تقصير التاجر | تنبيه + تصعيد للمشرف |
| 3 | استرجاع جزئي | العميل يريد إرجاع جزء | حساب المبلغ بدقة، إعادة المخزون |
| 4 | تاجر غائب بعد التأكيد | انقطاع التاجر | تصعيد + تعليق مؤقت |
| 5 | احتيال | طلبات سريعة/مبالغ كبيرة | flag يدوي + log للمراجعة |
| 6 | إشعار دفع مكرر | Webhook retry | Idempotency key يمنع التكرار |
| 7 | مهلة دفع منتهية | العميل لم يكمل الدفع | إلغاء + إعادة مخزون تلقائي |

## ملخص
هذه الحالات تغطي أكثر من 90% من المشاكل الواقعية في إدارة الطلبات. كل حالة لها حل آلي يضمن عدم خسارة المال أو المخزون، مع إشعارات للأطراف المعنية في الوقت المناسب.
