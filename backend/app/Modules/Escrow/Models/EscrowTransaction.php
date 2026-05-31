<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Models;

use App\Modules\Escrow\Database\Factories\EscrowTransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class EscrowTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'escrow_transactions';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'marketplace_ref_id',
        'amount_fils',
        'fee_fils',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount_fils' => 'integer',
        'fee_fils' => 'integer',
        'metadata' => 'array',
    ];

    public function dispute(): HasOne
    {
        return $this->hasOne(DisputeCase::class, 'escrow_transaction_id');
    }

    public function isFunded(): bool { return $this->status === 'funded'; }
    public function isReleased(): bool { return $this->status === 'released'; }
    public function isDisputed(): bool { return $this->status === 'disputed'; }
    public function isRefunded(): bool { return $this->status === 'refunded'; }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['funded', 'shipped', 'delivered', 'disputed']);
    }
}
