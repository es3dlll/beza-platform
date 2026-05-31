<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_delivery_logs', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('event_id', 36)->index();
            $table->string('event_type');
            $table->string('status', 20)->default('pending');
            $table->json('payload');
            $table->string('consumer_name')->nullable();
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->string('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'consumer_name'], 'evt_dlv_unique_consumer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_delivery_logs');
    }
};
