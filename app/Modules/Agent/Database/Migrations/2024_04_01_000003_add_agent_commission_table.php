<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('agent_id');
            $table->ulid('agent_transaction_id');
            $table->bigInteger('amount');
            $table->string('type', 30);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents');
            $table->foreign('agent_transaction_id')->references('id')->on('agent_transactions');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');
    }
};
