<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dead_letter_events', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('event_id', 36)->index();
            $table->string('event_type');
            $table->string('consumer_name');
            $table->json('payload');
            $table->json('headers');
            $table->string('error_message');
            $table->text('error_trace')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dead_letter_events');
    }
};
