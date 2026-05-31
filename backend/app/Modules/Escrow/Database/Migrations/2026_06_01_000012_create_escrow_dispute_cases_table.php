<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_dispute_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('escrow_transaction_id')->constrained('escrow_transactions')->cascadeOnDelete();
            $table->ulid('raised_by');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->json('documents')->nullable();
            $table->string('status')->default('open');
            $table->string('decision')->nullable();
            $table->text('decision_reason')->nullable();
            $table->ulid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_dispute_cases');
    }
};
