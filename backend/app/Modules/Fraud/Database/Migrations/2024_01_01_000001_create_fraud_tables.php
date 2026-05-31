<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('type', 30)->comment('velocity|amount|device|behavior|geo');
            $table->string('category', 30)->comment('pre_check|post_monitor');
            $table->string('action', 20)->default('flag')->comment('allow|flag|block|hold');
            $table->string('scope', 30)->comment('wallet|device|ip|global');
            $table->string('metric', 40)->comment('txn_count_1h|txn_amount_24h|device_count|geo_velocity');
            $table->bigInteger('threshold');
            $table->integer('score_impact')->default(0);
            $table->string('kyc_tier_min', 2)->default('t0');
            $table->integer('priority')->default(0);
            $table->string('time_window_minutes')->nullable()->comment('For velocity rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'is_active']);
        });

        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wallet_id')->index();
            $table->string('fingerprint_hash', 64)->index();
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 30)->nullable()->comment('mobile|web|ussd');
            $table->string('app_version')->nullable();
            $table->string('os', 30)->nullable();
            $table->string('screen_resolution')->nullable();
            $table->integer('trust_score')->default(500)->comment('0-1000, starts neutral');
            $table->integer('txn_count')->default(0);
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['wallet_id', 'fingerprint_hash']);
        });

        Schema::create('fraud_decisions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wallet_id')->index();
            $table->string('rule_id')->nullable();
            $table->string('device_fingerprint_id')->nullable();
            $table->string('action', 20)->comment('allow|flag|block|hold');
            $table->integer('score_before');
            $table->integer('score_after');
            $table->integer('score_impact');
            $table->string('reason');
            $table->string('reason_ar');
            $table->string('context_type', 30)->nullable()->comment('transaction|login|registration');
            $table->string('context_id')->nullable();
            $table->string('reference_id')->nullable()->comment('Points to previous decision if override');
            $table->string('resolved_by')->nullable();
            $table->string('resolution', 30)->nullable()->comment('confirmed_fraud|false_positive|overridden');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['context_type', 'context_id']);
        });

        Schema::create('velocity_counters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wallet_id')->index();
            $table->string('rule_id');
            $table->string('window_key', 64)->comment('wallet:rule:YYYYMMDDHH');
            $table->integer('count')->default(0);
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->timestamps();
            $table->unique(['wallet_id', 'rule_id', 'window_key']);
            $table->index(['window_key', 'count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('velocity_counters');
        Schema::dropIfExists('fraud_decisions');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('fraud_rules');
    }
};
