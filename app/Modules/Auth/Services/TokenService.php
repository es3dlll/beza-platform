<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\Identity\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenService
{
    public function generateToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function generateRefreshToken(): string
    {
        return Str::random(64) . '.' . bin2hex(random_bytes(32));
    }

    public function validateToken(string $token): ?User
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();

            $user = User::find($payload->get('sub'));

            if ($user === null || $user->isLocked()) {
                return null;
            }

            return $user;
        } catch (JWTException) {
            return null;
        }
    }

    public function invalidateToken(string $token): void
    {
        try {
            JWTAuth::setToken($token)->invalidate();
        } catch (JWTException) {
            // Token already invalid
        }
    }

    public function refreshToken(string $token): string
    {
        try {
            return JWTAuth::setToken($token)->refresh();
        } catch (JWTException $e) {
            throw new \Modules\Auth\Exceptions\TokenExpiredException(
                __('identity::messages.session_expired'),
                previous: $e,
            );
        }
    }

    public function getTokenTTL(): int
    {
        return (int) config('jwt.ttl', 60);
    }

    public function getRefreshTokenTTL(): int
    {
        return (int) config('jwt.refresh_ttl', 20160);
    }
}
