<?php

declare(strict_types=1);

namespace Modules\Escrow\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Escrow\Exceptions\EscrowNotFoundException;
use Modules\Escrow\Services\EscrowService;
use Modules\Escrow\Models\EscrowAgreement;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class EscrowFeatureTest extends TestCase
{
    use RefreshDatabase;
    private EscrowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(EscrowService::class);
    }

    public function test_can_create_escrow(): void
    {
        $buyer = $this->createUser('01ARbuyerEscrow01');
        $seller = $this->createUser('01ARsellerEscr01');

        $agreement = $this->service->create($buyer->id, $seller->id, 500000, 'order', 'ORD-001', 'Test escrow');

        $this->assertEquals('held', $agreement->status);
        $this->assertEquals(500000, $agreement->total_amount);
        $this->assertEquals(5000, $agreement->fee_amount);
        $this->assertEquals(495000, $agreement->net_amount);
        $this->assertNotNull($agreement->cfe_hold_id);
    }

    public function test_can_release_escrow(): void
    {
        $buyer = $this->createUser('01ARbuyerEscrow02');
        $seller = $this->createUser('01ARsellerEscr02');

        $agreement = $this->service->create($buyer->id, $seller->id, 250000, 'contract', 'CT-001');
        $released = $this->service->release($agreement->id);

        $this->assertEquals('released', $released->status);
        $this->assertNotNull($released->completed_at);
    }

    public function test_can_refund_escrow(): void
    {
        $buyer = $this->createUser('01ARbuyerEscrow03');
        $seller = $this->createUser('01ARsellerEscr03');

        $agreement = $this->service->create($buyer->id, $seller->id, 100000, 'service', 'SV-001');
        $refunded = $this->service->refund($agreement->id);

        $this->assertEquals('refunded', $refunded->status);
        $this->assertNotNull($refunded->completed_at);
    }

    public function test_can_open_and_resolve_dispute(): void
    {
        $buyer = $this->createUser('01ARbuyerEscrow04');
        $seller = $this->createUser('01ARsellerEscr04');

        $agreement = $this->service->create($buyer->id, $seller->id, 750000, 'order', 'ORD-002');
        $dispute = $this->service->openDispute($agreement->id, $buyer->id, 'Item not delivered');

        $this->assertEquals('open', $dispute->status);
        $this->assertEquals('Item not delivered', $dispute->reason);

        $resolved = $this->service->resolveDispute($dispute->id, $seller->id, 'Refund agreed', 'refund');

        $this->assertEquals('resolved', $resolved->status);
        $this->assertEquals('Refund agreed', $resolved->resolution);
        $this->assertEquals('refunded', EscrowAgreement::find($agreement->id)->status);
    }

    public function test_list_by_status(): void
    {
        $buyer = $this->createUser('01ARbuyerEscrow05');
        $seller = $this->createUser('01ARsellerEscr05');

        $this->service->create($buyer->id, $seller->id, 300000, 'order', 'ORD-003');
        $this->service->create($buyer->id, $seller->id, 200000, 'order', 'ORD-004');

        $result = $this->service->listByStatus('held');

        $this->assertCount(2, $result);
    }

    public function test_find_or_fail_throws(): void
    {
        $this->expectException(EscrowNotFoundException::class);
        $this->service->findOrFail('nonexistent-id');
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
