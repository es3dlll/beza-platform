<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'kyc_level', 'status', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'kyc_level' => 'integer',
        ];
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'admin' => [
                'manage_wap',
                'manage_users',
                'manage_wallets',
                'view_reports',
                'agents:view',
                'agents:commissions',
                'commissions:approve',
                'agents:finance',
                'finance:approve',
                'security:view',
                'security:resolve',
            ],
        ];

        return in_array($permission, $permissions[$this->role] ?? []);
    }
}
