<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\DTOs\SendOtpDto;
use Modules\Auth\DTOs\VerifyOtpDto;
use Modules\Auth\Http\Requests\SendOtpRequest;
use Modules\Auth\Http\Requests\VerifyOtpRequest;
use Modules\Auth\Services\AuthService;
use Modules\Identity\Services\OtpService;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private AuthService $authService,
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        $dto = new SendOtpDto(
            phone: $request->input('phone'),
            purpose: $request->input('purpose'),
        );

        $this->otpService->generateAndSend($dto->phone, $dto->purpose);

        return response()->json([
            'success' => true,
            'meta' => [
                'message' => __('identity::messages.otp_sent'),
            ],
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $dto = new VerifyOtpDto(
            phone: $request->input('phone'),
            code: $request->input('code'),
            purpose: $request->input('purpose'),
        );

        $user = $this->authService->verifyOtp($dto->phone, $dto->code, $dto->purpose);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'phone_verified' => $user->isPhoneVerified(),
                ],
            ],
            'meta' => [
                'message' => __('identity::messages.otp_verified'),
            ],
        ]);
    }
}
