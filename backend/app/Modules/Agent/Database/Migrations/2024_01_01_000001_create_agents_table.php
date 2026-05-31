<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('phone', 20)->unique()->index();
            $table->string('name');
            $table->string('name_ar');
            $table->string('kyc_tier', 2)->default('t0')->comment('t0|t1|t2|t3');
            $table->string('status', 20)->default('pending')->comment('pending|active|suspended|terminated');
            $table->string('id_type')->nullable()->comment('national_id|passport|residency');
            $table->string('id_number')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('address_ar')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'kyc_tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
