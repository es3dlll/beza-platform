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
            $table->integer('coverage_radius')->default(5000)->after('longitude');
            $table->integer('liquidity_score')->default(100)->after('coverage_radius');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['coverage_radius', 'liquidity_score']);
        });
    }
};
