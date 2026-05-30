<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_students', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('full_name_ar');
            $table->string('national_id', 50)->nullable()->after('phone');
        });
        Schema::table('education_fees', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('paid_at');
            $table->string('cfe_transaction_id', 100)->nullable()->after('payment_method');
            $table->timestamp('reminded_at')->nullable()->after('cfe_transaction_id');
            $table->json('metadata')->nullable()->after('reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('education_students', function (Blueprint $table) {
            $table->dropColumn(['phone', 'national_id']);
        });
        Schema::table('education_fees', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'cfe_transaction_id', 'reminded_at', 'metadata']);
        });
    }
};
