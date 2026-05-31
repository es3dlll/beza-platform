<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Domain\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected static $factory = \App\Modules\Identity\Database\Factories\UserFactory::class;

    protected $fillable = [
        'id',
        'phone',
        'name',
        'name_ar',
        'email',
        'password',
        'pin_hash',
        'status',
        'kyc_tier',
        'device_id',
    ];

    protected $hidden = [
        'password',
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    public $incrementing = false;

    protected $keyType = 'string';

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function primaryWallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id')->where('currency', Currency::SYP->value);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canTransact(): bool
    {
        return $this->isActive() && ! in_array($this->status, ['suspended', 'locked'], true);
    }

    public function getTierLimit(): int
    {
        return match ($this->kyc_tier) {
            't0' => 0,
            't1' => 1000000,
            't2' => 10000000,
            't3' => 100000000,
            default => 0,
        };
    }
}
