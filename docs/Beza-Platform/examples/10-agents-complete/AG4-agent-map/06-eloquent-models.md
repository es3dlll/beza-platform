# 06 - الموديلز مع العلاقات (Eloquent Models)

## AgentLocation Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentLocation extends Model
{
    protected $fillable = ['agent_id', 'latitude', 'longitude'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeNearby($query, $lat, $lng, $radiusKm = 5)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }
}
```

## Agent Model (Location relation)

```php
public function location(): HasOne
{
    return $this->hasOne(AgentLocation::class);
}

public function reviews(): HasMany
{
    return $this->hasMany(AgentReview::class);
}
```
