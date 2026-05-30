<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('savings_account_id');
            $table->ulid('savings_goal_id')->nullable();
            $table->ulid('user_id');
            $table->string('type', 30);
            $table->bigInteger('amount');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('currency', 3)->default('SYP');
            $table->text('description')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamps();

            $table->foreign('savings_account_id')->references('id')->on('savings_accounts');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
