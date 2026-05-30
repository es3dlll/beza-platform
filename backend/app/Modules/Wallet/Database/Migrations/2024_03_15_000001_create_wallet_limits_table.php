<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_limits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->integer('kyc_tier');
            $table->string('limit_type', 30);
            $table->string('period', 20);
            $table->bigInteger('max_amount');
            $table->integer('max_count')->nullable();
            $table->string('currency', 3)->default('SYP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kyc_tier', 'limit_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_limits');
    }
};
