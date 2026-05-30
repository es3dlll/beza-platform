<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gov_collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('provider_id');
            $table->ulid('inquiry_id')->nullable();
            $table->string('service_code', 50);
            $table->string('account_number', 100);
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('channel', 30)->default('mobile');
            $table->string('receipt_number')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('provider_id')->references('id')->on('gov_service_providers');
            $table->index('status');
            $table->index('service_code');
        });
    }

    public function down(): void { Schema::dropIfExists('gov_collections'); }
};
