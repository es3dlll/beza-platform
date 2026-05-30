<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journal_entry_id')->nullable();
            $table->string('transactionable_type');
            $table->string('transactionable_id');
            $table->enum('type', [
                'transfer', 'cash_in', 'cash_out', 'fx', 'bill',
                'merchant', 'payroll', 'remittance', 'fee', 'reversal',
            ]);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->enum('status', ['pending', 'held', 'completed', 'failed', 'reversed'])->default('pending');
            $table->string('idempotency_key', 100)->unique();
            $table->text('description')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->nullOnDelete();

            $table->index(['transactionable_type', 'transactionable_id']);
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
