<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('bid_rate', 16, 6);
            $table->decimal('mid_rate', 16, 6);
            $table->decimal('ask_rate', 16, 6);
            $table->decimal('spread_pct', 5, 2)->default(0);
            $table->string('rate_type', 20);
            $table->string('source', 50);
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['base_currency', 'quote_currency', 'rate_type']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};
