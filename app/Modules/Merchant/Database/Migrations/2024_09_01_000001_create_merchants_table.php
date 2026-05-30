<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('business_name', 150);
            $table->string('business_name_ar', 150);
            $table->string('commercial_registration', 50)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('governorate', 50);
            $table->string('city', 50);
            $table->text('address')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->bigInteger('monthly_volume_syp')->default(0);
            $table->decimal('mdr_percentage', 5, 2)->default(1.0);
            $table->integer('mdr_min_syp')->default(50);
            $table->integer('mdr_max_syp')->default(5000);
            $table->bigInteger('max_txn_amount')->default(500000);
            $table->boolean('is_micro_merchant')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('status');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
