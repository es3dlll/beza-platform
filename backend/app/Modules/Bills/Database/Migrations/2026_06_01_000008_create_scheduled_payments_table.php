<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('bill_provider_id');
            $table->string('account_number');
            $table->bigInteger('amount_fils');
            $table->string('recurrence');
            $table->integer('recurrence_day');
            $table->date('next_execution_date');
            $table->timestamp('last_executed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
            $table->index('next_execution_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_payments');
    }
};
