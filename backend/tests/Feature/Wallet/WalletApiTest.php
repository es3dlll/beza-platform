<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Wallet\Models\Wallet;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone' => '963900000001',
            'status' => 'active',
            'pin_hash' => bcrypt('123456'),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000001',
            'pin' => '123456',
        ]);

        $this->token = $response->json('data.token') ?? 'test-token';
    }

    public function test_can_create_wallet_via_api(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallets', [
                'currency' => 'SYP',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'SYP');
    }

    public function test_can_get_wallet_balance(): void
    {
        $wallet = Wallet::create([
            'id' => '01ARwalletTestBalance00001',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 50000,
            'available_balance' => 50000,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/wallets/{$wallet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 50000);
    }

    public function test_returns_404_for_nonexistent_wallet(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallets/nonexistent');

        $response->assertStatus(404);
    }

    public function test_can_deposit_via_api(): void
    {
        $wallet = Wallet::create([
            'id' => '01ARwalletTestDeposit0001',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
                'amount' => 100000,
                'currency' => 'SYP',
                'reference_id' => 'api-dep-001',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 100000);
    }

    public function test_can_withdraw_via_api(): void
    {
        $wallet = Wallet::create([
            'id' => '01ARwalletTestWithdraw001',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 200000,
            'available_balance' => 200000,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
                'amount' => 50000,
                'reference_id' => 'api-wth-001',
                'apply_fee' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 150000);
    }

    public function test_rejects_deposit_with_invalid_amount(): void
    {
        $wallet = Wallet::create([
            'id' => '01ARwalletTestInvalidAmt01',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
                'amount' => -100,
            ]);

        $response->assertStatus(422);
    }

    public function test_rejects_withdrawal_exceeding_balance(): void
    {
        $wallet = Wallet::create([
            'id' => '01ARwalletTestExceedBal01',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 1000,
            'available_balance' => 1000,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
                'amount' => 50000,
                'reference_id' => 'api-wth-exceed',
                'apply_fee' => false,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_transfer_via_api(): void
    {
        $fromWallet = Wallet::create([
            'id' => '01ARwalletTestTransferFrm1',
            'user_id' => $this->user->id,
            'currency' => 'SYP',
            'balance' => 100000,
            'available_balance' => 100000,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $toUser = User::factory()->create(['phone' => '963900000002', 'status' => 'active', 'phone_verified_at' => now()]);
        $toWallet = Wallet::create([
            'id' => '01ARwalletTestTransferTo01',
            'user_id' => $toUser->id,
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
            'daily_reset_at' => now()->endOfDay(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/wallets/{$fromWallet->id}/transfer", [
                'to_wallet_id' => $toWallet->id,
                'amount' => 30000,
                'apply_fee' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.from.balance', 70000)
            ->assertJsonPath('data.to.balance', 30000);
    }
}
