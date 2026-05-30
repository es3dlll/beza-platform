<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;
use Modules\Identity\DTOs\RegisterUserDto;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = new RegisterUserDto(
            phone: $request->input('phone'),
            phoneCountryCode: $request->input('phone_country_code', '963'),
            locale: $request->input('locale', 'ar'),
            firstName: $request->input('first_name'),
            lastName: $request->input('last_name'),
            email: $request->input('email'),
            password: $request->input('password'),
        );

        $result = $this->authService->register($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'first_name' => $result['user']->first_name ?? '',
                    'last_name' => $result['user']->last_name ?? '',
                    'email' => $result['user']->email ?? '',
                    'phone' => $result['user']->phone,
                    'status' => $result['user']->status,
                ],
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ], 201);
    }
}
