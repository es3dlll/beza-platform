<?php

declare(strict_types=1);

namespace App\Modules\Fx\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RateSource extends Model
{
    protected $fillable = ['name', 'name_ar', 'type', 'is_active', 'priority'];
    protected $casts = ['is_active' => 'boolean', 'priority' => 'integer'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    public function rates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'rate_source_id');
    }
}
