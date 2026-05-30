<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corridors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('source_country', 3);
            $table->string('source_currency', 3);
            $table->string('target_currency', 3)->default('SYP');
            $table->string('fx_rate_source', 20)->default('cbs_official');
            $table->decimal('fixed_spread_pct', 5, 2)->default(2.0);
            $table->string('fee_type', 20)->default('percentage');
            $table->json('fee_structure')->nullable();
            $table->bigInteger('min_amount')->default(25000);
            $table->bigInteger('max_amount')->default(10000000);
            $table->bigInteger('daily_limit_per_sender')->default(50000000);
            $table->bigInteger('monthly_limit_per_sender')->default(250000000);
            $table->boolean('is_active')->default(true);
            $table->json('supported_payout_methods');
            $table->json('compliance_requirements')->nullable();
            $table->string('partner_name', 100)->nullable();
            $table->timestamps();

            $table->index('source_country');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corridors');
    }
};
