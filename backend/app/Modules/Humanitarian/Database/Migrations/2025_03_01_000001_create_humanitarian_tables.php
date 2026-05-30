<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('humanitarian_organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('code', 30)->unique();
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('humanitarian_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('name', 100);
            $table->string('name_ar', 100);
            $table->string('type', 30);
            $table->bigInteger('total_budget');
            $table->bigInteger('remaining_budget');
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('humanitarian_organizations');
        });
        Schema::create('humanitarian_disbursements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('program_id');
            $table->ulid('user_id');
            $table->ulid('beneficiary_id')->nullable();
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('type', 30);
            $table->string('status', 20)->default('pending');
            $table->string('reference_number')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            $table->foreign('program_id')->references('id')->on('humanitarian_programs');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void { Schema::dropIfExists('humanitarian_disbursements'); Schema::dropIfExists('humanitarian_programs'); Schema::dropIfExists('humanitarian_organizations'); }
};
