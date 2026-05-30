<?php

declare(strict_types=1);

namespace Modules\Marketplace\Exceptions;

use Exception;

class GiftCardNotFoundException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'GIFT_CARD_NOT_FOUND',
                'message' => 'Gift card not found',
                'message_ar' => 'بطاقة الهدية غير موجودة',
            ],
        ], 404);
    }
}

class GiftCardExpiredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'GIFT_CARD_EXPIRED',
                'message' => 'Gift card has expired',
                'message_ar' => 'بطاقة الهدية منتهية الصلاحية',
            ],
        ], 422);
    }
}

class GiftCardAlreadyRedeemedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'GIFT_CARD_ALREADY_REDEEMED',
                'message' => 'Gift card has already been redeemed',
                'message_ar' => 'تم استبدال بطاقة الهدية بالفعل',
            ],
        ], 422);
    }
}

class PromoCodeInvalidException extends Exception
{
    public function __construct(string $reason = '')
    {
        parent::__construct($reason ?: 'Promo code is invalid');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'PROMO_CODE_INVALID',
                'message' => $this->getMessage(),
                'message_ar' => 'رمز الترويج غير صالح',
            ],
        ], 422);
    }
}

class InsufficientPointsException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INSUFFICIENT_POINTS',
                'message' => 'Insufficient loyalty points',
                'message_ar' => 'نقاط الولاء غير كافية',
            ],
        ], 422);
    }
}
