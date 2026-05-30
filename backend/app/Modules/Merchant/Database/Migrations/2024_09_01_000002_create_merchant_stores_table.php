<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_stores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('merchant_id');
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('phone', 20)->nullable();
            $table->string('governorate', 50);
            $table->string('city', 50);
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchants');
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_stores');
    }
};
