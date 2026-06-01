# 06 - نماذج Eloquent (Eloquent Models)

## Order Model
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'merchant_id', 'user_id', 'order_number', 'status',
        'total_amount', 'shipping_fee', 'tax_amount', 'grand_total',
        'currency', 'shipping_address_id', 'billing_address_id',
        'notes', 'metadata', 'confirmed_at', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'shipping_fee'  => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'grand_total'   => 'decimal:2',
        'metadata'      => 'json',
        'confirmed_at'  => 'datetime',
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    // العلاقات (Relations)
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function items(): HasMany     { return $this->hasMany(OrderItem::class); }
    public function transactions(): MorphMany { return $this->morphMany(Transaction::class, 'transactable'); }
    public function shippingAddress(): BelongsTo { return $this->belongsTo(Address::class, 'shipping_address_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(OrderStatusHistory::class); }

    // سكوبات (Scopes)
    public function scopePending($q)     { return $q->where('status', OrderStatus::PENDING); }
    public function scopeConfirmed($q)   { return $q->where('status', OrderStatus::CONFIRMED); }
    public function scopeShipped($q)     { return $q->where('status', OrderStatus::SHIPPED); }
    public function scopeDelivered($q)   { return $q->where('status', OrderStatus::DELIVERED); }
    public function scopeCancelled($q)   { return $q->where('status', OrderStatus::CANCELLED); }
    public function scopeByMerchant($q, int $merchantId) { return $q->where('merchant_id', $merchantId); }
    public function scopeByDateRange($q, $from, $to) { return $q->whereBetween('created_at', [$from, $to]); }
    public function scopeToday($q)       { return $q->whereDate('created_at', today()); }
    public function scopeThisMonth($q)   { return $q->whereMonth('created_at', now()->month); }

    // توابع مساعدة (Helpers)
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED]);
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, OrderStatus::TRANSITIONS[$this->status] ?? []);
    }
}
```

## OrderItem Model
```php
class OrderItem extends Model
{
    protected $table = 'order_items';
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'subtotal', 'metadata',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'metadata'   => 'json',
    ];

    public function order(): BelongsTo    { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo  { return $this->belongsTo(MerchantProduct::class, 'product_id'); }
}
```

## OrderStatusHistory Model
```php
class OrderStatusHistory extends Model
{
    protected $table = 'order_status_histories';

    protected $fillable = ['order_id', 'from_status', 'to_status', 'changed_by', 'notes'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
```

## Enum الحالات (Order Status Enum)
```php
<?php
namespace App\Enums;

enum OrderStatus: string
{
    case PENDING    = 'pending';
    case CONFIRMED  = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED    = 'shipped';
    case DELIVERED  = 'delivered';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';
    case RETURNED   = 'returned';
    case REFUNDED   = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING    => 'معلق',
            self::CONFIRMED  => 'مؤكد',
            self::PROCESSING => 'قيد التجهيز',
            self::SHIPPED    => 'تم الشحن',
            self::DELIVERED  => 'تم التوصيل',
            self::COMPLETED  => 'مكتمل',
            self::CANCELLED  => 'ملغي',
            self::RETURNED   => 'مرتجع',
            self::REFUNDED   => 'مسترجع',
        };
    }

    public const array TRANSITIONS = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'cancelled'],
        'processing' => ['shipped'],
        'shipped'    => ['delivered'],
        'delivered'  => ['completed', 'returned'],
        'completed'  => [],
        'cancelled'  => [],
        'returned'   => ['refunded'],
        'refunded'   => [],
    ];
}
```

## أمثلة استعلامات (Example Queries)
```php
// طلبات اليوم لتاجر معين
$todayOrders = Order::byMerchant($merchantId)->today()->get();

// طلبات pending تحتاج تأكيد
$pendingOrders = Order::pending()->byMerchant($merchantId)->get();

// طلبات الشهر الحالي مع العناصر
$monthlyOrders = Order::thisMonth()->with('items.product')->get();

// إحصائيات الحالات
$stats = Order::byMerchant($merchantId)
    ->selectRaw('status, COUNT(*) as count, SUM(grand_total) as revenue')
    ->groupBy('status')
    ->get();
```

## مخطط العلاقات (Relations Diagram)
```
Merchant (1) ──→ (*) Order (*) ←── (1) User
                         │
                         ├── (*) OrderItem (*) ←── (1) MerchantProduct
                         │
                         └── (*) Transaction (polymorphic)
```
