# 06 - نماذج Eloquent (Eloquent Models)

## Card Model (Reports scope)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeWithReports($query, $from, $to)
    {
        return $query->with(['transactions' => fn($q) => $q->whereBetween('created_at', [$from, $to])]);
    }
}
```

## Transaction Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['card_id', 'amount', 'merchant', 'category', 'status'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function scopeByCategory($query)
    {
        return $query->selectRaw('category, SUM(amount) as total')
            ->groupBy('category');
    }
}
```
