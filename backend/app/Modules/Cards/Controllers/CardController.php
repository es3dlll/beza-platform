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
use App\Support\ApiResponse;

final class CardController extends Controller
{
    use ApiResponse;
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
        return $this->respondCreated($card);
    }

    public function listCards(Request $request): JsonResponse
    {
        $cards = $this->cardRepository->findByUser($request->user()->id);
        return $this->respond($cards);
    }

    public function showCard(string $id): JsonResponse
    {
        $card = $this->cardService->findOrFail($id);
        return $this->respond($card);
    }

    public function activateCard(string $id): JsonResponse
    {
        $card = $this->cardService->activateCard($id);
        return $this->respond($card);
    }

    public function suspendCard(Request $request, string $id): JsonResponse
    {
        $card = $this->cardService->suspendCard($id, $request->input('reason', 'Manual suspend'));
        return $this->respond($card);
    }

    public function cancelCard(string $id): JsonResponse
    {
        $card = $this->cardService->cancelCard($id);
        return $this->respond($card);
    }

    public function updateLimits(UpdateCardLimitsRequest $request, string $id): JsonResponse
    {
        $this->cardService->findOrFail($id);
        $card = $this->cardRepository->update($id, $request->only([
            'daily_limit', 'weekly_limit', 'monthly_limit', 'single_txn_limit',
        ]));
        return $this->respond($card);
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
        return $this->respond($txn, null, $statusCode);
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        $txns = $this->transactionRepository->findByCard(
            $id,
            (int) $request->input('per_page', 15),
        );
        return $this->respond($txns);
    }

    public function blockMerchant(BlockMerchantRequest $request, string $id): JsonResponse
    {
        $block = $this->merchantBlockRepository->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'card_id' => $id,
            'merchant_category' => $request->input('merchant_category'),
            'reason' => $request->input('reason'),
        ]);
        return $this->respondCreated($block);
    }

    public function unblockMerchant(Request $request, string $id): JsonResponse
    {
        $this->merchantBlockRepository->remove($id, $request->input('merchant_category'));
        return $this->respond(null, null, 204);
    }

    public function listMerchantBlocks(string $id): JsonResponse
    {
        $blocks = $this->merchantBlockRepository->findByCard($id);
        return $this->respond($blocks);
    }

    public function updateSettings(Request $request, string $id): JsonResponse
    {
        $this->cardService->findOrFail($id);
        $card = $this->cardRepository->update($id, $request->only([
            'international_enabled', 'atm_enabled', 'contactless_enabled', 'ecommerce_enabled',
        ]));
        return $this->respond($card);
    }

    public function programSummary(): JsonResponse
    {
        $totalCards = \Modules\Cards\Models\Card::count();
        $activeCards = \Modules\Cards\Models\Card::where('status', 'active')->count();
        $suspendedCards = \Modules\Cards\Models\Card::where('status', 'suspended')->count();
        $pendingActivation = \Modules\Cards\Models\Card::where('status', 'issued')->count();
        $totalVolume = \Modules\Cards\Models\CardTransaction::where('status', 'approved')->sum('amount');
        $totalFees = \Modules\Cards\Models\CardTransaction::where('status', 'approved')->sum('fee_amount');

        return $this->respond([
            'total_cards' => $totalCards,
            'active_cards' => $activeCards,
            'suspended_cards' => $suspendedCards,
            'pending_activation' => $pendingActivation,
            'total_volume' => $totalVolume,
            'total_fees' => $totalFees,
        ]);
    }

    public function disputes(string $id): JsonResponse
    {
        $transactions = \Modules\Cards\Models\CardTransaction::where('card_id', $id)
            ->where('status', 'declined')
            ->orderByDesc('created_at')
            ->get();
        return $this->respond($transactions);
    }
}
