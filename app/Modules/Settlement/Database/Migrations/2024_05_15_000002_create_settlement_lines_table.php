<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('settlement_id');
            $table->string('account_id', 26);
            $table->bigInteger('amount');
            $table->enum('type', ['debit', 'credit']);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('settlement_id')->references('id')->on('settlements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_lines');
    }
};
