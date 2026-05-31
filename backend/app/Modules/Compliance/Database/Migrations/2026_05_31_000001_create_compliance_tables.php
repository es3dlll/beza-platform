<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_id', 40)->unique();
            $table->string('transaction_id', 40)->nullable();
            $table->string('account_id', 40)->nullable();
            $table->integer('risk_score')->default(0);
            $table->string('status', 30)->default('OPEN');
            $table->string('severity', 20)->default('INFO');
            $table->json('triggered_rules')->nullable();
            $table->json('context')->nullable();
            $table->string('reviewer_id', 40)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('resolution', 50)->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('compliance_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_id', 40)->unique();
            $table->string('case_id', 40)->nullable()->index();
            $table->string('severity', 20);
            $table->text('message');
            $table->string('rule_id', 40)->nullable();
            $table->integer('risk_score')->default(0);
            $table->json('context')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('compliance_sanction_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('device_fingerprint', 100)->nullable();
            $table->string('source', 50);
            $table->string('match_type', 30);
            $table->string('country', 10)->nullable();
            $table->string('sanction_ref', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('compliance_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 40)->unique();
            $table->string('rule_id', 40);
            $table->integer('risk_score');
            $table->json('context');
            $table->string('action', 50);
            $table->bigInteger('timestamp');
            $table->boolean('irreversible')->default(true);
            $table->timestamps();
        });

        Schema::create('compliance_rule_configs', function (Blueprint $table) {
            $table->id();
            $table->string('rule_id', 40)->unique();
            $table->string('description');
            $table->string('evaluation_type', 30);
            $table->integer('threshold');
            $table->string('action', 30);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_rule_configs');
        Schema::dropIfExists('compliance_audit_trails');
        Schema::dropIfExists('compliance_sanction_lists');
        Schema::dropIfExists('compliance_alerts');
        Schema::dropIfExists('compliance_cases');
    }
};
