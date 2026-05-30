<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('employer_id');
            $table->string('full_name', 100);
            $table->string('full_name_ar', 100)->nullable();
            $table->string('phone', 20);
            $table->string('national_id', 50)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->bigInteger('base_salary');
            $table->string('currency', 3)->default('SYP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('employer_id')->references('id')->on('employers');
            $table->index('phone');
            $table->index('employer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_records');
    }
};
