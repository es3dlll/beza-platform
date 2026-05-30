<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('fee_type', 50)->unique();
            $table->enum('calculation_type', ['flat', 'percentage', 'tiered', 'max_capped']);
            $table->bigInteger('value')->comment('Amount in minor units (e.g., SYP 500 = 50000)');
            $table->string('currency', 3)->default('SYP');
            $table->string('fee_account_number', 20)->comment('Revenue account in chart of accounts');
            $table->bigInteger('max_cap')->nullable()->comment('Maximum fee in minor units');
            $table->bigInteger('min_amount')->nullable()->comment('Minimum transaction amount to trigger fee');
            $table->boolean('waived_for_tier')->default(false)->comment('Waived for KYC tier 3+');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
