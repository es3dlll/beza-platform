<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Illuminate\Support\Str;
use Modules\Marketplace\Enums\GiftCardStatus;
use Modules\Marketplace\Enums\OrderStatus;
use Modules\Marketplace\Exceptions\GiftCardAlreadyRedeemedException;
use Modules\Marketplace\Exceptions\GiftCardExpiredException;
use Modules\Marketplace\Exceptions\GiftCardNotFoundException;
use Modules\Marketplace\Models\Fulfillment;
use Modules\Marketplace\Models\GiftCard;
use Modules\Marketplace\Models\Order;

final class GiftCardService
{
    public function purchase(string $orderId, array $cards): array
    {
        $order = Order::findOrFail($orderId);

        $created = [];

        foreach ($cards as $cardData) {
            $code = $this->generateUniqueCode();

            $giftCard = GiftCard::create([
                'order_id' => $order->id,
                'vendor_id' => $order->vendor_id,
                'amount' => $cardData['amount'],
                'balance' => $cardData['amount'],
                'code' => $code,
                'pin' => $cardData['pin'] ?? null,
                'recipient_phone' => $cardData['recipient_phone'] ?? null,
                'message' => $cardData['message'] ?? null,
                'status' => GiftCardStatus::Active,
                'delivery_method' => 'sms',
                'expires_at' => now()->addYear(),
            ]);

            $created[] = $giftCard;
        }

        $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);

        Fulfillment::create([
            'order_id' => $order->id,
            'type' => 'gift_card_delivery',
            'provider' => 'internal',
            'status' => 'completed',
            'fulfilled_at' => now(),
        ]);

        return $created;
    }

    public function deliver(string $id, string $method = 'sms'): GiftCard
    {
        $giftCard = GiftCard::findOrFail($id);

        $giftCard->update([
            'delivery_method' => $method,
            'delivered_at' => now(),
            'status' => GiftCardStatus::Delivered,
        ]);

        return $giftCard->fresh();
    }

    public function redeem(string $code, ?string $pin, string $userId): array
    {
        $giftCard = GiftCard::where('code', $code)->first();

        if ($giftCard === null) {
            throw new GiftCardNotFoundException();
        }

        if ($giftCard->status === GiftCardStatus::Redeemed) {
            throw new GiftCardAlreadyRedeemedException();
        }

        if ($giftCard->expires_at->isPast()) {
            throw new GiftCardExpiredException();
        }

        if ($pin !== null && $giftCard->pin !== null && $giftCard->pin !== $pin) {
            throw new GiftCardNotFoundException();
        }

        $amount = $giftCard->balance;

        $giftCard->update([
            'balance' => 0,
            'status' => GiftCardStatus::Redeemed,
            'redeemed_at' => now(),
        ]);

        return [
            'amount' => $amount,
            'code' => $giftCard->code,
        ];
    }

    public function checkBalance(string $code): int
    {
        $giftCard = GiftCard::where('code', $code)->firstOrFail();

        return $giftCard->balance;
    }

    public function listByVendor(string $vendorId): iterable
    {
        return GiftCard::where('vendor_id', $vendorId)->orderBy('created_at', 'desc')->get();
    }

    public function listByRecipient(string $phone): iterable
    {
        return GiftCard::where('recipient_phone', $phone)->orderBy('created_at', 'desc')->get();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(Str::random(8));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }
}
