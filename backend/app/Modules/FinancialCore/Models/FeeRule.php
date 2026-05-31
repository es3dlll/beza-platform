<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Models;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FeeRule extends Model
{
    protected $fillable = ['name', 'name_ar', 'type', 'value', 'cap_amount', 'min_amount', 'account_code', 'metadata'];
    protected $casts = ['cap_amount' => 'integer', 'min_amount' => 'integer', 'value' => 'integer', 'metadata' => 'array'];
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

    public function calculateFee(Money $amount): Money
    {
        $fee = match ($this->type) {
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
