<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('employer_id');
            $table->string('batch_reference', 50)->unique();
            $table->integer('total_employees');
            $table->bigInteger('total_amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('period_month', 7);
            $table->text('notes')->nullable();
            $table->ulid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('employer_id')->references('id')->on('employers');
            $table->index('status');
            $table->index('period_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batches');
    }
};
