<?php

declare(strict_types=1);

namespace Modules\Identity\Services;

use Modules\Identity\DTOs\LoginDto;
use Modules\Identity\Models\User;

final class LoginService
{
    public function __construct(
        private TokenService $tokenService,
    ) {}

    public function login(LoginDto $dto): array
    {
        $user = User::where('phone', $dto->phone)->first();

        if ($user === null) {
            throw new \RuntimeException('Invalid credentials');
        }

        if ($user->isLocked()) {
            throw new \RuntimeException('Account is locked. Try again later.');
        }

        if (!$user->isPhoneVerified()) {
            throw new \RuntimeException('Phone not verified');
        }

        if (!password_verify($dto->pin, $user->pin_hash ?? '')) {
            $user->incrementFailedAttempts();
            throw new \RuntimeException('Invalid PIN');
        }

        $user->resetFailedAttempts();

        $tokenPair = $this->tokenService->generateTokenPair($user);

        $session = $this->tokenService->createSession($user, [
            'token' => $tokenPair['token'],
            'refresh_token' => $tokenPair['refresh_token'],
            'device_id' => $dto->deviceId,
        ]);

        $this->enforceDeviceLimit($user);

        $user->update(['last_login_at' => now()]);

        return array_merge($tokenPair, [
            'user' => $user->load('profile'),
            'session_id' => $session->id,
        ]);
    }

    public function logout(User $user, string $tokenHash): void
    {
        $this->tokenService->invalidateSession($tokenHash);
    }

    private function enforceDeviceLimit(User $user): void
    {
        $maxDevices = config('beza.max_devices_per_user', 2);

        $activeSessions = $user->sessions()
            ->where('expires_at', '>', now())
            ->count();

        if ($activeSessions > $maxDevices) {
            $oldest = $user->sessions()
                ->where('expires_at', '>', now())
                ->orderBy('last_activity', 'asc')
                ->first();

            if ($oldest !== null) {
                $oldest->delete();
            }
        }
    }
}
