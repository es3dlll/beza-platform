# 14 - المعاملات الذرية ACID (ACID Transactions)

## مفهوم ACID في نظام الطلبات
ACID يضمن سلامة بيانات الطلبات عند إنشائها أو تعديلها، خاصة في العمليات الحرجة مثل خصم المخزون وإنشاء الدفع.

## 1. إنشاء طلب ذري (Atomic Order Creation)
```php
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientStockException;

public function createOrder(array $data, User $customer): Order
{
    return DB::transaction(function () use ($data, $customer) {
        // 1. خصم المخزون لكل منتج
        foreach ($data['items'] as $item) {
            $product = MerchantProduct::where('id', $item['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$product->hasStockFor($item['quantity'])) {
                throw new InsufficientStockException(
                    $product->name,
                    $product->stock ?? 0,
                    $item['quantity']
                );
            }

            $product->decrementStock($item['quantity']);
        }

        // 2. إنشاء الطلب
        $order = Order::create([
            'merchant_id'        => $data['merchant_id'],
            'user_id'            => $customer->id,
            'order_number'       => Order::generateOrderNumber(),
            'status'             => OrderStatus::PENDING,
            'total_amount'       => $data['total_amount'],
            'shipping_fee'       => $data['shipping_fee'] ?? 0,
            'tax_amount'         => $data['tax_amount'] ?? 0,
            'grand_total'        => $data['grand_total'],
            'shipping_address_id' => $data['shipping_address_id'],
            'notes'              => $data['notes'] ?? null,
        ]);

        // 3. إنشاء عناصر الطلب
        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'sku'          => $item['sku'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'subtotal'     => $item['quantity'] * $item['unit_price'],
            ]);
        }

        // 4. إنشاء سجل الدفع (معلق)
        $order->transactions()->create([
            'user_id'    => $customer->id,
            'type'       => 'order_payment',
            'amount'     => $data['grand_total'],
            'currency'   => $data['currency'] ?? 'SYP',
            'status'     => 'pending',
        ]);

        // إذا فشلت أي خطوة → ROLLBACK تلقائي
        return $order;
    }, attempts: 3); // إعادة المحاولة 3 مرات عند deadlock
}
```

## 2. قفل تشاؤمي للمخزون (Pessimistic Locking)
```php
public function confirmOrder(Order $order): void
{
    DB::transaction(function () use ($order) {
        // قفل صفوف المخزون لمنع البيع المتزامن
        foreach ($order->items as $item) {
            $product = MerchantProduct::where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            // التحقق من المخزون مرة أخرى قبل التأكيد
            if ($product && !$product->hasStockFor($item->quantity)) {
                throw new InsufficientStockException(
                    $product->name,
                    $product->stock ?? 0,
                    $item->quantity
                );
            }
        }

        $order->update([
            'status'       => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        // تسجيل تاريخ الحالة
        $order->statusHistories()->create([
            'from_status' => OrderStatus::PENDING->value,
            'to_status'   => OrderStatus::CONFIRMED->value,
            'changed_by'  => auth()->id(),
        ]);
    });
}
```

## 3. معالجة حالات Race Condition
```php
// سيناريو: مستخدمان يحاولان شراء آخر قطعة في نفس الوقت
// بدون قفل: كلا المستخدمين يرى stock = 1 → كلاهما يشتري → overselling
// مع القفل: أول من يصل يحصل على القفل، الثاني ينتظر ثم يجد المخزون 0

// حماية إضافية: UPDATE شرطي
$updated = MerchantProduct::where('id', $productId)
    ->where('stock', '>=', $quantity)
    ->update(['stock' => DB::raw("stock - {$quantity}")]);

if ($updated === 0) {
    throw new InsufficientStockException();
}
```

## 4. التراجع عند فشل الدفع (Rollback on Payment Failure)
```php
public function processPayment(Order $order): void
{
    DB::transaction(function () use ($order) {
        try {
            $paymentResult = PaymentGateway::charge($order->grand_total);

            if (!$paymentResult->success) {
                throw new PaymentFailedException('فشلت عملية الدفع');
            }

            $order->transactions()->create([
                'type'            => 'payment',
                'amount'          => $order->grand_total,
                'status'          => 'completed',
                'gateway_response' => $paymentResult->toArray(),
            ]);

            $order->update(['status' => OrderStatus::CONFIRMED]);

        } catch (\Throwable $e) {
            // إعادة المخزون تلقائياً إذا فشل الدفع
            foreach ($order->items as $item) {
                MerchantProduct::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }
            throw $e; // Rollback تلقائي من DB::transaction
        }
    });
}
```

## 5. إعادة المخزون عند إلغاء الطلب
```php
public function cancelOrder(Order $order, string $reason): void
{
    DB::transaction(function () use ($order, $reason) {
        // إعادة المخزون للمنتجات
        foreach ($order->items as $item) {
            MerchantProduct::where('id', $item->product_id)
                ->increment('stock', $item->quantity);
        }

        // تحديث حالة الطلب
        $order->update([
            'status' => OrderStatus::CANCELLED,
            'notes'  => $reason,
        ]);

        // إلغاء المعاملة المالية إن وجدت
        $order->transactions()->where('status', 'pending')->update(['status' => 'cancelled']);
    });
}
```

## شرح ACID بالتفصيل
| الخاصية | الشرح | مثال في الطلبات |
|---------|-------|----------------|
| **Atomicity** | كل العمليات تنجح معاً أو تفشل معاً | خصم المخزون + إنشاء الطلب + إنشاء الدفع في transaction واحدة |
| **Consistency** | البيانات تبقى صالحة دائماً | UNIQUE order_number, CHECK stock >= 0, Foreign keys |
| **Isolation** | المعاملات لا تتداخل | lockForUpdate() يمنع بيع آخر قطعة لشخصين |
| **Durability** | بعد commit البيانات محفوظة | MySQL InnoDB يضمن بقاء البيانات حتى عند انقطاع الكهرباء |

## ملخص
استخدام DB::transaction مع lockForUpdate() يضمن أن عملية إنشاء الطلب آمنة تماماً من الناحية التزامنية. في حال فشل أي خطوة (مخزون، دفع، إشعار)، يتم التراجع عن كل شيء تلقائياً وإعادة المخزون إلى حالته الأصلية.
