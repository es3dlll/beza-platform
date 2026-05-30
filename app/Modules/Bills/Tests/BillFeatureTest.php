<?php

declare(strict_types=1);

namespace Modules\Bills\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Bills\DTOs\CreateBillProviderDto;
use Modules\Bills\DTOs\BillInquiryDto;
use Modules\Bills\DTOs\PayBillDto;
use Modules\Bills\Enums\BillPaymentStatus;
use Modules\Bills\Exceptions\BillAlreadyPaidException;
use Modules\Bills\Exceptions\BillNotFoundException;
use Modules\Bills\Exceptions\BillAccountFormatInvalidException;
use Modules\Bills\Exceptions\BillInvalidAmountException;
use Modules\Bills\Exceptions\BillRetryExceededException;
use Modules\Bills\Models\BillProvider;
use Modules\Bills\Models\BillPayment;
use Modules\Bills\Services\BillProviderService;
use Modules\Bills\Services\BillPaymentService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class BillFeatureTest extends TestCase
{
    use RefreshDatabase;

    private BillProviderService $providers;
    private BillPaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providers = $this->app->make(BillProviderService::class);
        $this->payments = $this->app->make(BillPaymentService::class);
    }

    public function test_can_create_provider(): void
    {
        $dto = new CreateBillProviderDto(
            code: 'SYRIATEL',
            name: 'Syriatel',
            nameAr: 'سيريتل',
            category: 'telecom',
            accountLabel: 'Invoice Number',
            accountFormatRegex: '^[0-9]{10}$',
            feePercentage: 0.5,
            feeMinSyp: 100,
            feeMaxSyp: 2000,
        );

        $provider = $this->providers->create($dto);

        $this->assertInstanceOf(BillProvider::class, $provider);
        $this->assertEquals('SYRIATEL', $provider->code);
        $this->assertEquals('telecom', $provider->category);
        $this->assertTrue($provider->is_active);
    }

    public function test_can_list_active_providers(): void
    {
        $this->providers->create(new CreateBillProviderDto(
            code: 'MTN', name: 'MTN Syria', nameAr: 'إم تي إن', category: 'telecom',
            accountLabel: 'Phone Number',
        ));
        $this->providers->create(new CreateBillProviderDto(
            code: 'PEED', name: 'Electricity', nameAr: 'الكهرباء', category: 'utility',
            accountLabel: 'Meter Number',
        ));

        $all = $this->providers->allActive();
        $this->assertCount(2, $all);
    }

    public function test_can_inquire_bill(): void
    {
        $user = $this->createUser('01ARbillUserTest000001');
        $provider = $this->seedSyriatel();

        $dto = new BillInquiryDto(
            userId: $user->id,
            billProviderId: $provider->id,
            accountNumber: '0123456789',
        );

        $payment = $this->payments->inquire($dto);

        $this->assertInstanceOf(BillPayment::class, $payment);
        $this->assertEquals(BillPaymentStatus::INQUIRED->value, $payment->status);
        $this->assertEquals('0123456789', $payment->account_number);
        $this->assertGreaterThan(0, $payment->amount_due);
    }

    public function test_throws_on_invalid_account_format(): void
    {
        $user = $this->createUser('01ARbillUserTest000002');
        $provider = $this->seedSyriatel();

        $this->expectException(BillAccountFormatInvalidException::class);

        $this->payments->inquire(new BillInquiryDto(
            userId: $user->id,
            billProviderId: $provider->id,
            accountNumber: 'abc',
        ));
    }

    public function test_throws_on_unknown_provider(): void
    {
        $user = $this->createUser('01ARbillUserTest000003');
        $this->expectException(BillNotFoundException::class);

        $this->payments->inquire(new BillInquiryDto(
            userId: $user->id,
            billProviderId: 'nonexistent1234567890123456',
            accountNumber: '0123456789',
        ));
    }

    public function test_can_pay_bill(): void
    {
        $user = $this->createUser('01ARbillUserTest000004');
        $payment = $this->seedInquiredBill($user);

        $result = $this->payments->pay(new PayBillDto(
            billPaymentId: $payment->id,
            amount: $payment->amount_due,
        ));

        $this->assertEquals(BillPaymentStatus::PAID->value, $result->status);
        $this->assertNotNull($result->paid_at);
        $this->assertGreaterThan(0, $result->fee_amount);
        $this->assertEquals($payment->amount_due, $result->amount_paid);
    }

    public function test_throws_on_already_paid(): void
    {
        $user = $this->createUser('01ARbillUserTest000005');
        $payment = $this->seedInquiredBill($user);

        $this->payments->pay(new PayBillDto(billPaymentId: $payment->id, amount: $payment->amount_due));

        $this->expectException(BillAlreadyPaidException::class);
        $this->payments->pay(new PayBillDto(billPaymentId: $payment->id, amount: $payment->amount_due));
    }

    public function test_throws_on_amount_mismatch(): void
    {
        $user = $this->createUser('01ARbillUserTest000006');
        $payment = $this->seedInquiredBill($user);

        $this->expectException(BillInvalidAmountException::class);
        $this->payments->pay(new PayBillDto(billPaymentId: $payment->id, amount: 999999));
    }

    public function test_can_refund_bill(): void
    {
        $user = $this->createUser('01ARbillUserTest000007');
        $payment = $this->seedInquiredBill($user);

        $this->payments->pay(new PayBillDto(billPaymentId: $payment->id, amount: $payment->amount_due));

        $refunded = $this->payments->refund($payment->id, 'Customer request');
        $this->assertEquals(BillPaymentStatus::REFUNDED->value, $refunded->status);
        $this->assertEquals('Customer request', $refunded->refund_reason);
    }

    public function test_can_get_payment_history(): void
    {
        $user = $this->createUser('01ARbillUserTest000008');
        $p1 = $this->seedInquiredBill($user);
        $p2 = $this->seedInquiredBill($user);

        $this->payments->pay(new PayBillDto(billPaymentId: $p1->id, amount: $p1->amount_due));

        $history = $this->payments->findByUser($user->id, 15);
        $this->assertCount(2, $history);
    }

    public function test_throws_when_payment_not_found(): void
    {
        $this->expectException(BillNotFoundException::class);
        $this->payments->pay(new PayBillDto(billPaymentId: 'nonexistent', amount: 1000));
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

    private function seedSyriatel(): BillProvider
    {
        return $this->providers->create(new CreateBillProviderDto(
            code: 'SYRIATEL',
            name: 'Syriatel',
            nameAr: 'سيريتل',
            category: 'telecom',
            accountLabel: 'Invoice Number',
            accountFormatRegex: '^[0-9]{10}$',
            feePercentage: 0.5,
            feeMinSyp: 100,
            feeMaxSyp: 2000,
        ));
    }

    private function seedInquiredBill(User $user): BillPayment
    {
        if (!BillProvider::exists()) {
            $this->seedSyriatel();
        }
        $provider = BillProvider::first();

        return $this->payments->inquire(new BillInquiryDto(
            userId: $user->id,
            billProviderId: $provider->id,
            accountNumber: '0987654321',
        ));
    }
}
