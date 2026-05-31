<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id', 32)->unique();
            $table->string('business_name', 255);
            $table->string('owner_id', 32)->index();
            $table->string('phone', 20);
            $table->string('category', 50)->default('goods_general');
            $table->string('settlement_cycle', 20)->default('DAILY');
            $table->integer('commission_bps')->default(100);
            $table->string('status', 20)->default('active');
            $table->string('compliance_level', 20)->default('standard');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id', 32)->unique();
            $table->string('merchant_id', 32)->index();
            $table->bigInteger('amount');
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->text('description');
            $table->string('category', 50)->default('goods_general');
            $table->string('status', 30)->default('DRAFT')->index();
            $table->string('settlement_status', 20)->nullable();
            $table->string('qr_token', 512)->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('merchants');
    }
};
