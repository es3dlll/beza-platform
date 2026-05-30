<?php

declare(strict_types=1);

namespace Modules\Identity\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Identity\DTOs\RegisterUserDto;
use Modules\Identity\Http\Requests\CheckPhoneRequest;
use Modules\Identity\Http\Requests\RegisterUserRequest;
use Modules\Identity\Http\Requests\UpdateProfileRequest;
use Modules\Identity\Services\IdentityService;

class IdentityController extends Controller
{
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

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],
            ],
            'meta' => [
                'message' => __('identity::messages.otp_sent'),
            ],
        ], 201);
    }

    public function checkPhone(CheckPhoneRequest $request): JsonResponse
    {
        $result = $this->identityService->checkPhone($request->input('phone'));

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function profile(): JsonResponse
    {
        $user = $this->identityService->getUserProfile(auth()->id());

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->toArray(),
            ],
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

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $profile->fresh(),
            ],
            'meta' => [
                'message' => __('identity::messages.profile_updated'),
            ],
        ]);
    }
}
