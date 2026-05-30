<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_conversions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quote_id');
            $table->ulid('from_wallet_id')->nullable();
            $table->ulid('to_wallet_id')->nullable();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->bigInteger('from_amount');
            $table->bigInteger('to_amount');
            $table->decimal('rate_applied', 16, 6);
            $table->bigInteger('fee_amount')->default(0);
            $table->string('fee_currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->timestamps();

            $table->index('quote_id');
            $table->index('status');
            $table->foreign('quote_id')->references('id')->on('fx_quotes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_conversions');
    }
};
