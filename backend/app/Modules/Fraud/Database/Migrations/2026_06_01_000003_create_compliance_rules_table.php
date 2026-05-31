<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('rule_type');
            $table->json('parameters');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('risk_score_impact')->default(10);
            $table->string('decision')->default('suspend');
            $table->timestamps();
        });

        // Seed default rules
        DB::table('compliance_rules')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'عتبة المبلغ العالي',
                'key' => 'high_amount_threshold',
                'description' => 'المعاملات التي تتجاوز 10 ملايين فلس تخضع للمراجعة',
                'rule_type' => 'amount',
                'parameters' => json_encode(['min_amount_fils' => 10_000_000]),
                'is_active' => true,
                'priority' => 10,
                'risk_score_impact' => 30,
                'decision' => 'suspend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'التحويلات المتكررة قصيرة الزمن',
                'key' => 'rapid_successive_transfers',
                'description' => 'أكثر من 3 تحويلات خلال 10 دقائق',
                'rule_type' => 'frequency',
                'parameters' => json_encode(['max_count' => 3, 'window_minutes' => 10]),
                'is_active' => true,
                'priority' => 20,
                'risk_score_impact' => 40,
                'decision' => 'suspend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'مناطق عالية الخطورة',
                'key' => 'high_risk_region',
                'description' => 'معاملات من مناطق محددة تخضع لتدقيق إضافي',
                'rule_type' => 'region',
                'parameters' => json_encode(['regions' => ['outside_syria', 'border_area']]),
                'is_active' => true,
                'priority' => 15,
                'risk_score_impact' => 25,
                'decision' => 'suspend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'عدم تطابق بيانات الجهاز',
                'key' => 'device_mismatch',
                'description' => 'تغير الجهاز المستخدم مقارنة بآخر معاملة',
                'rule_type' => 'device',
                'parameters' => json_encode(['max_devices_per_day' => 2]),
                'is_active' => true,
                'priority' => 25,
                'risk_score_impact' => 50,
                'decision' => 'reject',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_rules');
    }
};
