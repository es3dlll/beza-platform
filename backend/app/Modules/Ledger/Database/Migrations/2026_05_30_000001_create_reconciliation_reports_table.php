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
        Schema::create('reconciliation_reports', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('report_type', 30)->index();
            $table->string('scope', 20)->default('full');
            $table->string('account_id', 26)->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('is_balanced')->default(true);
            $table->unsignedInteger('total_accounts_checked')->default(0);
            $table->unsignedInteger('total_discrepancies_found')->default(0);
            $table->bigInteger('discrepancy_amount')->nullable();
            $table->string('cbs_report_code')->nullable();
            $table->date('reporting_date')->nullable();
            $table->string('currency', 3)->default('SYP');
            $table->json('summary')->nullable();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->string('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_type', 'reporting_date', 'status']);
            $table->index(['cbs_report_code', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_reports');
    }
};
