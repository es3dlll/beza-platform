<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('trigger_type', 50);
            $table->string('trigger_value', 100)->nullable();
            $table->decimal('rate', 5, 2);
            $table->bigInteger('min_amount')->default(0);
            $table->bigInteger('max_cashback')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('tier_requirement', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('trigger_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_rules');
    }
};
