<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_sellers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('pending');
            $table->float('rating')->default(0);
            $table->integer('total_sales')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sellers');
    }
};
