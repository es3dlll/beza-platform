<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_discrepancies', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('report_id', 26)->index();
            $table->foreign('report_id')->references('id')->on('reconciliation_reports')->cascadeOnDelete();
            $table->string('account_id', 26)->index();
            $table->string('discrepancy_type', 30)->index();
            $table->string('severity', 20)->default('medium');
            $table->bigInteger('expected_balance')->default(0);
            $table->bigInteger('actual_balance')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('journal_line_id', 26)->nullable();
            $table->string('transaction_reference')->nullable();
            $table->json('context')->nullable();
            $table->text('description')->nullable();
            $table->json('resolution_steps')->nullable();
            $table->string('resolution_status', 20)->default('open');
            $table->text('resolution_notes')->nullable();
            $table->string('resolved_by', 26)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('requires_cbs_notification')->default(false);
            $table->string('cbs_case_reference')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'severity', 'resolution_status'], 'rec_disc_rpt_sev_res_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_discrepancies');
    }
};
