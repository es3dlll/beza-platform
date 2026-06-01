<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'admin' || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحية المشرف'],
            ], 401);
        }

        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role']),
                'permissions' => ['manage_wap'],
            ],
        ])->cookie('admin_token', $token, 60 * 24, '/', null, true, true);
    }

    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role']),
                'permissions' => $user->role === 'admin' ? ['manage_wap'] : [],
            ],
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $request->cookie('admin_token', '');
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $accessToken->delete();
            }
        }

        return response()->json(['success' => true])
            ->cookie('admin_token', '', -1);
    }
}
