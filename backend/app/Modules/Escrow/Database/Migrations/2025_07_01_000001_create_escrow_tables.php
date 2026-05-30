<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_agreements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('buyer_id');
            $table->ulid('seller_id');
            $table->string('reference_type', 30);
            $table->string('reference_id', 50);
            $table->bigInteger('total_amount');
            $table->bigInteger('fee_amount');
            $table->bigInteger('net_amount');
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->string('cfe_hold_id', 26)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('buyer_id')->references('id')->on('users');
            $table->foreign('seller_id')->references('id')->on('users');
            $table->index('status');
        });
        Schema::create('escrow_milestones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('escrow_id');
            $table->integer('milestone_number');
            $table->string('description', 255);
            $table->bigInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->foreign('escrow_id')->references('id')->on('escrow_agreements');
            $table->index('status');
        });
        Schema::create('escrow_disputes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('escrow_id');
            $table->ulid('opened_by');
            $table->string('reason', 500);
            $table->string('status', 20)->default('open');
            $table->string('resolution', 500)->nullable();
            $table->ulid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->foreign('escrow_id')->references('id')->on('escrow_agreements');
            $table->foreign('opened_by')->references('id')->on('users');
            $table->foreign('resolved_by')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down(): void { Schema::dropIfExists('escrow_disputes'); Schema::dropIfExists('escrow_milestones'); Schema::dropIfExists('escrow_agreements'); }
};
