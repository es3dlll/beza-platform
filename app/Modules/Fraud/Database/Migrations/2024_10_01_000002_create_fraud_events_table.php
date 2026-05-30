<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_type', 50);
            $table->ulid('actor_id')->nullable();
            $table->string('actor_type', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_id', 255)->nullable();
            $table->string('user_agent')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->integer('risk_score')->default(0);
            $table->string('decision', 20)->default('allow');
            $table->ulid('matched_rule_id')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('actor_id');
            $table->index('ip_address');
            $table->index('device_id');
            $table->index('decision');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
