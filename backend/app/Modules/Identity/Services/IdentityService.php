<?php

declare(strict_types=1);

namespace Modules\Identity\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Identity\DTOs\CreatePinDto;
use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\DTOs\VerifyOtpDto;
use Modules\Identity\Events\DeviceBound;
use Modules\Identity\Events\UserRegistered;
use Modules\Identity\Exceptions\EmailAlreadyRegisteredException;
use Modules\Identity\Exceptions\PhoneAlreadyRegisteredException;
use Modules\Identity\Models\Device;
use Modules\Identity\Models\User;
use Modules\Identity\Repositories\DeviceRepository;
use Modules\Identity\Repositories\OtpRepository;
use Modules\Identity\Repositories\UserRepository;

class IdentityService
{
    public function __construct(
        private UserRepository $users,
        private OtpRepository $otps,
        private DeviceRepository $devices,
        private OtpService $otpService,
    ) {}

    public function register(RegisterUserDto $dto): User
    {
        $existing = $this->users->findByPhone($dto->phone);

        if ($existing !== null) {
            if ($existing->isPhoneVerified()) {
                throw new PhoneAlreadyRegisteredException(
                    __('identity::messages.phone_already_registered')
                );
            }

            $this->otpService->generateAndSend($dto->phone, OtpService::PURPOSE_REGISTER);

            return $existing;
        }

        if ($dto->email !== null) {
            $emailExists = $this->users->findByEmail($dto->email);
            if ($emailExists !== null) {
                throw new EmailAlreadyRegisteredException(
                    __('identity::messages.email_already_registered')
                );
            }
        }

        if ($dto->password !== null) {
            $dto = new RegisterUserDto(
                phone: $dto->phone,
                phoneCountryCode: $dto->phoneCountryCode,
                locale: $dto->locale,
                firstName: $dto->firstName,
                lastName: $dto->lastName,
                email: $dto->email,
                password: Hash::make($dto->password),
            );
        }

        $user = $this->users->create($dto);

        $this->otpService->generateAndSend($dto->phone, OtpService::PURPOSE_REGISTER);

        UserRegistered::dispatch($user->id, $user->phone, now());

        return $user;
    }

    public function verifyOtp(VerifyOtpDto $dto): User
    {
        $verified = $this->otpService->verify($dto->phone, $dto->code, $dto->purpose);

        if (! $verified) {
            throw new \Modules\Identity\Exceptions\OtpExpiredException(
                __('identity::messages.invalid_or_expired_otp')
            );
        }

        return $this->users->findByPhoneOrFail($dto->phone);
    }

    public function createPin(CreatePinDto $dto): void
    {
        $pinHash = bcrypt($dto->pin);
        $this->users->updatePin($dto->userId, $pinHash);
    }

    public function bindDevice(string $userId, string $deviceId, array $data): Device
    {
        $device = $this->devices->bindToUser($userId, $deviceId, $data);

        DeviceBound::dispatch($userId, $deviceId, $data['device_name'] ?? 'Unknown', now());

        return $device;
    }

    public function getUserProfile(string $userId): User
    {
        return $this->users->findById($userId)->load([
            'profile',
            'kycProfile',
            'devices',
        ]);
    }

    public function checkPhone(string $phone): array
    {
        $user = $this->users->findByPhone($phone);

        return [
            'exists' => $user !== null,
            'verified' => $user?->isPhoneVerified() ?? false,
            'has_pin' => $user?->pin_hash !== null ?? false,
        ];
    }

    public function sendPhoneVerificationOtp(string $userId): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $this->otpService->generate($user->phone, OtpService::PURPOSE_PHONE_VERIFY, $userId);
    }

    public function verifyPhoneOtp(string $userId, string $code): bool
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $verified = $this->otpService->verify($user->phone, $code, OtpService::PURPOSE_PHONE_VERIFY);

        if ($verified) {
            $this->users->markPhoneVerified($userId);
        }

        return $verified;
    }
}
