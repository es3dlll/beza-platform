<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gov_payment_inquiries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('provider_id');
            $table->string('service_code', 50);
            $table->string('account_number', 100);
            $table->string('account_name', 100)->nullable();
            $table->bigInteger('amount_due');
            $table->bigInteger('fee')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('provider_id')->references('id')->on('gov_service_providers');
        });
    }

    public function down(): void { Schema::dropIfExists('gov_payment_inquiries'); }
};
