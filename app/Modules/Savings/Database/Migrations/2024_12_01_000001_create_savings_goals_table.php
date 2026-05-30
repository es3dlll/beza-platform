<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->bigInteger('target_amount');
            $table->bigInteger('current_amount')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('active');
            $table->date('target_date')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('auto_sweep_enabled')->default(false);
            $table->bigInteger('auto_sweep_amount')->nullable();
            $table->string('auto_sweep_frequency', 20)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
