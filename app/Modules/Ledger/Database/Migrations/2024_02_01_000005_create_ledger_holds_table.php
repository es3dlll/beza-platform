<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_holds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('account_id');
            $table->ulid('transaction_id');
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('reason', 200)->nullable();
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'released', 'expired', 'captured'])->default('active');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('ledger_accounts');

            $table->foreign('transaction_id')
                ->references('id')
                ->on('ledger_transactions');

            $table->index('account_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_holds');
    }
};
