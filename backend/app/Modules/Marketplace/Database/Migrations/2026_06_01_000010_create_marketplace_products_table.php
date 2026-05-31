<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('marketplace_sellers')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('price_fils');
            $table->string('category');
            $table->string('location')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('active');
            $table->float('rating')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('price_fils');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
