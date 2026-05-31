<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('module')->default('ledger');
            $table->string('category');
            $table->string('severity')->nullable();
            $table->text('description');
            $table->string('screenshot_url')->nullable();
            $table->json('context')->nullable();
            $table->string('status')->default('new');
            $table->json('internal_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
