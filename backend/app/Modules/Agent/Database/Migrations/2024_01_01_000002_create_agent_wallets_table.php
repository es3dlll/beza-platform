<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_wallets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('agent_id');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('balance')->default(0)->comment('Ledger balance');
            $table->bigInteger('float_balance')->default(0)->comment('Cash on hand');
            $table->bigInteger('daily_limit')->default(5000000)->comment('Max txn volume per day (50,000 SYP)');
            $table->bigInteger('daily_used')->default(0);
            $table->bigInteger('monthly_limit')->default(150000000)->comment('1,500,000 SYP');
            $table->bigInteger('monthly_used')->default(0);
            $table->string('status', 20)->default('active')->comment('active|frozen|closed');
            $table->timestamps();
            $table->unique(['agent_id', 'currency']);
            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_wallets');
    }
};
