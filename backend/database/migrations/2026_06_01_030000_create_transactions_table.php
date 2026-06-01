<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_wallet_id')->constrained('wallets');
            $table->foreignId('receiver_wallet_id')->constrained('wallets');
            $table->bigInteger('amount');
            $table->string('currency', 3);
            $table->string('type');
            $table->string('status');
            $table->string('idempotency_key')->unique()->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
