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
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->ulid('transaction_id')->nullable();
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('reason', 200)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('release_reason', 200)->nullable();
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
