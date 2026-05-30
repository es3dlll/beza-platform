<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_disbursements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('payroll_batch_id');
            $table->ulid('employer_id');
            $table->ulid('employee_record_id')->nullable();
            $table->string('employee_name', 100);
            $table->string('employee_phone', 20);
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->ulid('wallet_transaction_id')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('payroll_batch_id')->references('id')->on('payroll_batches');
            $table->foreign('employer_id')->references('id')->on('employers');
            $table->index('status');
            $table->index('employee_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_disbursements');
    }
};
