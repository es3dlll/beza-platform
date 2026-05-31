# 06 - الموديلز (Eloquent Models)

## Setting Model

```php
<?php
// app/Models/Admin/Setting.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key', 'value', 'type', 'group', 'description', 'updated_by',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number'  => (float) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->typed_value;
    }

    public static function setValue(string $key, mixed $value, int $updatedBy = null): void
    {
        $type = match (true) {
            is_bool($value)   => 'boolean',
            is_numeric($value) => 'number',
            is_array($value)  => 'json',
            default           => 'string',
        };

        self::updateOrCreate(
            ['key' => $key],
            [
                'value'      => is_array($value) ? json_encode($value) : (string) $value,
                'type'       => $type,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
```
