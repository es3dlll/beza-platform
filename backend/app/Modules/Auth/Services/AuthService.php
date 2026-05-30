<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\DTOs\LoginDto;
use Modules\Auth\DTOs\LogoutDto;
use Modules\Auth\Events\PhoneVerified;
use Modules\Auth\Events\PinCreated;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Events\UserLoggedOut;
use Modules\Identity\DTOs\CreatePinDto;
use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\DTOs\VerifyOtpDto;
use Modules\Identity\Exceptions\OtpExpiredException;
use Modules\Identity\Exceptions\PhoneAlreadyRegisteredException;
use Modules\Identity\Models\Session;
use Modules\Identity\Models\User;
use Modules\Identity\Repositories\DeviceRepository;
use Modules\Identity\Repositories\OtpRepository;
use Modules\Identity\Repositories\UserRepository;
use Modules\Identity\Services\IdentityService;
use Modules\Identity\Services\OtpService;

final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private OtpRepository $otps,
        private DeviceRepository $devices,
        private OtpService $otpService,
        private IdentityService $identityService,
        private TokenService $tokenService,
        private PinService $pinService,
    ) {}

    public function register(RegisterUserDto $dto): array
    {
        $user = $this->identityService->register($dto);

        $token = $this->tokenService->generateToken($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function verifyOtp(string $phone, string $code, string $purpose): User
    {
        $dto = new VerifyOtpDto(phone: $phone, code: $code, purpose: $purpose);
        $user = $this->identityService->verifyOtp($dto);

        if ($purpose === 'register') {
            $this->users->markPhoneVerified($user->id);
            PhoneVerified::dispatch($user->id, $user->phone, now());
        }

        return $user;
    }

    public function createPin(string $userId, string $pin): void
    {
        if (! $this->pinService->validatePinFormat($pin)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(422, __('identity::messages.pin_invalid_format'));
        }

        $dto = new CreatePinDto(userId: $userId, pin: $pin);
        $this->identityService->createPin($dto);

        PinCreated::dispatch($userId, now());
    }

    public function login(LoginDto $dto): array
    {
        $user = $this->users->findByPhoneOrFail($dto->phone);

        if ($user->isLocked()) {
            throw new \Modules\Auth\Exceptions\AccountLockedException(
                __('identity::messages.account_locked')
            );
        }

        if (! $user->isPhoneVerified()) {
            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.account_pending')
            );
        }

        if ($user->pin_hash === null) {
            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.account_pending')
            );
        }

        if (! $this->pinService->verify($dto->pin, $user->pin_hash)) {
            $user->incrementFailedAttempts();

            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.pin_incorrect')
            );
        }

        $user->resetFailedAttempts();

        $token = $this->tokenService->generateToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken();

        $session = $this->createSession($user, $refreshToken, $token, $dto);

        $this->enforceMaxSessions($user);

        if ($dto->deviceId !== null) {
            $this->identityService->bindDevice(
                $user->id,
                $dto->deviceId,
                [
                    'device_name' => $dto->deviceName ?? 'Unknown',
                    'device_type' => $dto->deviceType ?? 'mobile',
                    'fcm_token' => $dto->fcmToken,
                    'ip_address' => request()->ip(),
                ]
            );
        }

        $this->users->updateLastLogin($user->id);

        UserLoggedIn::dispatch($user->id, $session->id, now());

        return [
            'user' => $user->fresh(),
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->tokenService->getTokenTTL() * 60,
        ];
    }

    public function loginWithPassword(LoginDto $dto): array
    {
        $user = $this->users->findByPhoneOrFail($dto->phone);

        if ($user->isLocked()) {
            throw new \Modules\Auth\Exceptions\AccountLockedException(
                __('identity::messages.account_locked')
            );
        }

        if (! $user->isPhoneVerified()) {
            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.account_pending')
            );
        }

        if (! Hash::check($dto->pin, $user->password)) {
            $user->incrementFailedAttempts();

            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.password_incorrect')
            );
        }

        $user->resetFailedAttempts();

        $token = $this->tokenService->generateToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken();

        $session = $this->createSession($user, $refreshToken, $token, $dto);

        $this->enforceMaxSessions($user);

        if ($dto->deviceId !== null) {
            $this->identityService->bindDevice(
                $user->id,
                $dto->deviceId,
                [
                    'device_name' => $dto->deviceName ?? 'Unknown',
                    'device_type' => $dto->deviceType ?? 'mobile',
                    'fcm_token' => $dto->fcmToken,
                    'ip_address' => request()->ip(),
                ]
            );
        }

        $this->users->updateLastLogin($user->id);

        UserLoggedIn::dispatch($user->id, $session->id, now());

        return [
            'user' => $user->fresh(),
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->tokenService->getTokenTTL() * 60,
        ];
    }

    public function logout(LogoutDto $dto, ?string $bearerToken = null): void
    {
        $session = null;

        if ($dto->sessionId !== '') {
            $session = Session::find($dto->sessionId);
        } elseif ($bearerToken !== null) {
            $session = Session::where('token_hash', hash('sha256', $bearerToken))->first();
        }

        if ($session !== null && $session->user_id === $dto->userId) {
            $session->invalidate();
        }

        UserLoggedOut::dispatch($dto->userId, $dto->sessionId, now());
    }

    public function refreshToken(string $refreshToken): array
    {
        $session = Session::where('refresh_token_hash', hash('sha256', $refreshToken))
            ->where('expires_at', '>', now())
            ->first();

        if ($session === null) {
            throw new \Modules\Auth\Exceptions\TokenExpiredException(
                __('identity::messages.session_expired')
            );
        }

        $user = $this->users->findById($session->user_id);

        if ($user === null || $user->isLocked()) {
            throw new \Modules\Auth\Exceptions\AuthenticationException(
                __('identity::messages.account_locked')
            );
        }

        $token = $this->tokenService->generateToken($user);
        $newRefreshToken = $this->tokenService->generateRefreshToken();

        $session->update([
            'token_hash' => hash('sha256', $token),
            'refresh_token_hash' => hash('sha256', $newRefreshToken),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes($this->tokenService->getRefreshTokenTTL()),
        ]);

        return [
            'token' => $token,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->tokenService->getTokenTTL() * 60,
        ];
    }

    public function validateSession(string $token): ?User
    {
        $tokenHash = hash('sha256', $token);

        $session = Session::where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if ($session === null) {
            return null;
        }

        $session->updateActivity();

        return $session->user;
    }

    private function enforceMaxSessions(User $user, int $maxSessions = 2): void
    {
        $activeSessions = Session::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderBy('created_at')
            ->get();

        while ($activeSessions->count() > $maxSessions) {
            $oldest = $activeSessions->shift();
            $oldest->invalidate();
        }
    }

    private function createSession(User $user, string $refreshToken, string $token, LoginDto $dto): Session
    {
        return Session::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'device_id' => $dto->deviceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes($this->tokenService->getRefreshTokenTTL()),
        ]);
    }
}
