<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_spending_limits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('card_id');
            $table->string('limit_type', 20);
            $table->bigInteger('max_amount');
            $table->string('currency', 3)->default('SYP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('card_id')->references('id')->on('cards');
            $table->index('card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_spending_limits');
    }
};
