<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Exceptions\InsufficientBalanceException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Wallet extends Model
{
    use HasFactory;

    protected static $factory = \App\Modules\Identity\Database\Factories\WalletFactory::class;

    protected $fillable = [
        'id',
        'user_id',
        'currency',
        'balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public $incrementing = false;

    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function credit(int $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function debit(int $amount): void
    {
        if ($this->balance < $amount) {
            throw new InsufficientBalanceException($this->id, $amount, $this->balance);
        }

        $this->decrement('balance', $amount);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function frozen(): void
    {
        $this->update(['status' => 'frozen']);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }
}
