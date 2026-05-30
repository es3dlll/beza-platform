<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_merchant_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('card_id');
            $table->string('merchant_category', 50);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('card_id')->references('id')->on('cards');
            $table->unique(['card_id', 'merchant_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_merchant_blocks');
    }
};
