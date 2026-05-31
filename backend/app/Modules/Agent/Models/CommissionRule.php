<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CommissionRule extends Model
{
    protected $table = 'commission_rules';

    protected $fillable = [
        'name', 'name_ar', 'txn_type', 'calc_type', 'value',
        'cap_amount', 'min_amount', 'kyc_tier_min', 'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'cap_amount' => 'integer',
        'min_amount' => 'integer',
        'is_active' => 'boolean',
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

    public function calculate(Money $amount): Money
    {
        $fee = match ($this->calc_type) {
            'flat' => Money::fromInt($this->value, $amount->currency()),
            'percentage' => $amount->percentage($this->value),
            'mixed' => Money::fromInt($this->value, $amount->currency())->add($amount->percentage($this->value)),
            default => Money::zero($amount->currency()),
        };

        if ($this->cap_amount !== null && $fee->amount() > $this->cap_amount) {
            $fee = Money::fromInt($this->cap_amount, $fee->currency());
        }

        if ($this->min_amount !== null && $fee->amount() < $this->min_amount) {
            $fee = Money::fromInt($this->min_amount, $fee->currency());
        }

        return $fee;
    }
}
