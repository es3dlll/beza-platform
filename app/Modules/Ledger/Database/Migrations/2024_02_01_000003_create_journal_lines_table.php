<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journal_entry_id');
            $table->ulid('account_id');
            $table->enum('type', ['debit', 'credit']);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->cascadeOnDelete();

            $table->foreign('account_id')
                ->references('id')
                ->on('ledger_accounts');

            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index('reconciled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
