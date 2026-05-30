<?php

declare(strict_types=1);

namespace Modules\Remittance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Remittance\DTOs\CreateCorridorDto;
use Modules\Remittance\DTOs\RegisterBeneficiaryDto;
use Modules\Remittance\DTOs\CreateRemittanceDto;
use Modules\Remittance\Enums\RemittanceStatus;
use Modules\Remittance\Exceptions\RemittanceCorridorUnavailableException;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryNotFoundException;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryKycIncompleteException;
use Modules\Remittance\Exceptions\RemittanceLimitExceededException;
use Modules\Remittance\Exceptions\RemittancePurposeRequiredException;
use Modules\Remittance\Exceptions\RemittanceSourceOfFundsRequiredException;
use Modules\Remittance\Models\Corridor;
use Modules\Remittance\Models\Beneficiary;
use Modules\Remittance\Models\RemittanceOrder;
use Modules\Remittance\Services\CorridorService;
use Modules\Remittance\Services\BeneficiaryService;
use Modules\Remittance\Services\RemittanceService;
use Modules\FX\DTOs\CreateFxRateDto;
use Modules\FX\Services\FxRateService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class RemittanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CorridorService $corridors;
    private BeneficiaryService $beneficiaries;
    private RemittanceService $remittance;
    private FxRateService $fxRates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->corridors = $this->app->make(CorridorService::class);
        $this->beneficiaries = $this->app->make(BeneficiaryService::class);
        $this->remittance = $this->app->make(RemittanceService::class);
        $this->fxRates = $this->app->make(FxRateService::class);
    }

    /* ──── Corridor Tests ──── */

    public function test_can_create_corridor(): void
    {
        $dto = new CreateCorridorDto(
            name: 'GCC → Syria',
            sourceCountry: 'ARE',
            sourceCurrency: 'USD',
            fixedSpreadPct: 1.5,
            minAmount: 50000,
            maxAmount: 5000000,
            supportedPayoutMethods: ['wallet', 'agent'],
        );

        $corridor = $this->corridors->create($dto);

        $this->assertInstanceOf(Corridor::class, $corridor);
        $this->assertEquals('ARE', $corridor->source_country);
        $this->assertEquals('USD', $corridor->source_currency);
        $this->assertTrue($corridor->is_active);
        $this->assertEquals(1.5, $corridor->fixed_spread_pct);
    }

    public function test_throws_when_corridor_unavailable(): void
    {
        $this->expectException(RemittanceCorridorUnavailableException::class);
        $this->corridors->getActive('UNK');
    }

    public function test_can_get_active_corridor(): void
    {
        $this->corridors->create(new CreateCorridorDto(
            name: 'USA → Syria',
            sourceCountry: 'USA',
            sourceCurrency: 'USD',
        ));

        $corridor = $this->corridors->getActive('USA');
        $this->assertNotNull($corridor);
        $this->assertEquals('USA', $corridor->source_country);
    }

    public function test_returns_all_active_corridors(): void
    {
        $this->corridors->create(new CreateCorridorDto(
            name: 'UAE → Syria',
            sourceCountry: 'ARE',
            sourceCurrency: 'USD',
        ));
        $this->corridors->create(new CreateCorridorDto(
            name: 'KSA → Syria',
            sourceCountry: 'SAU',
            sourceCurrency: 'USD',
        ));

        $all = $this->corridors->allActive();
        $this->assertCount(2, $all);
    }

    /* ──── Beneficiary Tests ──── */

    public function test_can_register_beneficiary(): void
    {
        $user = $this->createUser('01ARbenefTestUser00001');

        $dto = new RegisterBeneficiaryDto(
            userId: $user->id,
            fullNameAr: 'أحمد محمد',
            fullNameEn: 'Ahmad Mohammad',
            phone: '963944000001',
            relationship: 'family',
            governorate: 'Damascus',
            city: 'Al-Midan',
        );

        $beneficiary = $this->beneficiaries->register($dto);

        $this->assertInstanceOf(Beneficiary::class, $beneficiary);
        $this->assertEquals('أحمد محمد', $beneficiary->full_name_ar);
        $this->assertFalse($beneficiary->kyc_completed);
    }

    public function test_throws_when_beneficiary_not_found(): void
    {
        $this->expectException(RemittanceBeneficiaryNotFoundException::class);
        $this->beneficiaries->findById('nonexistent');
    }

    public function test_can_complete_beneficiary_kyc(): void
    {
        $user = $this->createUser('01ARbenefTestUser00002');
        $beneficiary = $this->beneficiaries->register(new RegisterBeneficiaryDto(
            userId: $user->id,
            fullNameAr: 'سارة أحمد',
            phone: '963944000002',
            relationship: 'family',
        ));

        $updated = $this->beneficiaries->completeKyc($beneficiary->id);
        $this->assertTrue($updated->kyc_completed);
    }

    public function test_ensures_kyc_completed(): void
    {
        $user = $this->createUser('01ARbenefTestUser00003');
        $beneficiary = $this->beneficiaries->register(new RegisterBeneficiaryDto(
            userId: $user->id,
            fullNameAr: 'خالد عمر',
            phone: '963944000003',
            relationship: 'friend',
        ));

        $this->expectException(RemittanceBeneficiaryKycIncompleteException::class);
        $this->beneficiaries->ensureKycCompleted($beneficiary->id);
    }

    public function test_lists_beneficiaries_by_user(): void
    {
        $user = $this->createUser('01ARbenefTestUser00004');
        $this->beneficiaries->register(new RegisterBeneficiaryDto(
            userId: $user->id, fullNameAr: '第一个', phone: '963944000004', relationship: 'family',
        ));
        $this->beneficiaries->register(new RegisterBeneficiaryDto(
            userId: $user->id, fullNameAr: '第二个', phone: '963944000005', relationship: 'family',
        ));

        $list = $this->beneficiaries->findByUser($user->id);
        $this->assertCount(2, $list);
    }

    /* ──── Remittance Order Tests ──── */

    public function test_can_create_remittance_order(): void
    {
        $setup = $this->seedCorridorAndBeneficiary();
        $this->seedFxRate();

        $dto = new CreateRemittanceDto(
            corridorId: $setup['corridor']->id,
            senderUserId: $setup['sender']->id,
            senderCountry: 'USA',
            senderFullName: 'John Doe',
            senderPhone: '12025550001',
            beneficiaryId: $setup['beneficiary']->id,
            sourceAmount: 100000,
            sourceCurrency: 'USD',
            payoutMethod: 'wallet',
            purposeCode: 'FAMILY_SUPPORT',
            sourceOfFundsDeclaration: 'Personal savings from employment',
        );

        $order = $this->remittance->create($dto);

        $this->assertInstanceOf(RemittanceOrder::class, $order);
        $this->assertEquals(RemittanceStatus::SCREENING->value, $order->status);
        $this->assertNotNull($order->reference_number);
        $this->assertStringStartsWith('REM-', $order->reference_number);
    }

    public function test_throws_when_purpose_code_invalid(): void
    {
        $this->expectException(RemittancePurposeRequiredException::class);

        $this->remittance->create(new CreateRemittanceDto(
            corridorId: 'x', senderUserId: 'x', senderCountry: 'USA',
            senderFullName: 'John', senderPhone: '123', beneficiaryId: 'x',
            sourceAmount: 100, sourceCurrency: 'USD', payoutMethod: 'wallet',
            purposeCode: 'INVALID', sourceOfFundsDeclaration: 'Savings',
        ));
    }

    public function test_throws_when_source_of_funds_missing(): void
    {
        $this->expectException(RemittanceSourceOfFundsRequiredException::class);

        $this->remittance->create(new CreateRemittanceDto(
            corridorId: 'x', senderUserId: 'x', senderCountry: 'USA',
            senderFullName: 'John', senderPhone: '123', beneficiaryId: 'x',
            sourceAmount: 100, sourceCurrency: 'USD', payoutMethod: 'wallet',
            purposeCode: 'FAMILY_SUPPORT', sourceOfFundsDeclaration: '',
        ));
    }

    public function test_throws_when_beneficiary_not_found_on_create(): void
    {
        $corridor = $this->seedCorridor();
        $user = $this->createUser('01ARremSenderTestUser0002', '12025550002');

        $this->expectException(RemittanceBeneficiaryNotFoundException::class);

        $this->remittance->create(new CreateRemittanceDto(
            corridorId: $corridor->id, senderUserId: $user->id, senderCountry: 'USA',
            senderFullName: 'Jane Doe', senderPhone: '12025550002',
            beneficiaryId: 'nonexistent',
            sourceAmount: 100000, sourceCurrency: 'USD', payoutMethod: 'wallet',
            purposeCode: 'FAMILY_SUPPORT', sourceOfFundsDeclaration: 'Savings',
        ));
    }

    public function test_can_screen_remittance(): void
    {
        $this->seedFullOrder();

        $order = $this->remittance->screen('01ARremOrderTestUser00001', true);

        $this->assertEquals(RemittanceStatus::AWAITING_PAYMENT->value, $order->status);
        $this->assertEquals('passed', $order->compliance_result);
    }

    public function test_can_screen_remittance_failed(): void
    {
        $this->seedFullOrder();

        $order = $this->remittance->screen('01ARremOrderTestUser00001', false);

        $this->assertEquals(RemittanceStatus::SCREENING_FAILED->value, $order->status);
        $this->assertEquals('failed', $order->compliance_result);
    }

    public function test_can_quote_remittance(): void
    {
        $this->seedFullOrder();
        $this->remittance->screen('01ARremOrderTestUser00001', true);
        $this->seedFxRate();

        $order = $this->remittance->quote('01ARremOrderTestUser00001');

        $this->assertEquals(RemittanceStatus::QUOTED->value, $order->status);
        $this->assertGreaterThan(0, $order->target_amount);
        $this->assertGreaterThan(0, $order->fx_rate_applied);
        $this->assertNotNull($order->fx_quote_id);
        $this->assertGreaterThan(0, $order->total_cost);
    }

    public function test_can_complete_remittance(): void
    {
        $this->seedFullOrder();
        $this->remittance->screen('01ARremOrderTestUser00001', true);
        $this->seedFxRate();
        $this->remittance->quote('01ARremOrderTestUser00001');
        $this->remittance->confirmPaidIn('01ARremOrderTestUser00001', 100000);

        $order = $this->remittance->complete('01ARremOrderTestUser00001');

        $this->assertEquals(RemittanceStatus::COMPLETED->value, $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_can_fail_remittance(): void
    {
        $this->seedFullOrder();

        $order = $this->remittance->fail('01ARremOrderTestUser00001', 'Insufficient funds');

        $this->assertEquals(RemittanceStatus::FAILED->value, $order->status);
        $this->assertEquals('Insufficient funds', $order->failure_reason);
    }

    public function test_can_refund_remittance(): void
    {
        $this->seedFullOrder();

        $order = $this->remittance->refund('01ARremOrderTestUser00001', 'Customer requested');

        $this->assertEquals(RemittanceStatus::REFUNDED->value, $order->status);
        $this->assertEquals('Customer requested', $order->refund_reason);
    }

    /* ──── Seed Helpers ──── */

    private function createUser(string $id, string $phone = '963900000000'): User
    {
        $user = new User();
        $user->id = $id;
        $user->phone = $phone;
        $user->status = 'active';
        $user->save();
        return $user;
    }

    private function seedCorridor(): Corridor
    {
        return $this->corridors->create(new CreateCorridorDto(
            name: 'USA → Syria',
            sourceCountry: 'USA',
            sourceCurrency: 'USD',
            fixedSpreadPct: 1.5,
            minAmount: 10000,
            maxAmount: 10000000,
            supportedPayoutMethods: ['wallet', 'agent', 'bank'],
        ));
    }

    private function seedCorridorAndBeneficiary(): array
    {
        $sender = $this->createUser('01ARremSenderTestUser0001', '12025550001');
        $benefUser = $this->createUser('01ARremBenefUserForTest1', '963944000100');

        $corridor = $this->seedCorridor();

        $benef = $this->beneficiaries->register(new RegisterBeneficiaryDto(
            userId: $benefUser->id,
            fullNameAr: 'محمود علي',
            phone: '963944000100',
            relationship: 'family',
        ));
        $this->beneficiaries->completeKyc($benef->id);

        RemittanceOrder::create([
            'id' => '01ARremOrderTestUser00001',
            'corridor_id' => $corridor->id,
            'sender_user_id' => $sender->id,
            'sender_country' => 'USA',
            'sender_full_name' => 'John Doe',
            'sender_phone' => '12025550001',
            'beneficiary_id' => $benef->id,
            'source_amount' => 20000,
            'source_currency' => 'USD',
            'target_amount' => 0,
            'target_currency' => 'SYP',
            'fx_rate_applied' => 0,
            'fee_amount_in_source' => 0,
            'fee_amount_in_target' => 0,
            'total_cost' => 0,
            'payout_method' => 'wallet',
            'purpose_code' => 'FAMILY_SUPPORT',
            'source_of_funds_declaration' => 'Savings',
            'status' => RemittanceStatus::SCREENING->value,
            'reference_number' => 'REM-TEST00001',
        ]);

        return [
            'sender' => $sender,
            'corridor' => $corridor,
            'beneficiary' => $benef,
        ];
    }

    private function seedFullOrder(): array
    {
        return $this->seedCorridorAndBeneficiary();
    }

    private function seedFxRate(): void
    {
        $this->fxRates->create(new CreateFxRateDto(
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            midRate: 13100,
            rateType: 'cbs_official',
            source: 'CBS',
        ));
    }
}
