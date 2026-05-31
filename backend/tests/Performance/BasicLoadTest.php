<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Compliance\Models\ComplianceRuleConfig;
use App\Modules\Compliance\Services\FraudDetectionEngine;
use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\Wallet;
use App\Modules\Identity\Services\WalletService;
use App\Modules\Merchant\Services\MerchantEngine;
use App\Modules\Remittance\Services\RemittanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BasicLoadTest extends TestCase
{
    use RefreshDatabase;

    private const USER_COUNT = 10;
    private const TRANSACTIONS_PER_USER = 100;
    private const MAX_P95_MS = 500;
    private const MAX_ERROR_RATE = 0.001;

    private WalletService $walletService;
    private MerchantEngine $merchantEngine;
    private RemittanceEngine $remitEngine;
    private FraudDetectionEngine $fraudEngine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = $this->app->make(WalletService::class);
        $this->merchantEngine = $this->app->make(MerchantEngine::class);
        $this->remitEngine = $this->app->make(RemittanceEngine::class);
        $this->fraudEngine = $this->app->make(FraudDetectionEngine::class);

        // إعداد بيانات الامتثال
        ComplianceRuleConfig::create([
            'rule_id' => 'RULE-LOAD-001',
            'description' => 'Velocity load test rule',
            'evaluation_type' => 'velocity',
            'threshold' => 50000,
            'action' => 'review',
            'active' => true,
        ]);
    }

    /** @test محاكاة 100 مستخدم و 1000 معاملة وقياس الأداء */
    public function test_load_100_users_1000_transactions(): void
    {
        $users = $this->seedUsers(self::USER_COUNT);
        $merchant = $this->seedMerchant();

        $timings = [];
        $errors = 0;
        $total = 0;

        foreach ($users as $user) {
            for ($i = 0; $i < self::TRANSACTIONS_PER_USER; $i++) {
                $total++;
                $start = hrtime(true);

                try {
                    $this->executeFullCycle($user, $merchant);
                    $elapsed = (hrtime(true) - $start) / 1_000_000;
                    $timings[] = $elapsed;
                } catch (\Throwable $e) {
                    $errors++;
                    $elapsed = (hrtime(true) - $start) / 1_000_000;
                    $timings[] = $elapsed;
                }
            }
        }

        $metrics = $this->calculateMetrics($timings);
        $errorRate = $errors / max($total, 1);

        echo "\n========== LOAD TEST RESULTS ==========\n";
        echo "Users: " . self::USER_COUNT . "\n";
        echo "Transactions: {$total}\n";
        echo "Errors: {$errors} (" . round($errorRate * 100, 4) . "%)\n";
        echo "Avg time: " . round($metrics['avg'], 2) . " ms\n";
        echo "P50 time: " . round($metrics['p50'], 2) . " ms\n";
        echo "P95 time: " . round($metrics['p95'], 2) . " ms\n";
        echo "P99 time: " . round($metrics['p99'], 2) . " ms\n";
        echo "Min time: " . round($metrics['min'], 2) . " ms\n";
        echo "Max time: " . round($metrics['max'], 2) . " ms\n";
        echo "=======================================\n";

        $this->assertLessThanOrEqual(
            self::MAX_P95_MS,
            $metrics['p95'],
            "P95 response time {$metrics['p95']}ms exceeds limit " . self::MAX_P95_MS . "ms",
        );

        $this->assertLessThanOrEqual(
            self::MAX_ERROR_RATE,
            $errorRate,
            "Error rate " . round($errorRate * 100, 4) . "% exceeds limit " . (self::MAX_ERROR_RATE * 100) . "%",
        );
    }

    private function executeFullCycle(User $user, $merchant): void
    {
        $remittance = $this->remitEngine->initiate(
            idempotencyKey: 'load-' . Str::ulid()->toBase32(),
            senderId: $user->id,
            recipientName: 'Load Test Recipient',
            recipientPhone: '963900000990',
            recipientCountry: 'AE',
            fromCurrency: 'SYP',
            toCurrency: 'AED',
            sourceAmount: random_int(10000, 200000),
        );

        $this->fraudEngine->evaluateTransaction(new TransactionCompleted(
            transactionId: $remittance->remittance_id,
            accountId: $user->id,
            recipientId: $user->id,
            amount: $remittance->source_amount,
            currency: 'SYP',
            deviceFingerprint: 'load-device-' . Str::random(8),
            country: 'SY',
            dailyTransactionCount: random_int(1, 10),
            isNewDevice: false,
            isUntrustedDevice: false,
            isNewRecipient: false,
            recipientRepeatAmount: 0,
            timestamp: now()->getTimestamp(),
        ));
    }

    private function seedUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $user = User::create([
                'id' => Str::ulid()->toBase32(),
                'phone' => '9639000' . str_pad((string) (200 + $i), 4, '0', STR_PAD_LEFT),
                'name' => "Load User {$i}",
                'name_ar' => "مستخدم تحميل {$i}",
                'status' => 'active',
                'kyc_tier' => 't2',
            ]);

            $wallet = $this->walletService->createWallet($user->id, 'SYP');
            $wallet->credit(50000000);

            $users[] = $user;
        }
        return $users;
    }

    private function seedMerchant(): mixed
    {
        $owner = User::create([
            'id' => Str::ulid()->toBase32(),
            'phone' => '963900000999',
            'name' => 'Load Test Merchant Owner',
            'name_ar' => 'مالك تاجر اختبار التحميل',
            'status' => 'active',
            'kyc_tier' => 't2',
        ]);

        return $this->merchantEngine->onboard([
            'business_name' => 'Load Test Shop',
            'owner_id' => $owner->id,
            'phone' => '963900000888',
            'category' => 'goods_general',
            'settlement_cycle' => 'DAILY',
        ]);
    }

    private function calculateMetrics(array $timings): array
    {
        $count = count($timings);
        if ($count === 0) {
            return ['avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0, 'min' => 0, 'max' => 0];
        }

        sort($timings);

        return [
            'avg' => array_sum($timings) / $count,
            'p50' => $timings[(int) floor($count * 0.50)],
            'p95' => $timings[(int) floor($count * 0.95)],
            'p99' => $timings[(int) floor($count * 0.99)],
            'min' => $timings[0],
            'max' => $timings[$count - 1],
        ];
    }
}
