<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_blacklist', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type', 20);
            $table->string('value', 255);
            $table->string('reason')->nullable();
            $table->string('source', 50)->default('manual');
            $table->ulid('added_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('type');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_blacklist');
    }
};
