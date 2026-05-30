<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('float_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('float_account_id');
            $table->string('type', 30);
            $table->bigInteger('amount');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamps();

            $table->foreign('float_account_id')->references('id')->on('float_accounts')->onDelete('cascade');
            $table->index(['float_account_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('float_transactions');
    }
};
