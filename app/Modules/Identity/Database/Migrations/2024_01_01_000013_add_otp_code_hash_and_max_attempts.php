<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('otp_codes', 'code_hash')) {
                $table->string('code_hash', 255)->after('code');
            }
            if (!Schema::hasColumn('otp_codes', 'max_attempts')) {
                $table->unsignedTinyInteger('max_attempts')->default(5)->after('attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn(['code_hash', 'max_attempts']);
        });
    }
};
