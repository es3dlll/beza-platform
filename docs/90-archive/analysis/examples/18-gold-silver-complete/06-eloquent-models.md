# 06 - الموديلز مع العلاقات والأكسسورز (Eloquent Models)

## CommodityHolding

```php
<?php
// app/Models/CommodityHolding.php

namespace App\Models;

use App\Services\PriceFeedProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityHolding extends Model
{
    protected $table = 'commodity_holdings';

    protected $fillable = [
        'user_id',
        'commodity',
        'grams',
        'avg_price_usd',
        'total_invested_usd',
    ];

    protected $casts = [
        'grams'             => 'decimal:4',
        'avg_price_usd'     => 'decimal:2',
        'total_invested_usd'=> 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * القيمة السوقية الحالية بالدولار
     * current_value_usd = grams * current_price
     */
    public function getCurrentValueUsdAttribute(): float
    {
        $price = app(PriceFeedProvider::class)
            ->getPrice($this->commodity);

        return round($this->grams * $price['ask'], 2);
    }

    /**
     * الربح أو الخسارة
     * profit_loss = current_value - total_invested
     */
    public function getProfitLossAttribute(): float
    {
        return round($this->current_value_usd - $this->total_invested_usd, 2);
    }

    /**
     * نسبة الربح أو الخسارة
     */
    public function getProfitLossPercentAttribute(): float
    {
        if ($this->total_invested_usd <= 0) return 0;
        return round(
            ($this->profit_loss / $this->total_invested_usd) * 100,
            2
        );
    }
}
```

## CommodityTransaction

```php
<?php
// app/Models/CommodityTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommodityTransaction extends Model
{
    protected $table = 'commodity_transactions';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'commodity',
        'type',
        'grams',
        'price_usd',
        'total_usd',
        'fee',
        'reference_number',
        'status',
    ];

    protected $casts = [
        'grams'     => 'decimal:4',
        'price_usd' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'fee'       => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * توليد رقم مرجعي فريد
     * BZ + تاريخ + 6 محارف عشوائية
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'BZ' . now()->format('ymdHi');
        $random = strtoupper(Str::random(6));

        $ref = $prefix . $random;

        while (self::where('reference_number', $ref)->exists()) {
            $random = strtoupper(Str::random(6));
            $ref = $prefix . $random;
        }

        return $ref;
    }

    /**
     * نطاق (Scope) لمعاملات الشراء
     */
    public function scopeBuys($query)
    {
        return $query->where('type', 'buy');
    }

    /**
     * نطاق لمعاملات البيع
     */
    public function scopeSells($query)
    {
        return $query->where('type', 'sell');
    }

    /**
     * نطاق لمعاملات سلعة محددة
     */
    public function scopeOfCommodity($query, string $commodity)
    {
        return $query->where('commodity', $commodity);
    }
}
```

## CommodityPrice

```php
<?php
// app/Models/CommodityPrice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommodityPrice extends Model
{
    protected $table = 'commodity_prices';

    public $timestamps = false;

    protected $fillable = [
        'commodity',
        'price_usd',
        'price_syp',
        'bid_usd',
        'ask_usd',
        'source',
        'timestamp',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'price_syp' => 'decimal:2',
        'bid_usd'   => 'decimal:2',
        'ask_usd'   => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    /**
     * نطاق (Scope) لآخر سعر لكل سلعة
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc')->limit(1);
    }

    /**
     * نطاق لسعر الذهب فقط
     */
    public function scopeGold($query)
    {
        return $query->where('commodity', 'gold');
    }

    /**
     * نطاق لسعر الفضة فقط
     */
    public function scopeSilver($query)
    {
        return $query->where('commodity', 'silver');
    }
}
```

## CommodityOrder

```php
<?php
// app/Models/CommodityOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityOrder extends Model
{
    protected $table = 'commodity_orders';

    protected $fillable = [
        'user_id',
        'type',
        'commodity',
        'grams',
        'price_type',
        'limit_price',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'grams'       => 'decimal:4',
        'limit_price' => 'decimal:2',
        'expires_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * نطاق للأوامر المعلقة (pending)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * نطاق للأوامر المنتهية صلاحيتها
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'pending');
    }
}
```

## ملخص العلاقات والأكسسورز

| الموديل | العلاقات | الأكسسورز | النطاقات (Scopes) |
|---------|----------|-----------|-------------------|
| CommodityHolding | belongsTo(User) | current_value_usd, profit_loss, profit_loss_percent | — |
| CommodityTransaction | belongsTo(User) | — | buys, sells, ofCommodity |
| CommodityPrice | — | — | latest, gold, silver |
| CommodityOrder | belongsTo(User) | — | pending, expired |
