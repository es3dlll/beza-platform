<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('card_type', 20);
            $table->string('status', 20)->default('pending');
            $table->string('cardholder_name', 100);
            $table->string('card_number_last4', 4);
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('daily_limit')->default(1000000);
            $table->bigInteger('weekly_limit')->default(5000000);
            $table->bigInteger('monthly_limit')->default(15000000);
            $table->bigInteger('daily_used')->default(0);
            $table->bigInteger('weekly_used')->default(0);
            $table->bigInteger('monthly_used')->default(0);
            $table->bigInteger('single_txn_limit')->default(500000);
            $table->boolean('is_virtual')->default(false);
            $table->boolean('international_enabled')->default(false);
            $table->boolean('atm_enabled')->default(false);
            $table->boolean('contactless_enabled')->default(true);
            $table->boolean('ecommerce_enabled')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
