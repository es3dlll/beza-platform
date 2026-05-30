<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_profit_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->decimal('annual_rate', 5, 2);
            $table->string('calculation_method', 30)->default('daily');
            $table->string('distribution_method', 30)->default('monthly');
            $table->bigInteger('min_balance')->default(0);
            $table->bigInteger('min_duration_days')->default(0);
            $table->decimal('early_withdrawal_penalty_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_profit_rules');
    }
};
