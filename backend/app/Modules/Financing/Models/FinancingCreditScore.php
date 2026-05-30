<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

final class FinancingCreditScore extends Model
{
    protected $table = 'financing_credit_scores';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','score','transaction_volume','account_age_days','kyc_tier','factors','calculated_at'];
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) $model->id = (string) \Illuminate\Support\Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'transaction_volume' => 'integer',
            'account_age_days' => 'integer',
            'factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }
}
