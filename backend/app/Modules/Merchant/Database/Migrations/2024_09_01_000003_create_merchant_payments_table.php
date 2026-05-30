<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('merchant_id');
            $table->ulid('store_id')->nullable();
            $table->ulid('payer_user_id');
            $table->string('qr_code', 64);
            $table->string('qr_type', 20)->default('static');
            $table->bigInteger('amount');
            $table->bigInteger('mdr_fee')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('failure_reason')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchants');
            $table->foreign('store_id')->references('id')->on('merchant_stores');
            $table->index('payer_user_id');
            $table->index('status');
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_payments');
    }
};
