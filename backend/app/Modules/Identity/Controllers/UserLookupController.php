<?php
declare(strict_types=1);

namespace App\Modules\Identity\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserLookupController
{
    public function byEmail(Request $request, string $email): JsonResponse
    {
        $user = User::where('email', $email)
            ->with('wallet')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود',
                'data' => null,
                'errors' => ['email' => ['لم يتم العثور على مستخدم بهذا البريد الإلكتروني']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم العثور على المستخدم',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'wallet_id' => $user->wallet?->id,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ]);
    }
}
