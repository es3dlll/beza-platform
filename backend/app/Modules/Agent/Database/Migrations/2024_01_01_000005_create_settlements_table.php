<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('agent_id')->index();
            $table->date('settlement_date');
            $table->bigInteger('expected_amount')->comment('Expected cash on hand');
            $table->bigInteger('actual_amount')->comment('Reported cash on hand');
            $table->bigInteger('difference');
            $table->bigInteger('commission_amount')->default(0);
            $table->string('status', 20)->default('pending')->comment('pending|confirmed|disputed|resolved');
            $table->string('notes')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_id', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
