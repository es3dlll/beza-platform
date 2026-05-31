<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Models;

use App\Modules\Remittance\Database\Factories\RemittanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Remittance extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'remittances';

    protected $fillable = [
        'sender_user_id',
        'receiver_name',
        'receiver_phone',
        'from_currency',
        'to_currency',
        'from_amount_fils',
        'to_amount_fils',
        'exchange_rate_id',
        'rate_used_fils_per_unit',
        'fee_fils',
        'total_charged_fils',
        'status',
        'risk_score_id',
        'reference_number',
        'metadata',
    ];

    protected $casts = [
        'from_amount_fils' => 'integer',
        'to_amount_fils' => 'integer',
        'rate_used_fils_per_unit' => 'integer',
        'fee_fils' => 'integer',
        'total_charged_fils' => 'integer',
        'metadata' => 'array',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
