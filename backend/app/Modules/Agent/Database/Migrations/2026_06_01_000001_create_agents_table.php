<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->integer('verification_level')->default(0);
            $table->bigInteger('available_balance_fils')->default(0);
            $table->bigInteger('daily_liquidity_limit_fils')->default(1_000_000_000);
            $table->string('region')->nullable();
            $table->float('rating')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
