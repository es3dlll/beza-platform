<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('user_id', 26);
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('balance')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['user_id', 'currency']);
            $table->index(['user_id', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
