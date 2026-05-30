<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_finance_webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('app_id');
            $table->string('url', 500);
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('app_id')->references('id')->on('open_finance_apps');
        });

        Schema::create('open_finance_webhook_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('webhook_id');
            $table->string('event', 50);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
            $table->foreign('webhook_id')->references('id')->on('open_finance_webhooks');
        });

        Schema::create('open_finance_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('consent_id');
            $table->ulid('user_id');
            $table->string('payment_type', 30);
            $table->string('recipient_id', 50);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('description')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->string('cfe_transaction_id', 26)->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('consent_id')->references('id')->on('open_finance_consents');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_finance_payments');
        Schema::dropIfExists('open_finance_webhook_deliveries');
        Schema::dropIfExists('open_finance_webhooks');
    }
};
