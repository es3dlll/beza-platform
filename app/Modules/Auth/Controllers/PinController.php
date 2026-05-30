<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\ChangePinRequest;
use Modules\Auth\Http\Requests\CreatePinRequest;
use Modules\Auth\Http\Requests\VerifyPinRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\PinService;

class PinController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private PinService $pinService,
    ) {}

    public function create(CreatePinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => __('identity::messages.session_expired'),
                ],
            ], 401);
        }

        $this->authService->createPin($user->id, $request->input('pin'));

        return response()->json([
            'success' => true,
            'meta' => [
                'message' => __('identity::messages.pin_created'),
            ],
        ], 201);
    }

    public function change(ChangePinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => __('identity::messages.session_expired'),
                ],
            ], 401);
        }

        if (! $this->pinService->verify($request->input('current_pin'), $user->pin_hash)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID_PIN',
                    'message' => __('identity::messages.pin_incorrect'),
                ],
            ], 400);
        }

        $this->authService->createPin($user->id, $request->input('new_pin'));

        return response()->json([
            'success' => true,
            'meta' => [
                'message' => __('identity::messages.pin_changed'),
            ],
        ]);
    }

    public function verify(VerifyPinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => __('identity::messages.session_expired'),
                ],
            ], 401);
        }

        $valid = $this->pinService->verify($request->input('pin'), $user->pin_hash);

        if (! $valid) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID_PIN',
                    'message' => __('identity::messages.pin_incorrect'),
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'meta' => [
                'message' => __('identity::messages.pin_verified'),
            ],
        ]);
    }
}
