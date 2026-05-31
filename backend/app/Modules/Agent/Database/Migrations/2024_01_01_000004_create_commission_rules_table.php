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
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('txn_type', 20)->comment('cash_in|cash_out|transfer|bill_payment');
            $table->string('calc_type', 20)->comment('flat|percentage|mixed');
            $table->bigInteger('value');
            $table->bigInteger('cap_amount')->nullable();
            $table->bigInteger('min_amount')->nullable();
            $table->string('kyc_tier_min', 2)->default('t0');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['txn_type', 'kyc_tier_min', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
