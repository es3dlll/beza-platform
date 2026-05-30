<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('business_name');
            $table->string('trade_license')->nullable();
            $table->string('agent_type', 30)->default('retail');
            $table->string('status', 20)->default('pending');
            $table->string('governorate', 50);
            $table->string('city', 50);
            $table->string('area', 50)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->bigInteger('daily_cash_in_limit')->default(50000000);
            $table->bigInteger('daily_cash_out_limit')->default(20000000);
            $table->bigInteger('max_commission_per_txn')->default(500000);
            $table->decimal('commission_rate', 5, 2)->default(0.50);
            $table->string('wallet_id', 26)->nullable();
            $table->string('phone', 20);
            $table->string('alt_phone', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->ulid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('governorate');
            $table->index('status');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
