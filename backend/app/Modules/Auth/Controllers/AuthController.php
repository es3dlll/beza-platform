<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->authService->register($request);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 201);
    }
}
