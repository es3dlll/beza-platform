<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('fraud_event_id');
            $table->ulid('actor_id')->nullable();
            $table->string('actor_type', 50)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('severity', 20)->default('medium');
            $table->integer('risk_score');
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->ulid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('decision', 20)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('fraud_event_id')->references('id')->on('fraud_events');
            $table->index('status');
            $table->index('severity');
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_cases');
    }
};
