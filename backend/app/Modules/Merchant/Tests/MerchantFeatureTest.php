<?php

declare(strict_types=1);

namespace Modules\Merchant\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Merchant\DTOs\RegisterMerchantDto;
use Modules\Merchant\DTOs\CreateStoreDto;
use Modules\Merchant\DTOs\MerchantPaymentDto;
use Modules\Merchant\Enums\MerchantStatus;
use Modules\Merchant\Enums\MerchantPaymentStatus;
use Modules\Merchant\Exceptions\MerchantNotFoundException;
use Modules\Merchant\Exceptions\MerchantSuspendedException;
use Modules\Merchant\Exceptions\MerchantPaymentAboveMaximumException;
use Modules\Merchant\Exceptions\MerchantRefundExpiredException;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\MerchantStore;
use Modules\Merchant\Models\MerchantPayment;
use Modules\Merchant\Services\MerchantService;
use Modules\Merchant\Services\MerchantPaymentService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class MerchantFeatureTest extends TestCase
{
    use RefreshDatabase;

    private MerchantService $merchantService;
    private MerchantPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchantService = $this->app->make(MerchantService::class);
        $this->paymentService = $this->app->make(MerchantPaymentService::class);
    }

    public function test_can_register_merchant(): void
    {
        $user = $this->createUser('01ARmerchUserTest00001');

        $dto = new RegisterMerchantDto(
            userId: $user->id,
            businessName: 'Al-Sham Supermarket',
            businessNameAr: 'سوبرماركت الشام',
            phone: '963111111111',
            governorate: 'Damascus',
            city: 'Al-Midan',
        );

        $merchant = $this->merchantService->register($dto);

        $this->assertInstanceOf(Merchant::class, $merchant);
        $this->assertEquals(MerchantStatus::PENDING->value, $merchant->status);
        $this->assertEquals('Al-Sham Supermarket', $merchant->business_name);
    }

    public function test_can_approve_merchant(): void
    {
        $merchant = $this->seedMerchant('01ARmerchUserTest00002');

        $approved = $this->merchantService->approve($merchant->id, 'admin-001');

        $this->assertEquals(MerchantStatus::ACTIVE->value, $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_can_suspend_merchant(): void
    {
        $merchant = $this->seedMerchant('01ARmerchUserTest00003');
        $this->merchantService->approve($merchant->id, 'admin-001');

        $suspended = $this->merchantService->suspend($merchant->id);

        $this->assertEquals(MerchantStatus::SUSPENDED->value, $suspended->status);
    }

    public function test_throws_on_missing_merchant(): void
    {
        $this->expectException(MerchantNotFoundException::class);
        $this->merchantService->findOrFail('nonexistent');
    }

    public function test_can_create_store(): void
    {
        $merchant = $this->seedMerchant('01ARmerchUserTest00004');

        $store = $this->merchantService->createStore(new CreateStoreDto(
            merchantId: $merchant->id,
            name: 'Main Branch',
            nameAr: 'الفرع الرئيسي',
            governorate: 'Damascus',
            city: 'Baramkeh',
        ));

        $this->assertInstanceOf(MerchantStore::class, $store);
        $this->assertEquals('Main Branch', $store->name);
    }

    public function test_can_pay_merchant(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0001');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00005');

        $payment = $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000001',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 50000,
        ));

        $this->assertInstanceOf(MerchantPayment::class, $payment);
        $this->assertEquals(MerchantPaymentStatus::PAID->value, $payment->status);
        $this->assertEquals(50000, $payment->amount);
        $this->assertGreaterThan(0, $payment->mdr_fee);
        $this->assertLessThan($payment->amount, $payment->net_amount);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_throws_when_merchant_suspended(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0002');
        $merchant = $this->seedMerchant('01ARmerchUserTest00006');

        $this->expectException(MerchantSuspendedException::class);

        $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000002',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 50000,
        ));
    }

    public function test_throws_when_below_minimum(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0003');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00007');

        $this->expectException(MerchantPaymentAboveMaximumException::class);

        $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000003',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 100,
        ));
    }

    public function test_throws_when_above_max(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0004');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00008');

        $this->expectException(MerchantPaymentAboveMaximumException::class);
        $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000004',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 999999999,
        ));
    }

    public function test_can_refund_within_window(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0005');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00009');
        $payment = $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000005',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 50000,
        ));

        $refunded = $this->paymentService->refund($payment->id, 'Customer changed mind');
        $this->assertEquals(MerchantPaymentStatus::REFUNDED->value, $refunded->status);
    }

    public function test_throws_refund_after_expiry(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0006');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00010');
        $payment = $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST00000000000006',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 50000,
        ));

        $this->travel(8)->days();

        $this->expectException(MerchantRefundExpiredException::class);
        $this->paymentService->refund($payment->id, 'Too late');
    }

    public function test_checks_mdr_fee_calculation(): void
    {
        $payer = $this->createUser('01ARmerchPayerTest0007');
        $merchant = $this->seedActiveMerchant('01ARmerchUserTest00011');

        $payment = $this->paymentService->pay(new MerchantPaymentDto(
            qrCode: 'MQR-TEST-MDR-0000001',
            merchantId: $merchant->id,
            payerUserId: $payer->id,
            amount: 100000,
        ));

        $expectedFee = (int) round(100000 * 0.01);
        $this->assertEquals($expectedFee, $payment->mdr_fee);
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

    private function seedMerchant(string $userId): Merchant
    {
        $this->createUser($userId, $userId);
        return $this->merchantService->register(new RegisterMerchantDto(
            userId: $userId,
            businessName: 'Test Merchant',
            businessNameAr: 'تاجر اختبار',
            phone: $userId,
            governorate: 'Damascus',
            city: 'Center',
        ));
    }

    private function seedActiveMerchant(string $userId): Merchant
    {
        $merchant = $this->seedMerchant($userId);
        return $this->merchantService->approve($merchant->id, 'admin-test');
    }
}
