<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('rule_type', 50);
            $table->text('description')->nullable();
            $table->json('parameters');
            $table->integer('risk_score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('severity', 20)->default('medium');
            $table->timestamps();

            $table->index('rule_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_rules');
    }
};
