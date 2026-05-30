<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->bigInteger('min_amount');
            $table->bigInteger('max_amount');
            $table->decimal('interest_rate', 5, 2);
            $table->integer('min_term_days');
            $table->integer('max_term_days');
            $table->json('required_documents')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('loan_product_id');
            $table->bigInteger('principal');
            $table->bigInteger('total_repayable');
            $table->bigInteger('outstanding_balance');
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_days');
            $table->string('status', 20)->default('pending');
            $table->string('purpose')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('loan_product_id')->references('id')->on('loan_products');
            $table->index('status');
        });
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('loan_id');
            $table->integer('installment_number');
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->foreign('loan_id')->references('id')->on('loans');
            $table->index('status');
        });
    }

    public function down(): void { Schema::dropIfExists('loan_repayments'); Schema::dropIfExists('loans'); Schema::dropIfExists('loan_products'); }
};
