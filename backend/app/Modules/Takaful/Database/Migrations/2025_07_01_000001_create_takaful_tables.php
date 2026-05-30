<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('takaful_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->bigInteger('min_premium');
            $table->bigInteger('max_premium');
            $table->bigInteger('coverage_amount');
            $table->integer('waiting_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('takaful_policies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('product_id');
            $table->string('policy_number', 30)->unique();
            $table->bigInteger('premium');
            $table->bigInteger('coverage_amount');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active');
            $table->string('cfe_premium_tx_id', 26)->nullable();
            $table->string('cfe_pool_account_id', 26)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('product_id')->references('id')->on('takaful_products');
        });

        Schema::create('takaful_claims', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('policy_id');
            $table->string('claim_number', 30)->unique();
            $table->bigInteger('amount');
            $table->text('reason');
            $table->string('status', 20)->default('filed');
            $table->bigInteger('approved_amount')->nullable();
            $table->string('cfe_payout_tx_id', 26)->nullable();
            $table->timestamp('filed_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('policy_id')->references('id')->on('takaful_policies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('takaful_claims');
        Schema::dropIfExists('takaful_policies');
        Schema::dropIfExists('takaful_products');
    }
};
