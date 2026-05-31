<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->bigInteger('rate_fils_per_unit');
            $table->bigInteger('bid_fils_per_unit');
            $table->bigInteger('ask_fils_per_unit');
            $table->string('provider')->default('simulated');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['from_currency', 'to_currency', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
