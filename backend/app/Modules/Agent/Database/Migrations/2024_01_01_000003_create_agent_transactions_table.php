<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('agent_id')->index();
            $table->string('type', 20)->comment('cash_in|cash_out|commission|settlement|adjustment');
            $table->string('status', 20)->default('completed')->comment('pending|completed|failed|reversed');
            $table->string('customer_wallet_id')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('fee')->default(0);
            $table->bigInteger('commission_amount')->default(0);
            $table->integer('commission_rate_bps')->nullable();
            $table->date('settlement_date')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('transaction_id')->nullable()->comment('FK to financial_transactions');
            $table->string('description')->nullable();
            $table->string('description_ar')->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->timestamps();
            $table->index(['agent_id', 'type', 'created_at']);
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
    }
};
