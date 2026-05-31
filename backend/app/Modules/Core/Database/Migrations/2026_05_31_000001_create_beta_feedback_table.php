<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beta_feedback', function (Blueprint $table) {
            $table->string('feedback_id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('category');
            $table->text('description');
            $table->string('screenshot_url')->nullable();
            $table->tinyInteger('rating');
            $table->boolean('allow_followup')->default(false);
            $table->string('status')->default('new');
            $table->json('analysis_metadata')->nullable();
            $table->json('internal_notes')->nullable();
            $table->index(['user_id', 'status']);
            $table->index('category');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beta_feedback');
    }
};
