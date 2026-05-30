<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    private function respond(mixed $data = null, ?string $message = null, int $status = 200, array $extra = []): JsonResponse
    {
        $response = ['success' => $status >= 200 && $status < 300];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json(array_merge($response, $extra), $status);
    }

    private function respondCreated(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->respond($data, $message, 201);
    }

    private function respondDeleted(?string $message = null): JsonResponse
    {
        return $this->respond(null, $message ?? 'Deleted successfully', 200);
    }

    private function respondError(string $code, ?string $message = null, ?string $messageAr = null, int $status = 422, array $details = []): JsonResponse
    {
        $error = ['code' => $code];

        if ($message !== null) {
            $error['message'] = $message;
        }

        if ($messageAr !== null) {
            $error['message_ar'] = $messageAr;
        }

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $status);
    }

    private function respondNotFound(?string $resource = null): JsonResponse
    {
        $name = $resource ?? 'Resource';
        return $this->respondError(
            code: strtoupper(str_replace(' ', '_', $name)) . '_NOT_FOUND',
            message: "{$name} not found",
            messageAr: __("{$name} غير موجود"),
            status: 404,
        );
    }

    private function respondForbidden(?string $message = null): JsonResponse
    {
        return $this->respondError(
            code: 'FORBIDDEN',
            message: $message ?? 'Forbidden',
            messageAr: __('ليس لديك صلاحية'),
            status: 403,
        );
    }

    private function respondUnauthenticated(?string $message = null): JsonResponse
    {
        return $this->respondError(
            code: 'UNAUTHENTICATED',
            message: $message ?? 'Unauthenticated',
            messageAr: __('يجب تسجيل الدخول'),
            status: 401,
        );
    }

    private function respondWithMeta(mixed $data, array $meta, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'meta' => $meta,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    private function respondPaginated(mixed $data, int $total, int $page, int $perPage): JsonResponse
    {
        return $this->respondWithMeta($data, [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / max($perPage, 1)),
        ]);
    }
}
