<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class IdempotencyKey extends Model
{
    protected $fillable = ['key', 'transaction_id', 'response', 'expires_at'];
    protected $casts = ['response' => 'array', 'expires_at' => 'datetime'];
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
}
