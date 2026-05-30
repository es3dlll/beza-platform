<?php

declare(strict_types=1);

namespace Modules\Takaful\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class TakafulProduct extends Model
{
    protected $table = 'takaful_products';

    protected $fillable = [
        'id', 'name', 'name_ar', 'type', 'description', 'description_ar',
        'min_premium', 'max_premium', 'coverage_amount', 'waiting_days',
        'is_active', 'metadata',
    ];

    protected $casts = [
        'min_premium' => 'integer',
        'max_premium' => 'integer',
        'coverage_amount' => 'integer',
        'waiting_days' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $product) {
            if (empty($product->id)) {
                $product->id = (string) Str::ulid();
            }
        });
    }
}
