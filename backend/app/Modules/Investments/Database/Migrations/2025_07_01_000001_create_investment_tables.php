<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_funds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->bigInteger('min_investment');
            $table->bigInteger('max_investment')->nullable();
            $table->bigInteger('current_nav')->default(100000);
            $table->timestamp('nav_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->ulid('fund_id');
            $table->foreign('fund_id')->references('id')->on('investment_funds')->cascadeOnDelete();
            $table->string('type', 10)->default('subscribe');
            $table->bigInteger('units');
            $table->bigInteger('unit_price');
            $table->bigInteger('total_amount');
            $table->string('status', 20)->default('pending');
            $table->string('cfe_transaction_id', 26)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_navs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('fund_id');
            $table->foreign('fund_id')->references('id')->on('investment_funds')->cascadeOnDelete();
            $table->bigInteger('nav');
            $table->date('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_navs');
        Schema::dropIfExists('investment_subscriptions');
        Schema::dropIfExists('investment_funds');
    }
};
