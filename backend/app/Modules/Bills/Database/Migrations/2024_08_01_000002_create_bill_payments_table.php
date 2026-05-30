<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('bill_provider_id');
            $table->string('account_number', 50);
            $table->string('account_name', 100)->nullable();
            $table->string('biller_reference', 100)->nullable();
            $table->string('period', 30)->nullable();
            $table->date('due_date')->nullable();
            $table->integer('amount_due')->default(0);
            $table->integer('amount_paid')->default(0);
            $table->integer('fee_amount')->default(0);
            $table->integer('total_debited')->default(0);
            $table->string('status', 30)->default('pending_inquiry');
            $table->string('failure_reason')->nullable();
            $table->string('refund_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('bill_provider_id')->references('id')->on('bill_providers');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
