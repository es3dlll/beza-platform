<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->string('product_type', 30)->default('murabaha')->after('name_ar');
            $table->decimal('late_penalty_rate', 5, 2)->default(0)->after('interest_rate');
            $table->json('bnpl_installments')->nullable()->after('is_active');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->string('product_type', 30)->default('murabaha')->after('loan_product_id');
            $table->decimal('late_penalty_rate', 5, 2)->default(0)->after('interest_rate');
            $table->timestamp('under_review_at')->nullable()->after('approved_at');
            $table->timestamp('defaulted_at')->nullable()->after('completed_at');
            $table->string('rejection_reason')->nullable()->after('purpose');
            $table->integer('credit_score')->nullable()->after('term_days');
        });

        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->bigInteger('late_penalty')->default(0)->after('paid_amount');
            $table->timestamp('reminded_at')->nullable()->after('paid_at');
            $table->string('payment_method', 30)->nullable()->after('status');
        });

        Schema::create('financing_credit_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->unique();
            $table->integer('score')->default(0);
            $table->integer('transaction_volume')->default(0);
            $table->integer('account_age_days')->default(0);
            $table->string('kyc_tier', 20)->nullable();
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_credit_scores');
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropColumn(['late_penalty', 'reminded_at', 'payment_method']);
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'late_penalty_rate', 'under_review_at', 'defaulted_at', 'rejection_reason', 'credit_score']);
        });
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'late_penalty_rate', 'bnpl_installments']);
        });
    }
};
