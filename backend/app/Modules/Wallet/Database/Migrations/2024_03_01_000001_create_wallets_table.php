<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('available_balance')->default(0);
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->integer('kyc_tier_required')->default(1);
            $table->bigInteger('daily_limit')->default(5000000);
            $table->bigInteger('daily_used')->default(0);
            $table->timestamp('daily_reset_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ledger_account_id', 26)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'currency']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
