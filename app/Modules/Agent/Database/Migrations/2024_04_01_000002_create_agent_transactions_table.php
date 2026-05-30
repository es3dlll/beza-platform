<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('agent_id');
            $table->ulid('user_wallet_id')->comment('Customer wallet');
            $table->string('type', 20);
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            $table->bigInteger('commission')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('cfe_transaction_id', 26)->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('reference_id', 50)->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents');
            $table->index(['agent_id', 'created_at']);
            $table->unique(['agent_id', 'reference_id']);
            $table->unique('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
    }
};
