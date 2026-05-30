<?php

declare(strict_types=1);

namespace Modules\Identity\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\Models\User;

class UserRepository
{
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findByPhoneOrFail(string $phone): User
    {
        return User::where('phone', $phone)->firstOrFail();
    }

    public function create(RegisterUserDto $dto): User
    {
        return User::create([
            'id' => (string) Str::ulid(),
            'phone' => $dto->phone,
            'phone_country_code' => $dto->phoneCountryCode,
            'locale' => $dto->locale,
            'status' => 'pending',
            'kyc_tier' => 0,
        ]);
    }

    public function updateStatus(string $id, string $status): void
    {
        User::where('id', $id)->update(['status' => $status]);
    }

    public function findWithKycPending(): Collection
    {
        return User::whereHas('kycProfile', function ($query) {
            $query->where('status', 'pending');
        })->get();
    }

    public function findActiveUsers(int $days = 30): Collection
    {
        return User::where('status', 'active')
            ->where('last_login_at', '>=', now()->subDays($days))
            ->get();
    }

    public function updatePin(string $id, string $pinHash): void
    {
        User::where('id', $id)->update([
            'pin_hash' => $pinHash,
            'pin_updated_at' => now(),
        ]);
    }

    public function markPhoneVerified(string $id): void
    {
        User::where('id', $id)->update([
            'phone_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    public function updateLastLogin(string $id): void
    {
        User::where('id', $id)->update([
            'last_login_at' => now(),
        ]);
    }

    public function existsByPhone(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    public function countByStatus(string $status): int
    {
        return User::where('status', $status)->count();
    }
}
