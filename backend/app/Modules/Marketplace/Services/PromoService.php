<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Exceptions\PromoCodeInvalidException;
use Modules\Marketplace\Models\PromoCode;

final class PromoService
{
    public function create(array $data): PromoCode
    {
        return PromoCode::create($data);
    }

    public function validate(string $code, int $orderAmount): PromoCode
    {
        $promo = PromoCode::where('code', $code)->first();

        if ($promo === null) {
            throw new PromoCodeInvalidException('Promo code not found');
        }

        if (! $promo->is_active) {
            throw new PromoCodeInvalidException('Promo code is not active');
        }

        if ($promo->expires_at !== null && $promo->expires_at->isPast()) {
            throw new PromoCodeInvalidException('Promo code has expired');
        }

        if ($promo->starts_at !== null && $promo->starts_at->isFuture()) {
            throw new PromoCodeInvalidException('Promo code is not yet valid');
        }

        if ($promo->used_count >= $promo->max_uses) {
            throw new PromoCodeInvalidException('Promo code usage limit reached');
        }

        if ($orderAmount < $promo->min_order_amount) {
            throw new PromoCodeInvalidException(
                'Minimum order amount of ' . $promo->min_order_amount . ' required'
            );
        }

        return $promo;
    }

    public function apply(string $code, int $orderAmount): int
    {
        $promo = $this->validate($code, $orderAmount);

        $discount = $promo->discount_type === 'percent'
            ? (int) ($orderAmount * $promo->discount_value / 100)
            : $promo->discount_value;

        $discountedAmount = max(0, $orderAmount - $discount);

        $promo->increment('used_count');

        return $discountedAmount;
    }

    public function listActive(): iterable
    {
        return PromoCode::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereColumn('used_count', '<', 'max_uses')
            ->get();
    }
}
