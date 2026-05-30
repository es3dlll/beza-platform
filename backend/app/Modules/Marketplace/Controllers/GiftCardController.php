<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\GiftCardService;
use App\Support\ApiResponse;

final class GiftCardController extends Controller
{
    use ApiResponse;
    public function __construct(
        private GiftCardService $giftCards,
    ) {}

    public function purchaseGiftCards(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|string',
            'cards' => 'required|array|min:1',
            'cards.*.amount' => 'required|integer|min:1',
            'cards.*.pin' => 'nullable|string|max:10',
            'cards.*.recipient_phone' => 'nullable|string|max:20',
            'cards.*.message' => 'nullable|string|max:500',
        ]);

        $giftCards = $this->giftCards->purchase($data['order_id'], $data['cards']);

        return $this->respondCreated($giftCards);
    }

    public function deliverGiftCard(string $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'sometimes|string|in:sms,whatsapp,email',
        ]);

        $giftCard = $this->giftCards->deliver($id, $data['method'] ?? 'sms');

        return $this->respond($giftCard);
    }

    public function redeemGiftCard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'pin' => 'nullable|string|max:10',
        ]);

        $result = $this->giftCards->redeem(
            $data['code'],
            $data['pin'] ?? null,
            $request->user()->id,
        );

        return $this->respond($result);
    }

    public function checkBalance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
        ]);

        $balance = $this->giftCards->checkBalance($data['code']);

        return $this->respond(['balance' => $balance]);
    }

    public function listByVendor(Request $request): JsonResponse
    {
        $giftCards = $this->giftCards->listByVendor($request->user()->id);

        return $this->respond($giftCards);
    }

    public function listByRecipient(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $giftCards = $this->giftCards->listByRecipient($data['phone']);

        return $this->respond($giftCards);
    }
}
