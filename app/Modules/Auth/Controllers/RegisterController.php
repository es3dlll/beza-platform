<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\DTOs\RegisterRequestDto;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = new RegisterRequestDto(
            phone: $request->input('phone'),
            phoneCountryCode: $request->input('phone_country_code', '963'),
            locale: $request->input('locale', 'ar'),
        );

        $user = $this->authService->register($dto);

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
}
