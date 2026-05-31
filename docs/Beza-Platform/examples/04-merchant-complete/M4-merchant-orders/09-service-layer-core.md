# 09 - OrderService كامل

```php
<?php
namespace App\Services\Merchant;
use App\Events\OrderStatusUpdated;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Models\MerchantOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const VALID_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'cancelled'],
        'delivered'  => [],
        'cancelled'  => [],
    ];

    public function updateStatus(int $merchantId, int $orderId, string $newStatus, ?string $notes = null): MerchantOrder
    {
        $order = MerchantOrder::where('merchant_id', $merchantId)->findOrFail($orderId);

        if (!in_array($newStatus, self::VALID_TRANSITIONS[$order->status] ?? [])) {
            throw new InvalidOrderStatusTransitionException($order->status, $newStatus);
        }

        DB::transaction(function () use ($order, $newStatus, $notes) {
            $order->update(['status' => $newStatus, 'notes' => $notes ? $notes : $order->notes]);
        });

        try { OrderStatusUpdated::dispatch($order); }
        catch (\Throwable $e) { Log::warning('فشل إرسال حدث تحديث الطلب', ['order_id' => $order->id]); }

        return $order->fresh();
    }
}
```
