<?php

declare(strict_types=1);

namespace App\Modules\Fx\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_sources', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('type', 20)->comment('cbs|manual|market');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('rate_source_id');
            $table->string('base_currency', 3)->default('SYP');
            $table->string('quote_currency', 3);
            $table->bigInteger('buy_rate')->comment('SYP per 1 quote unit, in minor units * 10000');
            $table->bigInteger('sell_rate')->comment('SYP per 1 quote unit, in minor units * 10000');
            $table->integer('spread_bps')->default(0);
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->string('status', 20)->default('active')->comment('active|expired|manual');
            $table->timestamps();
            $table->index(['base_currency', 'quote_currency', 'status']);
            $table->index('valid_until');
        });

        Schema::create('fx_holds', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wallet_id')->index();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->bigInteger('amount');
            $table->bigInteger('locked_rate');
            $table->bigInteger('locked_spread_bps');
            $table->bigInteger('converted_amount');
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('active')->comment('active|consumed|expired|released');
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['wallet_id', 'status', 'expires_at']);
        });

        Schema::create('fx_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wallet_id')->index();
            $table->string('type', 20)->comment('conversion|reversal|settlement');
            $table->string('status', 20)->default('completed')->comment('pending|completed|failed|reversed');
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->bigInteger('debit_amount')->comment('Amount debited in base currency');
            $table->bigInteger('credit_amount')->comment('Amount credited in quote currency');
            $table->bigInteger('rate_used');
            $table->integer('spread_bps_applied');
            $table->string('rate_source_id');
            $table->string('fx_hold_id')->nullable();
            $table->string('cfe_transaction_id')->nullable()->comment('FK to financial_transactions');
            $table->string('reversal_of')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('description')->nullable();
            $table->string('description_ar')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
            $table->index('cfe_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_transactions');
        Schema::dropIfExists('fx_holds');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('rate_sources');
    }
};
