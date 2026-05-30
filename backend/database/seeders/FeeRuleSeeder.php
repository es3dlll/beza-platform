<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FeeRuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rules = [
            [
                'id' => Str::ulid()->toBase32(),
                'fee_type' => 'transfer_out',
                'calculation_type' => 'flat',
                'value' => 50000,
                'currency' => 'SYP',
                'fee_account_number' => '4000-001',
                'max_cap' => null,
                'min_amount' => null,
                'waived_for_tier' => false,
                'is_active' => true,
                'metadata' => json_encode(['ar' => 'رسوم تحويل خارجي', 'en' => 'External transfer fee']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'fee_type' => 'cash_withdrawal',
                'calculation_type' => 'percentage',
                'value' => 50,
                'currency' => 'SYP',
                'fee_account_number' => '4000-002',
                'max_cap' => 250000,
                'min_amount' => null,
                'waived_for_tier' => true,
                'is_active' => true,
                'metadata' => json_encode(['ar' => 'رسوم سحب نقدي 0.5%', 'en' => 'Cash withdrawal fee 0.5%']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'fee_type' => 'bill_payment',
                'calculation_type' => 'flat',
                'value' => 20000,
                'currency' => 'SYP',
                'fee_account_number' => '4000-003',
                'max_cap' => null,
                'min_amount' => null,
                'waived_for_tier' => false,
                'is_active' => true,
                'metadata' => json_encode(['ar' => 'رسوم دفع فاتورة', 'en' => 'Bill payment fee']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'fee_type' => 'wallet_to_wallet',
                'calculation_type' => 'flat',
                'value' => 0,
                'currency' => 'SYP',
                'fee_account_number' => '4000-004',
                'max_cap' => null,
                'min_amount' => null,
                'waived_for_tier' => false,
                'is_active' => true,
                'metadata' => json_encode(['ar' => 'رسوم تحويل محفظة', 'en' => 'Wallet transfer fee']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::ulid()->toBase32(),
                'fee_type' => 'agent_cash_out',
                'calculation_type' => 'percentage',
                'value' => 100,
                'currency' => 'SYP',
                'fee_account_number' => '4000-005',
                'max_cap' => 500000,
                'min_amount' => null,
                'waived_for_tier' => false,
                'is_active' => true,
                'metadata' => json_encode(['ar' => 'رسوم سحب وكيل 1%', 'en' => 'Agent cash out fee 1%']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('fee_rules')->insert($rules);
    }
}
