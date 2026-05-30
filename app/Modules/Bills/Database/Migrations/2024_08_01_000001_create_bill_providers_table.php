<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('category', 30);
            $table->string('account_label', 100);
            $table->string('account_format_regex', 100)->nullable();
            $table->json('supported_account_types')->nullable();
            $table->decimal('fee_percentage', 5, 2)->default(0.5);
            $table->integer('fee_min_syp')->default(100);
            $table->integer('fee_max_syp')->default(2000);
            $table->boolean('is_active')->default(true);
            $table->json('integration_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_providers');
    }
};
