<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_institutions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('code', 30)->unique();
            $table->string('type', 30);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('education_students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('institution_id');
            $table->string('student_id', 50);
            $table->string('full_name', 100);
            $table->string('full_name_ar', 100);
            $table->string('grade', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('institution_id')->references('id')->on('education_institutions');
            $table->unique(['institution_id','student_id']);
        });
        Schema::create('education_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->string('fee_type', 50);
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->string('receipt_number')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('education_students');
            $table->index('status');
        });
    }

    public function down(): void { Schema::dropIfExists('education_fees'); Schema::dropIfExists('education_students'); Schema::dropIfExists('education_institutions'); }
};
