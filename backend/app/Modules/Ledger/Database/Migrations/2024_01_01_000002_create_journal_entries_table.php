<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('transaction_id')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->string('description_ar', 500)->nullable();
            $table->string('previous_hash')->nullable();
            $table->string('hash')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
