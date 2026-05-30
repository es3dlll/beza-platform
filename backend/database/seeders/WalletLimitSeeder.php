<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WalletLimitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $limits = [
            // KYC Tier 0 — Unverified (very limited)
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC0_daily_withdrawal',
                'description' => 'KYC Tier 0 daily withdrawal limit',
                'kyc_tier' => 0,
                'limit_type' => 'withdrawal',
                'period' => 'daily',
                'max_amount' => 500000,
                'max_count' => 2,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC0_monthly_withdrawal',
                'description' => 'KYC Tier 0 monthly withdrawal limit',
                'kyc_tier' => 0,
                'limit_type' => 'withdrawal',
                'period' => 'monthly',
                'max_amount' => 2000000,
                'max_count' => 10,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            // KYC Tier 1 — Basic
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC1_daily_withdrawal',
                'description' => 'KYC Tier 1 daily withdrawal limit',
                'kyc_tier' => 1,
                'limit_type' => 'withdrawal',
                'period' => 'daily',
                'max_amount' => 2000000,
                'max_count' => 5,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC1_monthly_withdrawal',
                'description' => 'KYC Tier 1 monthly withdrawal limit',
                'kyc_tier' => 1,
                'limit_type' => 'withdrawal',
                'period' => 'monthly',
                'max_amount' => 10000000,
                'max_count' => 30,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC1_single_transfer',
                'description' => 'KYC Tier 1 single transfer max',
                'kyc_tier' => 1,
                'limit_type' => 'transfer',
                'period' => 'single',
                'max_amount' => 1000000,
                'max_count' => null,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            // KYC Tier 2 — Enhanced
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC2_daily_withdrawal',
                'description' => 'KYC Tier 2 daily withdrawal limit',
                'kyc_tier' => 2,
                'limit_type' => 'withdrawal',
                'period' => 'daily',
                'max_amount' => 5000000,
                'max_count' => 10,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC2_monthly_withdrawal',
                'description' => 'KYC Tier 2 monthly withdrawal limit',
                'kyc_tier' => 2,
                'limit_type' => 'withdrawal',
                'period' => 'monthly',
                'max_amount' => 50000000,
                'max_count' => 100,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'name' => 'KYC2_single_transfer',
                'description' => 'KYC Tier 2 single transfer max',
                'kyc_tier' => 2,
                'limit_type' => 'transfer',
                'period' => 'single',
                'max_amount' => 5000000,
                'max_count' => null,
                'currency' => 'SYP',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ];

        DB::table('wallet_limits')->insert($limits);
    }
}
