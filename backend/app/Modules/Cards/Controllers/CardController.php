<?php

declare(strict_types=1);

namespace Modules\Cards\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cards\DTOs\CreateCardDto;
use Modules\Cards\DTOs\AuthorizeTransactionDto;
use Modules\Cards\Http\Requests\CreateCardRequest;
use Modules\Cards\Http\Requests\UpdateCardLimitsRequest;
use Modules\Cards\Http\Requests\AuthorizeTransactionRequest;
use Modules\Cards\Http\Requests\BlockMerchantRequest;
use Modules\Cards\Services\CardService;
use Modules\Cards\Services\CardAuthorizationService;
use Modules\Cards\Repositories\CardRepository;
use Modules\Cards\Repositories\CardTransactionRepository;
use Modules\Cards\Repositories\CardMerchantBlockRepository;

class CardController extends Controller
{
    public function __construct(
        private readonly CardService $cardService,
        private readonly CardAuthorizationService $authorizationService,
        private readonly CardRepository $cardRepository,
        private readonly CardTransactionRepository $transactionRepository,
        private readonly CardMerchantBlockRepository $merchantBlockRepository,
    ) {}

    public function createCard(CreateCardRequest $request): JsonResponse
    {
        $dto = new CreateCardDto(
            userId: $request->user()->id,
            cardType: $request->input('card_type', 'virtual'),
            cardholderName: $request->input('cardholder_name'),
            currency: $request->input('currency', 'SYP'),
            isVirtual: $request->boolean('is_virtual', true),
        );

        $card = $this->cardService->createCard($dto);
        return response()->json(['data' => $card], 201);
    }

    public function listCards(Request $request): JsonResponse
    {
        $cards = $this->cardRepository->findByUser($request->user()->id);
        return response()->json(['data' => $cards]);
    }

    public function showCard(string $id): JsonResponse
    {
        $card = $this->cardService->findOrFail($id);
        return response()->json(['data' => $card]);
    }

    public function activateCard(string $id): JsonResponse
    {
        $card = $this->cardService->activateCard($id);
        return response()->json(['data' => $card]);
    }

    public function suspendCard(Request $request, string $id): JsonResponse
    {
        $card = $this->cardService->suspendCard($id, $request->input('reason', 'Manual suspend'));
        return response()->json(['data' => $card]);
    }

    public function cancelCard(string $id): JsonResponse
    {
        $card = $this->cardService->cancelCard($id);
        return response()->json(['data' => $card]);
    }

    public function updateLimits(UpdateCardLimitsRequest $request, string $id): JsonResponse
    {
        $this->cardService->findOrFail($id);
        $card = $this->cardRepository->update($id, $request->only([
            'daily_limit', 'weekly_limit', 'monthly_limit', 'single_txn_limit',
        ]));
        return response()->json(['data' => $card]);
    }

    public function authorizeTransaction(AuthorizeTransactionRequest $request, string $id): JsonResponse
    {
        $dto = new AuthorizeTransactionDto(
            cardId: $id,
            userId: $request->user()->id,
            type: $request->input('type', 'purchase'),
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            merchantName: $request->input('merchant_name'),
            merchantCategory: $request->input('merchant_category'),
            merchantCountry: $request->input('merchant_country'),
            channel: $request->input('channel'),
        );

        $txn = $this->authorizationService->authorize($dto);
        $statusCode = $txn->status === 'approved' ? 200 : 403;
        return response()->json(['data' => $txn], $statusCode);
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        $txns = $this->transactionRepository->findByCard(
            $id,
            (int) $request->input('per_page', 15),
        );
        return response()->json(['data' => $txns]);
    }

    public function blockMerchant(BlockMerchantRequest $request, string $id): JsonResponse
    {
        $block = $this->merchantBlockRepository->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'card_id' => $id,
            'merchant_category' => $request->input('merchant_category'),
            'reason' => $request->input('reason'),
        ]);
        return response()->json(['data' => $block], 201);
    }

    public function unblockMerchant(Request $request, string $id): JsonResponse
    {
        $this->merchantBlockRepository->remove($id, $request->input('merchant_category'));
        return response()->json(null, 204);
    }

    public function listMerchantBlocks(string $id): JsonResponse
    {
        $blocks = $this->merchantBlockRepository->findByCard($id);
        return response()->json(['data' => $blocks]);
    }

    public function updateSettings(Request $request, string $id): JsonResponse
    {
        $this->cardService->findOrFail($id);
        $card = $this->cardRepository->update($id, $request->only([
            'international_enabled', 'atm_enabled', 'contactless_enabled', 'ecommerce_enabled',
        ]));
        return response()->json(['data' => $card]);
    }

    public function programSummary(): JsonResponse
    {
        $totalCards = \Modules\Cards\Models\Card::count();
        $activeCards = \Modules\Cards\Models\Card::where('status', 'active')->count();
        $suspendedCards = \Modules\Cards\Models\Card::where('status', 'suspended')->count();
        $pendingActivation = \Modules\Cards\Models\Card::where('status', 'issued')->count();
        $totalVolume = \Modules\Cards\Models\CardTransaction::where('status', 'approved')->sum('amount');
        $totalFees = \Modules\Cards\Models\CardTransaction::where('status', 'approved')->sum('fee_amount');

        return response()->json(['data' => [
            'total_cards' => $totalCards,
            'active_cards' => $activeCards,
            'suspended_cards' => $suspendedCards,
            'pending_activation' => $pendingActivation,
            'total_volume' => $totalVolume,
            'total_fees' => $totalFees,
        ]]);
    }

    public function disputes(string $id): JsonResponse
    {
        $transactions = \Modules\Cards\Models\CardTransaction::where('card_id', $id)
            ->where('status', 'declined')
            ->orderByDesc('created_at')
            ->get();
        return response()->json(['data' => $transactions]);
    }
}
