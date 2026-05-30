<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('float_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('owner_type', 50);
            $table->ulid('owner_id');
            $table->enum('float_type', ['cash', 'electronic']);
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('pending_balance')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('active');
            $table->bigInteger('minimum_balance')->default(0);
            $table->bigInteger('maximum_balance')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->unique(['owner_type', 'owner_id', 'float_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('float_accounts');
    }
};
