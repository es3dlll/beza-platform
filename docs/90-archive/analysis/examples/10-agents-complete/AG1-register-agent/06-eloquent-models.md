# 06 - الموديلز مع العلاقات (Eloquent Models)

## AgentRequest Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'status', 'documents', 'admin_notes'
    ];

    protected $casts = [
        'documents' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
```

## Agent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = ['user_id', 'business_name', 'commission_rate', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```
