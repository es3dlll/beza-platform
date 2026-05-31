<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class VelocityCounter extends Model
{
    protected $table = 'velocity_counters';

    protected $fillable = ['wallet_id', 'rule_id', 'window_key', 'count', 'window_start', 'window_end'];

    protected $casts = [
        'count' => 'integer',
        'window_start' => 'datetime',
        'window_end' => 'datetime',
    ];

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

    public function incrementCount(): void
    {
        $this->increment('count');
    }
}
