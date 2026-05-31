<?php

declare(strict_types=1);

namespace App\Modules\Identity\Controllers;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance_fils' => 0,
            'currency' => 'SYP',
        ]);

        $token = $user->createApiToken();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب',
            'data' => [
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'token' => $token,
                'expires_in_minutes' => 15,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        $token = $user->createApiToken();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول',
            'data' => [
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'token' => $token,
                'expires_in_minutes' => 15,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ]);
    }
}
