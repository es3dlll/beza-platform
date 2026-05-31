# 06 - نماذج Eloquent (Eloquent Models)

## User Model (relevant portion)

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}
```

## Card Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'status', 'last_four', 'expiry_date',
        'daily_limit', 'currency', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'json',
        'expiry_date' => 'date',
    ];

    protected $hidden = [
        'pan', 'cvv', 'pin_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

## Model Relationships
```
┌─────────────┐      ┌──────────────────┐
│    User     │      │      Card        │
├─────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │
│ name        │      │ id               │
│ phone       │      │ type             │
└─────────────┘      │ status           │
                     └──────────────────┘
```
