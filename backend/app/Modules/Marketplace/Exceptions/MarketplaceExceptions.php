<?php

declare(strict_types=1);

namespace Modules\Marketplace\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Product not found',
                'message_ar' => 'المنتج غير موجود',
            ],
        ], 404);
    }
}

class OrderNotFoundException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'ORDER_NOT_FOUND',
                'message' => 'Order not found',
                'message_ar' => 'الطلب غير موجود',
            ],
        ], 404);
    }
}

class VendorNotFoundException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VENDOR_NOT_FOUND',
                'message' => 'Vendor not found',
                'message_ar' => 'التاجر غير موجود',
            ],
        ], 404);
    }
}

class OrderNotInCartException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'ORDER_NOT_IN_CART',
                'message' => 'Order is not in cart status',
                'message_ar' => 'الطلب ليس في حالة سلة التسوق',
            ],
        ], 422);
    }
}

class InsufficientStockException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INSUFFICIENT_STOCK',
                'message' => 'Insufficient stock for product',
                'message_ar' => 'المخزون غير كافٍ للمنتج',
            ],
        ], 422);
    }
}
