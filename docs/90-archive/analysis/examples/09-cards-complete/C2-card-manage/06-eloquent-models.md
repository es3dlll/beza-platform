# 06 - نماذج Eloquent (Eloquent Models)

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
        'user_id', 'status', 'daily_limit', 'frozen_at'
    ];

    protected $casts = [
        'frozen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CardLog::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'frozen']);
    }
}
```

## CardLog Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardLog extends Model
{
    protected $fillable = ['card_id', 'action', 'old_status', 'new_status', 'changed_by'];
}
```

## Model Relationships
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│    User     │      │      Card        │      │    CardLog       │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │<──1──│ card_id          │
└─────────────┘      │ status           │      │ action           │
                     └──────────────────┘      └──────────────────┘
```
