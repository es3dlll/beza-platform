<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'commission_tier')) {
                $table->string('commission_tier', 20)->default('Bronze')->after('is_verified');
            }
            if (!Schema::hasColumn('agents', 'minimum_float')) {
                $table->bigInteger('minimum_float')->default(100000)->after('commission_tier');
            }
            if (!Schema::hasColumn('agents', 'max_txn_limit')) {
                $table->bigInteger('max_txn_limit')->default(500000)->after('minimum_float');
            }
            if (!Schema::hasColumn('agents', 'service_point_status')) {
                $table->string('service_point_status', 30)->default('PENDING_ACTIVATION')->after('max_txn_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['commission_tier', 'minimum_float', 'max_txn_limit', 'service_point_status']);
        });
    }
};
