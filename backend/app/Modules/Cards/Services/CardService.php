<?php

declare(strict_types=1);

namespace Modules\Cards\Services;

use Illuminate\Support\Str;
use Modules\Cards\DTOs\CreateCardDto;
use Modules\Cards\Enums\CardStatus;
use Modules\Cards\Enums\CardType;
use Modules\Cards\Events\CardCreated;
use Modules\Cards\Events\CardActivated;
use Modules\Cards\Events\CardSuspended;
use Modules\Cards\Exceptions\CardNotFoundException;
use Modules\Cards\Models\Card;
use Modules\Cards\Repositories\CardRepository;

final class CardService
{
    private const EXPIRY_YEARS = 4;

    public function __construct(
        private readonly CardRepository $cardRepository,
    ) {}

    public function createCard(CreateCardDto $dto): Card
    {
        $last4 = $this->generateLast4();
        $expiryMonth = now()->format('m');
        $expiryYear = (now()->addYears(self::EXPIRY_YEARS))->format('y');

        $card = $this->cardRepository->create([
            'id' => (string) Str::ulid(),
            'user_id' => $dto->userId,
            'card_type' => $dto->cardType,
            'status' => CardStatus::PENDING->value,
            'cardholder_name' => $dto->cardholderName,
            'card_number_last4' => $last4,
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
            'currency' => $dto->currency,
            'is_virtual' => $dto->isVirtual,
            'expires_at' => now()->addYears(self::EXPIRY_YEARS),
        ]);

        CardCreated::dispatch($card->id, $dto->userId, $dto->cardType, $last4);
        return $card;
    }

    public function activateCard(string $cardId): Card
    {
        $card = $this->findOrFail($cardId);
        $updated = $this->cardRepository->update($cardId, [
            'status' => CardStatus::ACTIVE->value,
            'activated_at' => now(),
        ]);

        CardActivated::dispatch($cardId, $card->user_id);
        return $updated;
    }

    public function suspendCard(string $cardId, string $reason = 'Manual suspend'): Card
    {
        $this->findOrFail($cardId);
        $updated = $this->cardRepository->update($cardId, [
            'status' => CardStatus::SUSPENDED->value,
            'suspended_at' => now(),
        ]);

        CardSuspended::dispatch($cardId, $updated->user_id, $reason);
        return $updated;
    }

    public function cancelCard(string $cardId): Card
    {
        $this->findOrFail($cardId);
        return $this->cardRepository->update($cardId, [
            'status' => CardStatus::CANCELLED->value,
        ]);
    }

    public function findOrFail(string $id): Card
    {
        $card = $this->cardRepository->findById($id);
        if (!$card) {
            throw new CardNotFoundException($id);
        }
        return $card;
    }

    private function generateLast4(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
