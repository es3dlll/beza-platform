<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\DTOs\LoginDto;
use Modules\Auth\DTOs\LogoutDto;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\LogoutRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\TokenService;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private TokenService $tokenService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = new LoginDto(
            phone: $request->input('phone'),
            pin: $request->input('pin'),
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
            deviceType: $request->input('device_type', 'mobile'),
            fcmToken: $request->input('fcm_token'),
        );

        $result = $this->authService->login($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user']->toArray(),
                'token' => $result['token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $result['expires_in'],
            ],
            'meta' => [
                'message' => __('identity::messages.login_success'),
            ],
        ]);
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user !== null) {
            $sessionId = $request->input('session_id', '');
            $dto = new LogoutDto(
                userId: $user->id,
                sessionId: $sessionId,
            );

            $this->authService->logout($dto);

            try {
                $this->tokenService->invalidateToken(
                    $request->bearerToken() ?? ''
                );
            } catch (\Exception) {
                // Token may already be invalid
            }
        }

        return response()->json([
            'success' => true,
            'meta' => [
                'message' => __('identity::messages.logout_success'),
            ],
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->input('refresh_token'));

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $result['token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $result['expires_in'],
            ],
            'meta' => [
                'message' => __('identity::messages.token_refreshed'),
            ],
        ]);
    }
}
