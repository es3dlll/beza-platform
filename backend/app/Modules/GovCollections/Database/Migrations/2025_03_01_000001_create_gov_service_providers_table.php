<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gov_service_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('code', 30)->unique();
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->json('supported_services')->nullable();
            $table->decimal('fee_rate', 5, 2)->default(0);
            $table->bigInteger('min_fee')->default(0);
            $table->bigInteger('max_fee')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('gov_service_providers'); }
};
