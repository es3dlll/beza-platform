<?php

namespace App\Http\Controllers\Api\Wap;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'],
            ], 401);
        }

        $token = $user->createToken('wap-token', ['wap'])->plainTextToken;

        $fingerprint = (string) Str::uuid();

        $response = response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'role']),
                'wallets' => $user->wallets,
            ],
        ]);

        $response->cookie('wap_token', $token, 10080, '/', null, true, true, false, 'Lax');
        $response->cookie('wap_fp', $fingerprint, 10080, '/', null, true, true, false, 'Lax');

        return $response;
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->cookie('wap_token', '');

        if (!blank($token)) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $accessToken->delete();
            }
        }

        $response = response()->json(['success' => true, 'data' => null]);
        $response->withCookie(cookie()->forget('wap_token'));
        $response->withCookie(cookie()->forget('wap_fp'));

        return $response;
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $request->cookie('wap_token', '');

        if (blank($token)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'لا يوجد جلسة نشطة'],
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->can('wap')) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => 'رمز الدخول غير صالح'],
            ], 401);
        }

        $accessToken->delete();

        $user = User::find($accessToken->tokenable_id);
        $newToken = $user->createToken('wap-token', ['wap'])->plainTextToken;

        $response = response()->json(['success' => true, 'data' => ['refreshed' => true]]);
        $response->cookie('wap_token', $newToken, 10080, '/', null, true, true, false, 'Lax');

        return $response;
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'role']),
                'wallets' => $user->wallets,
            ],
        ]);
    }
}
