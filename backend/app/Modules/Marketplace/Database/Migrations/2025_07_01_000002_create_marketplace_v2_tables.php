<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id');
            $table->ulid('vendor_id');
            $table->bigInteger('amount');
            $table->bigInteger('balance');
            $table->string('code', 30)->unique();
            $table->string('pin', 10)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->string('message', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('delivery_method', 20)->default('sms');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('discount_type', 10);
            $table->bigInteger('discount_value');
            $table->bigInteger('min_order_amount')->default(0);
            $table->integer('max_uses')->default(100);
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_ledger', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->integer('points');
            $table->string('action', 30);
            $table->string('reference_type', 30)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_ledger');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('gift_cards');
    }
};
