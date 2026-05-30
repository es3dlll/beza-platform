<?php

declare(strict_types=1);

namespace Modules\Identity\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\Http\Requests\CheckPhoneRequest;
use Modules\Identity\Http\Requests\RegisterUserRequest;
use Modules\Identity\Http\Requests\SendPhoneVerificationOtpRequest;
use Modules\Identity\Http\Requests\UpdateProfileRequest;
use Modules\Identity\Http\Requests\VerifyPhoneOtpRequest;
use Modules\Identity\Services\IdentityService;

final class IdentityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private IdentityService $identityService,
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $dto = new RegisterUserDto(
            phone: $request->input('phone'),
            phoneCountryCode: $request->input('phone_country_code', '963'),
            locale: $request->input('locale', 'ar'),
        );

        $user = $this->identityService->register($dto);

        return $this->respondWithMeta([
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'status' => $user->status,
            ],
        ], [
            'message' => __('identity::messages.otp_sent'),
        ], null, 201);
    }

    public function checkPhone(CheckPhoneRequest $request): JsonResponse
    {
        $result = $this->identityService->checkPhone($request->input('phone'));

        return $this->respond($result);
    }

    public function profile(): JsonResponse
    {
        $user = $this->identityService->getUserProfile(auth()->id());

        return $this->respond([
            'user' => $user->toArray(),
        ]);
    }

    public function sendPhoneVerificationOtp(SendPhoneVerificationOtpRequest $request): JsonResponse
    {
        $this->identityService->sendPhoneVerificationOtp(auth()->id());

        return $this->respond(null, null, 200, [
            'meta' => [
                'message' => __('identity::messages.otp_sent'),
            ],
        ]);
    }

    public function verifyPhoneOtp(VerifyPhoneOtpRequest $request): JsonResponse
    {
        $verified = $this->identityService->verifyPhoneOtp(
            auth()->id(),
            $request->input('code'),
        );

        if (! $verified) {
            return $this->respondError('INVALID_OTP', __('identity::messages.invalid_or_expired_otp'), null, 422);
        }

        return $this->respondWithMeta([
            'phone_verified' => true,
        ], [
            'message' => __('identity::messages.phone_verified'),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'full_name',
                'full_name_ar',
                'national_id',
                'date_of_birth',
                'gender',
                'address',
                'city',
                'province',
            ])
        );

        return $this->respondWithMeta([
            'profile' => $profile->fresh(),
        ], [
            'message' => __('identity::messages.profile_updated'),
        ]);
    }
}
