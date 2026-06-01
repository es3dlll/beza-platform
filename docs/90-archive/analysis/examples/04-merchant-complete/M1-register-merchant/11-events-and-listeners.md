# 11 - الأحداث والمستمعون (Events & Listeners)

## MerchantRegistered Event
```php
<?php
namespace AppEvents;
use AppModelsMerchant;
use AppModelsUser;
use IlluminateFoundationEventsDispatchable;
class MerchantRegistered { use Dispatchable; public function __construct(public readonly Merchant $merchant, public readonly User $user) {} }
```

## MerchantApproved Event
```php
<?php
namespace AppEvents;
use AppModelsMerchant;
use IlluminateFoundationEventsDispatchable;
class MerchantApproved { use Dispatchable; public function __construct(public readonly Merchant $merchant) {} }
```

## EventServiceProvider
```php
protected $listen = [
    MerchantRegistered::class => [NotifyAdminNewMerchant::class],
    MerchantApproved::class => [CreateMerchantWallets::class, SendMerchantApprovalNotification::class],
];
```

## المستمعون (Listeners)
- **NotifyAdminNewMerchant**: يرسل إشعار للمشرف بوجود طلب تاجر جديد
- **CreateMerchantWallets**: ينشئ محفظتي SYP و USD للتاجر بعد الموافقة
- **SendMerchantApprovalNotification**: يرسل إشعاراً للتاجر بأن حسابه نشط
