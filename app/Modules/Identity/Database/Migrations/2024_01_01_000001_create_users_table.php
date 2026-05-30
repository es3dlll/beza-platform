<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone', 15)->unique();
            $table->string('phone_country_code', 5)->default('963');
            $table->string('email')->nullable()->unique();
            $table->string('pin_hash')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('kyc_tier', 20)->default('tier_1_basic');
            $table->string('locale', 5)->default('ar');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
