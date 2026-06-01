# 11 - الأحداث والمستمعون (Events & Listeners)

## الأحداث (Events)
```php
<?php
namespace AppEvents;
use AppModelsMerchantProduct;
use IlluminateFoundationEventsDispatchable;

class ProductCreated { use Dispatchable; public function __construct(public readonly MerchantProduct $product) {} }
class ProductUpdated { use Dispatchable; public function __construct(public readonly MerchantProduct $product) {} }
class ProductDeleted { use Dispatchable; public function __construct(public readonly int $productId, public readonly int $merchantId) {} }
class ProductImageAdded { use Dispatchable; public function __construct(public readonly MerchantProduct $product, public readonly string $imagePath) {} }
class ProductStockLow { use Dispatchable; public function __construct(public readonly MerchantProduct $product, public readonly int $remainingStock) {} }
```

## المستمعون (Listeners)
```php
<?php
namespace AppListeners;
use AppEventsProductCreated;
use AppNotificationsProductAdded;

class SendProductCreatedNotification {
    public function handle(ProductCreated $event): void {
        $merchant = $event->product->merchant;
        $merchant->user->notify(new ProductAdded($event->product));
    }
}

class LogProductActivity {
    public function handle($event): void {
        IlluminateSupportFacadesLog::info('Product activity', [
            'event' => class_basename($event),
            'product_id' => $event->product?->id ?? $event->productId,
            'merchant_id' => $event->merchantId ?? $event->product?->merchant_id,
        ]);
    }
}
```

## EventServiceProvider
```php
protected $listen = [
    ProductCreated::class => [SendProductCreatedNotification::class, LogProductActivity::class],
    ProductUpdated::class => [LogProductActivity::class],
    ProductDeleted::class => [LogProductActivity::class],
    ProductStockLow::class => [SendLowStockNotification::class],
];
```

شرح: كل حدث يسمح بفصل المنطق ومعالجة الإشعارات والتسجيل بشكل غير متزامن.
