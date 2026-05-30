<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('full_name_ar', 100);
            $table->string('full_name_en', 100)->nullable();
            $table->string('phone', 20);
            $table->string('national_id', 50)->nullable();
            $table->string('relationship', 20);
            $table->string('governorate', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('address')->nullable();
            $table->boolean('kyc_completed')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
