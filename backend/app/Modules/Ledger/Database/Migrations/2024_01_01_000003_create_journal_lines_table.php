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
            $table->string('id', 26)->primary();
            $table->string('journal_entry_id', 26);
            $table->string('account_id', 26);
            $table->string('type');
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->onDelete('restrict');

            $table->foreign('account_id')
                ->references('id')
                ->on('ledger_accounts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
