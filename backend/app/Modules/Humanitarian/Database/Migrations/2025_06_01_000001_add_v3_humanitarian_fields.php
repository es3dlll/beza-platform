<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('humanitarian_disbursements', function (Blueprint $table) {
            $table->string('pickup_code', 10)->nullable()->after('disbursed_at');
            $table->string('voucher_code', 50)->nullable()->after('pickup_code');
            $table->boolean('ofac_flagged')->default(false)->after('voucher_code');
            $table->timestamp('ofac_checked_at')->nullable()->after('ofac_flagged');
            $table->string('disbursement_batch_id', 50)->nullable()->after('ofac_checked_at');
            $table->json('metadata')->nullable()->after('disbursement_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('humanitarian_disbursements', function (Blueprint $table) {
            $table->dropColumn(['pickup_code', 'voucher_code', 'ofac_flagged', 'ofac_checked_at', 'disbursement_batch_id', 'metadata']);
        });
    }
};
