<?php

declare(strict_types=1);

namespace Modules\Wallet\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Wallet transactions lookup by wallet
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['wallet_id', 'created_at'], 'idx_wallet_txns_wallet_created');
        });

        // 2. Wallet transactions reference lookup
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id'], 'idx_wallet_txns_ref');
        });

        // 3. OTP lookup
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->index(['phone', 'purpose', 'expires_at', 'verified_at'], 'idx_otp_lookup');
        });

        // 4. Ledger accounts lookup
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->index(['module', 'type', 'currency'], 'idx_ledger_lookup');
        });

        // 5. CFE transactions deduplication
        Schema::table('cfe_transactions', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id'], 'idx_cfe_transactions_ref');
        });

        // 6. Active sessions lookup
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['user_id', 'is_active'], 'idx_sessions_active');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_wallet_txns_wallet_created');
            $table->dropIndex('idx_wallet_txns_ref');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex('idx_otp_lookup');
        });

        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_ledger_lookup');
        });

        Schema::table('cfe_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cfe_transactions_ref');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_active');
        });
    }
};
