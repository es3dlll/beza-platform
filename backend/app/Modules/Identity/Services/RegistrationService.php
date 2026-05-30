<?php

declare(strict_types=1);

namespace Modules\Identity\Services;

use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\Models\User;

final class RegistrationService
{
    public function __construct(
        private OtpService $otpService,
    ) {}

    public function register(RegisterUserDto $dto): array
    {
        $existingUser = User::where('phone', $dto->phone)->first();

        if ($existingUser !== null) {
            $this->otpService->generate($dto->phone, 'phone_verification', $existingUser->id);

            return [
                'user_id' => $existingUser->id,
                'message' => 'Verification code sent',
            ];
        }

        $user = User::create([
            'phone' => $dto->phone,
            'phone_country_code' => $dto->phoneCountryCode,
            'locale' => $dto->locale,
            'status' => 'pending',
        ]);

        $this->otpService->generate($dto->phone, 'phone_verification', $user->id);

        return [
            'user_id' => $user->id,
            'message' => 'User registered successfully. Verification code sent.',
        ];
    }
}
