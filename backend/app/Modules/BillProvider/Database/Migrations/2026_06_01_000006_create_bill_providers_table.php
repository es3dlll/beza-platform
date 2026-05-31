<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('category');
            $table->string('external_id')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('logo_url')->nullable();
            $table->string('support_phone')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_providers');
    }
};
