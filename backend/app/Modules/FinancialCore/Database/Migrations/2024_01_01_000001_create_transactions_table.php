<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type')->comment('hold|post|fee|reversal|settlement');
            $table->string('status')->default('initiated')->comment('initiated|held|posted|settled|reversed|failed');
            $table->string('wallet_id')->nullable()->index();
            $table->string('from_account_id')->nullable();
            $table->string('to_account_id')->nullable();
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('fee_amount')->default(0);
            $table->string('fee_account_id')->nullable();
            $table->integer('fee_basis_points')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('description')->nullable();
            $table->string('description_ar')->nullable();
            $table->json('metadata')->nullable();
            $table->string('reversed_by')->nullable();
            $table->string('reversal_of')->nullable();
            $table->string('journal_entry_id')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
