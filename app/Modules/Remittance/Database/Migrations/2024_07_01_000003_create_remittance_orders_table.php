<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittance_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('corridor_id');
            $table->ulid('sender_user_id');
            $table->string('sender_country', 3);
            $table->string('sender_full_name', 100);
            $table->string('sender_phone', 20);
            $table->string('sender_id_document', 100)->nullable();
            $table->ulid('beneficiary_id');
            $table->bigInteger('source_amount');
            $table->string('source_currency', 3);
            $table->bigInteger('target_amount');
            $table->string('target_currency', 3)->default('SYP');
            $table->decimal('fx_rate_applied', 16, 6);
            $table->string('fx_quote_id', 26)->nullable();
            $table->bigInteger('fee_amount_in_source')->default(0);
            $table->bigInteger('fee_amount_in_target')->default(0);
            $table->bigInteger('total_cost');
            $table->string('payout_method', 20);
            $table->ulid('payout_wallet_id')->nullable();
            $table->string('payout_agent_id', 26)->nullable();
            $table->string('payout_bank_account')->nullable();
            $table->string('purpose_code', 10);
            $table->string('source_of_funds_declaration')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('compliance_result', 20)->nullable();
            $table->ulid('compliance_case_id')->nullable();
            $table->string('reference_number', 12)->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamp('paid_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('corridor_id')->references('id')->on('corridors');
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries');
            $table->index('sender_user_id');
            $table->index('status');
            $table->index('reference_number')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittance_orders');
    }
};
