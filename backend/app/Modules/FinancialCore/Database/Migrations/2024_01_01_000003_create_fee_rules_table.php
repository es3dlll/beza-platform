<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('type')->comment('flat|percentage|mixed');
            $table->bigInteger('value');
            $table->bigInteger('cap_amount')->nullable();
            $table->bigInteger('min_amount')->nullable();
            $table->string('account_code');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
