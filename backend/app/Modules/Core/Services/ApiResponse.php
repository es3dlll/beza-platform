<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'تمت العملية بنجاح',
        int $code = 200,
    ): JsonResponse {
        return self::build(true, $message, $data, null, $code);
    }

    public static function error(
        string $message = 'حدث خطأ',
        mixed $errors = null,
        int $code = 400,
    ): JsonResponse {
        return self::build(false, $message, null, $errors, $code);
    }

    private static function build(
        bool $success,
        string $message,
        mixed $data,
        mixed $errors,
        int $code,
    ): JsonResponse {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-Id'),
        ], $code);
    }
}
