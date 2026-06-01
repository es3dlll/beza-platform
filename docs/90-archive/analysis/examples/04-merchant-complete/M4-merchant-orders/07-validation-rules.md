# 07 - قواعد التحقق (Validation Rules)

## إنشاء طلب جديد (Create Order Request)
```php
<?php
namespace App\Http\Requests\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'merchant_id'           => ['required', 'exists:merchants,id'],
            'items'                 => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id'    => ['required', 'exists:merchant_products,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0.01'],
            'shipping_address_id'   => ['required', 'exists:addresses,id'],
            'billing_address_id'    => ['sometimes', 'exists:addresses,id'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'coupon_code'           => ['nullable', 'string', 'max:50', 'exists:coupons,code'],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_id.required'        => 'التاجر مطلوب',
            'merchant_id.exists'          => 'التاجر غير موجود',
            'items.required'              => 'يجب إضافة منتج واحد على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists'   => 'المنتج غير موجود',
            'items.*.quantity.min'        => 'الكمية يجب أن تكون 1 على الأقل',
            'items.*.unit_price.min'      => 'السعر يجب أن يكون أكبر من صفر',
        ];
    }
}
```

## تحديث حالة الطلب (Update Order Status Request)
```php
class UpdateOrderStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OrderStatus::cases())],
            'notes'  => ['nullable', 'string', 'max:500'],
            'tracking_number' => [
                Rule::requiredIf($this->status === OrderStatus::SHIPPED->value),
                'nullable', 'string', 'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'        => 'الحالة مطلوبة',
            'status.in'              => 'حالة غير صالحة',
            'tracking_number.required_if' => 'رقم التتبع مطلوب عند الشحن',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && !$order->canTransitionTo($this->status)) {
                $validator->errors()->add('status',
                    "لا يمكن تغيير الحالة من {$order->status} إلى {$this->status}");
            }
        });
    }
}
```

## إلغاء طلب (Cancel Order Request)
```php
class CancelOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'notify_customer' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يرجى ذكر سبب الإلغاء',
            'reason.min'      => 'سبب الإلغاء يجب أن يكون 10 أحرف على الأقل',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && !in_array($order->status, ['pending', 'confirmed'])) {
                $validator->errors()->add('status',
                    'يمكن إلغاء الطلبات المعلقة أو المؤكدة فقط');
            }
        });
    }
}
```

## طلب إرجاع (Return/Refund Request)
```php
class ReturnOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items'               => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'exists:order_items,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'items.*.reason'      => ['required', 'string', 'max:500'],
            'return_type'         => ['required', Rule::in(['full', 'partial'])],
            'images'              => ['nullable', 'array', 'max:5'],
            'images.*'            => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_type.required' => 'نوع الإرجاع مطلوب (كلي أو جزئي)',
            'items.*.reason.required' => 'سبب الإرجاع مطلوب لكل منتج',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && $order->status !== 'delivered') {
                $validator->errors()->add('status',
                    'يمكن إرجاع الطلبات التي تم توصيلها فقط');
            }
            if ($order && $order->delivered_at && $order->delivered_at->diffInDays(now()) > 14) {
                $validator->errors()->add('status',
                    'لا يمكن إرجاع الطلب بعد مرور 14 يوماً على التوصيل');
            }
        });
    }
}
```

## قواعد التحقق من الأسعار والكميات (Server-side)
```php
public function validateItemPrices(array $items, Merchant $merchant): void
{
    foreach ($items as $item) {
        $product = MerchantProduct::findOrFail($item['product_id']);

        // التأكد من أن المنتج يتبع نفس التاجر
        if ($product->merchant_id !== $merchant->id) {
            throw new ProductNotBelongsToMerchantException('المنتج لا يتبع هذا التاجر');
        }

        // التأكد من أن السعر لم يتغير بشكل غير طبيعي
        if (abs((float)$item['unit_price'] - (float)$product->price_syp) > 0.01) {
            throw new PriceMismatchException('سعر المنتج لا يتطابق مع السعر الحالي');
        }

        // التأكد من توفر المخزون
        if (!$product->hasStockFor($item['quantity'])) {
            throw new InsufficientStockException("المنتج {$product->name} غير متوفر بالكمية المطلوبة");
        }
    }
}
```

## جدول ملخص قواعد التحقق
| الحقل | القاعدة | رسالة الخطأ |
|-------|---------|------------|
| merchant_id | required, exists | التاجر مطلوب/غير موجود |
| items | required, array, min:1 | يجب إضافة منتج واحد على الأقل |
| items.*.quantity | integer, min:1 | الكمية يجب أن تكون 1 على الأقل |
| items.*.unit_price | numeric, min:0.01 | السعر يجب أن يكون أكبر من صفر |
| status | required, in enum | حالة غير صالحة |
| reason (cancel) | required, min:10 | يرجى ذكر سبب الإلغاء |
| return_type | required, in:full,partial | نوع الإرجاع مطلوب |
| images.* | image, max:2048 | الصورة كبيرة جداً |
