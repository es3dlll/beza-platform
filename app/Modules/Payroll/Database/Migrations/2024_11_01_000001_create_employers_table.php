<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('company_name', 150);
            $table->string('company_name_ar', 150);
            $table->string('commercial_registration', 50)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('governorate', 50);
            $table->string('city', 50);
            $table->text('address')->nullable();
            $table->string('status', 20)->default('pending');
            $table->bigInteger('monthly_payroll_limit')->default(50000000);
            $table->bigInteger('used_monthly_payroll')->default(0);
            $table->integer('employee_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
