<?php
declare(strict_types=1);

namespace Modules\Bills\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->bigInteger('amount_due')->default(0)->change();
            $table->bigInteger('amount_paid')->default(0)->change();
            $table->bigInteger('fee_amount')->default(0)->change();
            $table->bigInteger('total_debited')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->integer('amount_due')->change();
            $table->integer('amount_paid')->change();
            $table->integer('fee_amount')->change();
            $table->integer('total_debited')->change();
        });
    }
};
