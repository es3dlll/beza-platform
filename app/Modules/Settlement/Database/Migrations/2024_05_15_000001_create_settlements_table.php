<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference_type', 50);
            $table->string('reference_id', 50);
            $table->string('settlement_type', 30);
            $table->string('status', 20)->default('pending');
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('commission_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('settlement_account_id', 26)->nullable();
            $table->string('cfe_transaction_id', 26)->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['reference_type', 'reference_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
