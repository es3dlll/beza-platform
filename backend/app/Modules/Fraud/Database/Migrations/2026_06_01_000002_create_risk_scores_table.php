<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->integer('score');
            $table->string('status');
            $table->json('reasons')->nullable();
            $table->string('request_type');
            $table->string('request_id')->nullable();
            $table->string('user_id')->nullable();
            $table->bigInteger('amount_fils')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('region')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_scores');
    }
};
