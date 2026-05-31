<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('buyer_id');
            $table->ulid('seller_id');
            $table->string('marketplace_ref_id')->nullable();
            $table->bigInteger('amount_fils');
            $table->bigInteger('fee_fils')->default(0);
            $table->string('status')->default('initiated');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
