<?php

namespace App\Models;

use App\Core\Enums\WalletStatus;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'blocked',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'blocked' => 'decimal:4',
            'status' => WalletStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
