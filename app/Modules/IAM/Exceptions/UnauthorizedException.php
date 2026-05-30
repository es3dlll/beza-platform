<?php

declare(strict_types=1);

namespace Modules\IAM\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'AUTH_FORBIDDEN',
                'message' => $this->getMessage() ?: 'Unauthorized action',
                'message_ar' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
            ],
        ], 403);
    }
}
