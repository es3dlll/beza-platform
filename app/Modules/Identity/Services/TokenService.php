<?php

declare(strict_types=1);

namespace Modules\Identity\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\Session;
use Modules\Identity\Models\User;

class TokenService
{
    public function generateToken(User $user): string
    {
        return auth('api')->login($user);
    }

    public function generateRefreshToken(User $user): string
    {
        return Hash::make($user->id . now()->timestamp);
    }

    public function generateTokenPair(User $user): array
    {
        $token = $this->generateToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    public function createSession(User $user, array $data = []): Session
    {
        return Session::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($data['token'] ?? ''),
            'refresh_token_hash' => Hash::make($data['refresh_token'] ?? ''),
            'device_id' => $data['device_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('beza.jwt_ttl', 15)),
        ]);
    }

    public function refreshToken(string $refreshTokenHash): ?array
    {
        $session = Session::where('refresh_token_hash', $refreshTokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if ($session === null) {
            return null;
        }

        $user = User::find($session->user_id);

        if ($user === null) {
            return null;
        }

        return $this->generateTokenPair($user);
    }

    public function invalidateSession(string $tokenHash): void
    {
        Session::where('token_hash', $tokenHash)->delete();
    }

    public function invalidateAllUserSessions(string $userId): void
    {
        Session::where('user_id', $userId)->delete();
    }
}
