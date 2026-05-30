<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('wallet_id');
            $table->string('type', 30);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->string('cfe_transaction_id', 26)->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('description')->nullable();
            $table->ulid('related_wallet_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index(['reference_type', 'reference_id']);
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
