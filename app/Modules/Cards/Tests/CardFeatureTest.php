<?php

declare(strict_types=1);

namespace Modules\Cards\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cards\DTOs\CreateCardDto;
use Modules\Cards\DTOs\AuthorizeTransactionDto;
use Modules\Cards\Enums\CardStatus;
use Modules\Cards\Enums\CardTransactionStatus;
use Modules\Cards\Exceptions\CardNotFoundException;
use Modules\Cards\Models\Card;
use Modules\Cards\Models\CardTransaction;
use Modules\Cards\Models\CardMerchantBlock;
use Modules\Cards\Services\CardService;
use Modules\Cards\Services\CardAuthorizationService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class CardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CardService $cardService;
    private CardAuthorizationService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cardService = $this->app->make(CardService::class);
        $this->authService = $this->app->make(CardAuthorizationService::class);
    }

    public function test_can_create_virtual_card(): void
    {
        $user = $this->createUser('01ARcardUser001');

        $card = $this->cardService->createCard(new CreateCardDto(
            userId: $user->id,
            cardType: 'virtual',
            cardholderName: 'Ahmed Ali',
        ));

        $this->assertInstanceOf(Card::class, $card);
        $this->assertEquals(CardStatus::PENDING->value, $card->status);
        $this->assertTrue($card->is_virtual);
        $this->assertEquals('Ahmed Ali', $card->cardholder_name);
        $this->assertNotNull($card->card_number_last4);
        $this->assertEquals(4, strlen($card->card_number_last4));
    }

    public function test_can_activate_card(): void
    {
        $card = $this->seedCard('01ARcardUser002');

        $activated = $this->cardService->activateCard($card->id);

        $this->assertEquals(CardStatus::ACTIVE->value, $activated->status);
        $this->assertNotNull($activated->activated_at);
    }

    public function test_can_suspend_card(): void
    {
        $card = $this->seedCard('01ARcardUser003');
        $this->cardService->activateCard($card->id);

        $suspended = $this->cardService->suspendCard($card->id, 'Lost card');

        $this->assertEquals(CardStatus::SUSPENDED->value, $suspended->status);
    }

    public function test_can_cancel_card(): void
    {
        $card = $this->seedCard('01ARcardUser004');

        $cancelled = $this->cardService->cancelCard($card->id);

        $this->assertEquals(CardStatus::CANCELLED->value, $cancelled->status);
    }

    public function test_throws_on_missing_card(): void
    {
        $this->expectException(CardNotFoundException::class);
        $this->cardService->findOrFail('nonexistent');
    }

    public function test_authorizes_transaction(): void
    {
        $card = $this->seedActiveCard('01ARcardUser005');

        $txn = $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 50000,
            merchantName: 'Al-Sham Supermarket',
            merchantCategory: 'grocery',
            channel: 'pos',
        ));

        $this->assertEquals(CardTransactionStatus::APPROVED->value, $txn->status);
        $this->assertEquals(50000, $txn->amount);
        $this->assertEquals('Al-Sham Supermarket', $txn->merchant_name);
    }

    public function test_declines_on_suspended_card(): void
    {
        $card = $this->seedActiveCard('01ARcardUser006');
        $this->cardService->suspendCard($card->id, 'Testing');

        $txn = $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 10000,
        ));

        $this->assertEquals(CardTransactionStatus::DECLINED->value, $txn->status);
        $this->assertEquals('CARD_SUSPENDED', $txn->decline_reason);
    }

    public function test_declines_when_limit_exceeded(): void
    {
        $card = $this->seedActiveCard('01ARcardUser007');

        $txn = $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 99999999,
        ));

        $this->assertEquals(CardTransactionStatus::DECLINED->value, $txn->status);
        $this->assertStringContainsString('limit exceeded', $txn->decline_reason);
    }

    public function test_declines_on_blocked_merchant(): void
    {
        $card = $this->seedActiveCard('01ARcardUser008');

        CardMerchantBlock::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'card_id' => $card->id,
            'merchant_category' => 'gambling',
        ]);

        $txn = $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 5000,
            merchantCategory: 'gambling',
        ));

        $this->assertEquals(CardTransactionStatus::DECLINED->value, $txn->status);
        $this->assertEquals('MERCHANT_CATEGORY_BLOCKED', $txn->decline_reason);
    }

    public function test_declines_when_international_not_enabled(): void
    {
        $card = $this->seedActiveCard('01ARcardUser009');

        $txn = $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 10000,
            merchantCountry: 'US',
        ));

        $this->assertEquals(CardTransactionStatus::DECLINED->value, $txn->status);
        $this->assertEquals('INTERNATIONAL_NOT_ENABLED', $txn->decline_reason);
    }

    public function test_creates_transaction_record(): void
    {
        $card = $this->seedActiveCard('01ARcardUser010');

        $this->authService->authorize(new AuthorizeTransactionDto(
            cardId: $card->id,
            userId: $card->user_id,
            amount: 25000,
            merchantName: 'Test Store',
        ));

        $this->assertEquals(1, CardTransaction::count());

        $saved = CardTransaction::first();
        $this->assertEquals(25000, $saved->amount);
        $this->assertEquals('Test Store', $saved->merchant_name);
    }

    /* ──── Helpers ──── */

    private function createUser(string $id, string $phone = '963900000000'): User
    {
        $user = new User();
        $user->id = $id;
        $user->phone = $phone;
        $user->status = 'active';
        $user->save();
        return $user;
    }

    private function seedCard(string $userId): Card
    {
        $this->createUser($userId, $userId);
        return $this->cardService->createCard(new CreateCardDto(
            userId: $userId,
            cardholderName: 'Test User',
        ));
    }

    private function seedActiveCard(string $userId): Card
    {
        $card = $this->seedCard($userId);
        return $this->cardService->activateCard($card->id);
    }
}
