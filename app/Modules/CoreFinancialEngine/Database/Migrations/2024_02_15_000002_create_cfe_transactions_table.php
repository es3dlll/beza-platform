<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfe_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference_type', 50);
            $table->string('reference_id', 50)->index();
            $table->string('description');
            $table->bigInteger('total_amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('channel', 20)->default('api');
            $table->string('initiated_by', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('journal_entry_id', 36)->nullable()->comment('Ledger journal entry reference');
            $table->string('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['reference_type', 'reference_id']);
        });

        Schema::create('cfe_transaction_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('cfe_transaction_id');
            $table->string('account_id', 36);
            $table->bigInteger('amount');
            $table->enum('type', ['debit', 'credit']);
            $table->string('description')->nullable();

            $table->foreign('cfe_transaction_id')
                ->references('id')
                ->on('cfe_transactions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfe_transaction_lines');
        Schema::dropIfExists('cfe_transactions');
    }
};
