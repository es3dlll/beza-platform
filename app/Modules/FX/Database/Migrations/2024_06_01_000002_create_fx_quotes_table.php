<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('requestor_id');
            $table->string('requestor_type', 20);
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->bigInteger('amount_in_base');
            $table->bigInteger('amount_in_quote');
            $table->decimal('rate_used', 16, 6);
            $table->string('rate_type', 20);
            $table->ulid('fx_rate_id');
            $table->string('status', 20)->default('active');
            $table->integer('ttl_seconds')->default(60);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['requestor_id', 'requestor_type']);
            $table->index('status');
            $table->index('expires_at');
            $table->foreign('fx_rate_id')->references('id')->on('fx_rates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_quotes');
    }
};
