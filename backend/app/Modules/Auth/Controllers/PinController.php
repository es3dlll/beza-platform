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
use App\Support\ApiResponse;

final class PinController extends Controller
{
    use ApiResponse;
    public function __construct(
        private AuthService $authService,
        private PinService $pinService,
    ) {}

    public function create(CreatePinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->respondUnauthenticated(__('identity::messages.session_expired'));
        }

        $this->authService->createPin($user->id, $request->input('pin'));

        return $this->respondCreated(null, __('identity::messages.pin_created'));
    }

    public function change(ChangePinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->respondUnauthenticated(__('identity::messages.session_expired'));
        }

        if (! $this->pinService->verify($request->input('current_pin'), $user->pin_hash)) {
            return $this->respondError('AUTH_INVALID_PIN', __('identity::messages.pin_incorrect'), status: 400);
        }

        $this->authService->createPin($user->id, $request->input('new_pin'));

        return $this->respond(null, __('identity::messages.pin_changed'));
    }

    public function verify(VerifyPinRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->respondUnauthenticated(__('identity::messages.session_expired'));
        }

        $valid = $this->pinService->verify($request->input('pin'), $user->pin_hash);

        if (! $valid) {
            return $this->respondError('AUTH_INVALID_PIN', __('identity::messages.pin_incorrect'), status: 400);
        }

        return $this->respond(null, __('identity::messages.pin_verified'));
    }
}
