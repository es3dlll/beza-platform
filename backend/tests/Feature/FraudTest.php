<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Fraud\Exceptions\TransactionBlockedException;
use App\Modules\Fraud\Models\DeviceFingerprint;
use App\Modules\Fraud\Models\FraudDecision;
use App\Modules\Fraud\Models\FraudRule;
use App\Modules\Fraud\Services\DeviceFingerprintService;
use App\Modules\Fraud\Services\FraudGuard;
use App\Modules\Fraud\Services\ScoringPipeline;
use App\Modules\Fraud\Services\VelocityService;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FraudTest extends TestCase
{
    use RefreshDatabase;

    private FraudGuard $fraudGuard;
    private VelocityService $velocityService;
    private ScoringPipeline $scoringPipeline;
    private DeviceFingerprintService $deviceFingerprintService;
    private string $walletId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LedgerSeeder::class);
        $this->fraudGuard = $this->app->make(FraudGuard::class);
        $this->velocityService = $this->app->make(VelocityService::class);
        $this->scoringPipeline = $this->app->make(ScoringPipeline::class);
        $this->deviceFingerprintService = $this->app->make(DeviceFingerprintService::class);
        $this->walletId = Str::ulid()->toBase32();
    }

    public function test_velocity_limit_blocks_excess_txns(): void
    {
        $rule = FraudRule::create([
            'name' => 'Max 3 txns per hour',
            'name_ar' => 'الحد الأقصى 3 معاملات في الساعة',
            'type' => 'velocity',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'txn_count_1h',
            'threshold' => 3,
            'score_impact' => 200,
            'time_window_minutes' => 60,
            'kyc_tier_min' => 't0',
            'priority' => 100,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->velocityService->checkAndIncrement($this->walletId, $rule);
        }

        $allowed = $this->velocityService->checkAndIncrement($this->walletId, $rule);
        $this->assertFalse($allowed);
    }

    public function test_velocity_allows_within_threshold(): void
    {
        $rule = FraudRule::create([
            'name' => 'Max 5 txns per hour',
            'name_ar' => 'الحد الأقصى 5 معاملات في الساعة',
            'type' => 'velocity',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'txn_count_1h',
            'threshold' => 5,
            'score_impact' => 100,
            'time_window_minutes' => 60,
            'kyc_tier_min' => 't0',
        ]);

        $allowed = $this->velocityService->checkAndIncrement($this->walletId, $rule);
        $this->assertTrue($allowed);
    }

    public function test_device_hash_is_consistent(): void
    {
        $data = ['user_agent' => 'Mozilla/5.0', 'os' => 'Android 13', 'screen_resolution' => '1080x2400', 'app_version' => '1.0.0', 'ip_address' => '10.0.0.1'];

        $hash1 = $this->deviceFingerprintService->computeHash($data);
        $hash2 = $this->deviceFingerprintService->computeHash($data);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));
    }

    public function test_device_mismatch_different_hashes(): void
    {
        $hash1 = $this->deviceFingerprintService->computeHash(['user_agent' => 'Chrome', 'os' => 'Android']);
        $hash2 = $this->deviceFingerprintService->computeHash(['user_agent' => 'Firefox', 'os' => 'iOS']);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_register_device_fingerprint(): void
    {
        $device = $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'Mozilla/5.0',
            'os' => 'iOS 17',
            'device_type' => 'mobile',
            'app_version' => '2.0.0',
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertEquals($this->walletId, $device->wallet_id);
        $this->assertEquals(500, $device->trust_score);
        $this->assertEquals(1, $device->txn_count);
    }

    public function test_device_trust_score_updates(): void
    {
        $device = $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'TestAgent',
            'device_type' => 'web',
        ]);

        $this->deviceFingerprintService->adjustTrustScore($this->walletId, $device->fingerprint_hash, 50);
        $device->refresh();
        $this->assertEquals(550, $device->trust_score);

        $this->deviceFingerprintService->adjustTrustScore($this->walletId, $device->fingerprint_hash, -200);
        $device->refresh();
        $this->assertEquals(350, $device->trust_score);
    }

    public function test_device_trusted_at_700(): void
    {
        $device = $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'TrustedDevice',
        ]);

        $this->deviceFingerprintService->adjustTrustScore($this->walletId, $device->fingerprint_hash, 200);
        $device->refresh();
        $this->assertTrue($device->is_trusted);
    }

    public function test_scoring_pipeline_aggregates_correctly(): void
    {
        FraudRule::create([
            'name' => 'High amount',
            'name_ar' => 'مبلغ كبير',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'flag',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 500000,
            'score_impact' => 100,
            'is_active' => true,
        ]);

        FraudRule::create([
            'name' => 'New device',
            'name_ar' => 'جهاز جديد',
            'type' => 'device',
            'category' => 'pre_check',
            'action' => 'hold',
            'scope' => 'wallet',
            'metric' => 'device_trust',
            'threshold' => 300,
            'score_impact' => 150,
            'is_active' => true,
        ]);

        $rules = FraudRule::where('is_active', true)->get();

        $result = $this->scoringPipeline->evaluate(
            amount: 1000000,
            deviceTrustScore: 200,
            velocityCount: 0,
            deviceCount: 1,
            rules: $rules,
        );

        $this->assertGreaterThanOrEqual(500, $result->getScore());
        $this->assertContains($result->getAction(), ['allow', 'flag', 'hold', 'block']);
    }

    public function test_hold_action_in_fraud_guard(): void
    {
        FraudRule::create([
            'name' => 'Large txn hold',
            'name_ar' => 'تعليق المعاملات الكبيرة',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'hold',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 100000,
            'score_impact' => 100,
            'priority' => 100,
        ]);

        $decision = $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 500000,
            deviceData: ['user_agent' => 'Test'],
        );

        $this->assertNotNull($decision->id);
    }

    public function test_block_action_prevents_transaction(): void
    {
        FraudRule::create([
            'name' => 'Block huge txn',
            'name_ar' => 'حظر المعاملات الضخمة',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 10000,
            'score_impact' => 300,
            'priority' => 100,
        ]);

        $this->expectException(TransactionBlockedException::class);

        $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 50000000,
            deviceData: ['user_agent' => 'Suspicious'],
        );
    }

    public function test_fraud_decision_worm_integrity(): void
    {
        FraudRule::create([
            'name' => 'Test rule',
            'name_ar' => 'قاعدة اختبار',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'flag',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 1000,
            'score_impact' => 50,
            'priority' => 10,
        ]);

        $first = $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 5000,
            deviceData: ['user_agent' => 'Test'],
        );

        $second = $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 5000,
            deviceData: ['user_agent' => 'Test'],
        );

        $this->assertNotEquals($first->id, $second->id);
        $this->assertEquals(2, FraudDecision::where('wallet_id', $this->walletId)->count());
    }

    public function test_tier_specific_rules_apply(): void
    {
        FraudRule::create([
            'name' => 'T1 daily limit 500K',
            'name_ar' => 'حد T1 اليومي 500 ألف',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 50000000,
            'score_impact' => 200,
            'kyc_tier_min' => 't0',
            'priority' => 50,
        ]);

        $decision = $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 100000,
            deviceData: ['user_agent' => 'Test'],
            kycTier: 't1',
        );

        $this->assertNotNull($decision->id);
    }

    public function test_post_monitor_updates_trust_score(): void
    {
        $device = $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'Positive Behavior',
            'device_type' => 'mobile',
        ]);

        $oldScore = $device->trust_score;

        $this->fraudGuard->postMonitor(
            walletId: $this->walletId,
            transactionId: Str::ulid()->toBase32(),
            amount: 50000,
            deviceData: ['user_agent' => 'Positive Behavior', 'device_type' => 'mobile'],
        );

        $device->refresh();
        $this->assertGreaterThan($oldScore, $device->trust_score);
    }

    public function test_pre_check_respects_kyc_tier(): void
    {
        FraudRule::create([
            'name' => 'T3 only - high threshold',
            'name_ar' => 'T3 فقط - حد مرتفع',
            'type' => 'amount',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'txn_amount',
            'threshold' => 100000,
            'score_impact' => 100,
            'kyc_tier_min' => 't2',
            'priority' => 100,
        ]);

        $decision = $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 200000,
            kycTier: 't1',
        );

        $this->assertEquals('allow', $decision->action);
    }

    public function test_fraud_decision_resolution(): void
    {
        $decision = FraudDecision::create([
            'wallet_id' => $this->walletId,
            'action' => 'block',
            'score_before' => 500,
            'score_after' => 700,
            'score_impact' => 200,
            'reason' => 'Test',
            'reason_ar' => 'اختبار',
        ]);

        $decision->update([
            'resolution' => 'false_positive',
            'resolved_by' => 'admin@beza',
            'resolved_at' => now(),
        ]);

        $this->assertEquals('false_positive', $decision->fresh()->resolution);
    }

    public function test_device_count_tracking(): void
    {
        $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'Device A',
            'device_type' => 'mobile',
        ]);

        $this->deviceFingerprintService->registerOrVerify($this->walletId, [
            'user_agent' => 'Device B',
            'device_type' => 'web',
        ]);

        $count = $this->deviceFingerprintService->getDeviceCount($this->walletId);
        $this->assertEquals(2, $count);
    }

    public function test_multiple_devices_triggers_velocity(): void
    {
        FraudRule::create([
            'name' => 'Max 2 devices',
            'name_ar' => 'الحد الأقصى جهازين',
            'type' => 'device',
            'category' => 'pre_check',
            'action' => 'block',
            'scope' => 'wallet',
            'metric' => 'device_count',
            'threshold' => 2,
            'score_impact' => 200,
            'priority' => 100,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->deviceFingerprintService->registerOrVerify($this->walletId, [
                'user_agent' => "Device {$i}",
                'device_type' => 'mobile',
            ]);
        }

        $this->expectException(TransactionBlockedException::class);

        $this->fraudGuard->preCheck(
            walletId: $this->walletId,
            amount: 50000,
            deviceData: ['user_agent' => 'Device 3', 'device_type' => 'mobile'],
        );
    }
}
