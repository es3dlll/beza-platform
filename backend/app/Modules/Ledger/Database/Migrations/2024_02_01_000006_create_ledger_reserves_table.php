<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_reserves', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('account_id');
            $table->enum('reserve_type', ['cbs_minimum', 'liquidity', 'capital']);
            $table->bigInteger('required_amount');
            $table->bigInteger('current_amount');
            $table->string('currency', 3)->default('SYP');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('ledger_accounts');

            $table->index('account_id');
            $table->index('reserve_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_reserves');
    }
};
