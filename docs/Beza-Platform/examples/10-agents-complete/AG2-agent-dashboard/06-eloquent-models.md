# 06 - الموديلز مع العلاقات (Eloquent Models)

## AgentStat Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentStat extends Model
{
    protected $fillable = [
        'agent_id', 'today_count', 'today_volume',
        'week_volume', 'month_volume', 'commission_total'
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
```

## Agent Model (Dashboard scope)

```php
public function stats(): HasOne
{
    return $this->hasOne(AgentStat::class);
}

public function scopeWithDashboard($query)
{
    return $query->with('stats');
}
```
