<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Models\User;
use Database\Factories\Modules\Wallet\Models\WalletFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['user_id', 'balance_fils', 'currency'];

    protected function casts(): array
    {
        return [
            'balance_fils' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
