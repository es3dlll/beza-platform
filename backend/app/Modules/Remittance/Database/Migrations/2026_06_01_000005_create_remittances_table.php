<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('sender_user_id');
            $table->string('receiver_name');
            $table->string('receiver_phone')->nullable();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->bigInteger('from_amount_fils');
            $table->bigInteger('to_amount_fils');
            $table->string('exchange_rate_id');
            $table->bigInteger('rate_used_fils_per_unit');
            $table->bigInteger('fee_fils');
            $table->bigInteger('total_charged_fils');
            $table->string('status')->default('pending');
            $table->string('risk_score_id')->nullable();
            $table->string('reference_number')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sender_user_id', 'status']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
