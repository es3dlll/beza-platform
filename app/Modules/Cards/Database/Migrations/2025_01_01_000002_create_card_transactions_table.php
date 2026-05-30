<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('card_id');
            $table->ulid('user_id');
            $table->string('type', 30);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('merchant_name', 100)->nullable();
            $table->string('merchant_category', 50)->nullable();
            $table->string('merchant_country', 2)->nullable();
            $table->boolean('is_international')->default(false);
            $table->string('channel', 30)->nullable();
            $table->string('decline_reason')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->foreign('card_id')->references('id')->on('cards');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
