<?php

declare(strict_types=1);

namespace Modules\Cards\Services;

use Illuminate\Support\Str;
use Modules\Cards\DTOs\AuthorizeTransactionDto;
use Modules\Cards\Enums\CardStatus;
use Modules\Cards\Enums\CardTransactionStatus;
use Modules\Cards\Enums\CardTransactionType;
use Modules\Cards\Events\CardTransactionAuthorized;
use Modules\Cards\Events\CardTransactionDeclined;
use Modules\Cards\Exceptions\CardSuspendedException;
use Modules\Cards\Exceptions\CardExpiredException;
use Modules\Cards\Exceptions\CardLimitExceededException;
use Modules\Cards\Exceptions\MerchantBlockedException;
use Modules\Cards\Models\Card;
use Modules\Cards\Models\CardTransaction;
use Modules\Cards\Repositories\CardRepository;
use Modules\Cards\Repositories\CardTransactionRepository;
use Modules\Cards\Repositories\CardMerchantBlockRepository;

class CardAuthorizationService
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly CardTransactionRepository $transactionRepository,
        private readonly CardMerchantBlockRepository $merchantBlockRepository,
        private readonly CardSpendingControlService $spendingControl,
    ) {}

    public function authorize(AuthorizeTransactionDto $dto): CardTransaction
    {
        $card = $this->cardRepository->findById($dto->cardId);
        if (!$card) {
            return $this->decline($dto, 'CARD_NOT_FOUND');
        }

        // Status checks
        if ($card->status === CardStatus::SUSPENDED->value || $card->status === CardStatus::BLOCKED->value) {
            return $this->decline($dto, 'CARD_SUSPENDED');
        }

        if ($card->status === CardStatus::CANCELLED->value || $card->status === CardStatus::EXPIRED->value) {
            return $this->decline($dto, 'CARD_EXPIRED');
        }

        if ($card->expires_at && $card->expires_at->isPast()) {
            return $this->decline($dto, 'CARD_EXPIRED');
        }

        // Feature checks
        if ($dto->channel === 'atm' && !$card->atm_enabled) {
            return $this->decline($dto, 'ATM_NOT_ENABLED');
        }

        if ($dto->channel === 'ecommerce' && !$card->ecommerce_enabled) {
            return $this->decline($dto, 'ECOMMERCE_NOT_ENABLED');
        }

        if ($dto->merchantCountry !== null && $dto->merchantCountry !== 'SY' && !$card->international_enabled) {
            return $this->decline($dto, 'INTERNATIONAL_NOT_ENABLED');
        }

        // Merchant block check
        if ($dto->merchantCategory && $this->merchantBlockRepository->isBlocked($card->id, $dto->merchantCategory)) {
            return $this->decline($dto, 'MERCHANT_CATEGORY_BLOCKED');
        }

        // Limit check
        try {
            $this->spendingControl->checkLimits($card, $dto->amount);
        } catch (CardLimitExceededException $e) {
            return $this->decline($dto, $e->getMessage());
        }

        // Approve
        $txn = $this->transactionRepository->create([
            'id' => (string) Str::ulid(),
            'card_id' => $dto->cardId,
            'user_id' => $dto->userId,
            'type' => $dto->type,
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'status' => CardTransactionStatus::APPROVED->value,
            'merchant_name' => $dto->merchantName,
            'merchant_category' => $dto->merchantCategory,
            'merchant_country' => $dto->merchantCountry,
            'is_international' => ($dto->merchantCountry !== 'SY' && $dto->merchantCountry !== null),
            'channel' => $dto->channel,
            'authorized_at' => now(),
        ]);

        CardTransactionAuthorized::dispatch(
            $txn->id, $dto->cardId, $dto->userId, $dto->amount,
            $dto->merchantName ?? 'Unknown',
        );

        return $txn;
    }

    private function decline(AuthorizeTransactionDto $dto, string $reason): CardTransaction
    {
        $txn = $this->transactionRepository->create([
            'id' => (string) Str::ulid(),
            'card_id' => $dto->cardId,
            'user_id' => $dto->userId,
            'type' => $dto->type,
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'status' => CardTransactionStatus::DECLINED->value,
            'merchant_name' => $dto->merchantName,
            'merchant_category' => $dto->merchantCategory,
            'merchant_country' => $dto->merchantCountry,
            'channel' => $dto->channel,
            'decline_reason' => $reason,
        ]);

        CardTransactionDeclined::dispatch($txn->id, $dto->cardId, $dto->userId, $dto->amount, $reason);

        return $txn;
    }
}
