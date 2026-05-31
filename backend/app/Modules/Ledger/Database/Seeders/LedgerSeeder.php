<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Seeders;

use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1100', 'name' => 'Customer Wallets', 'name_ar' => 'محافظ العملاء', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Agent Wallets', 'name_ar' => 'محافظ الوكلاء', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Suspense', 'name_ar' => 'حساب التعليق', 'type' => 'asset'],
            ['code' => '1400', 'name' => 'Liquidity Reserve', 'name_ar' => 'احتياطي السيولة', 'type' => 'asset'],
            ['code' => '2100', 'name' => 'Merchant Payable', 'name_ar' => 'مستحق للتجار', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'Government Payable', 'name_ar' => 'مستحق للحكومة', 'type' => 'liability'],
            ['code' => '3100', 'name' => 'Capital', 'name_ar' => 'رأس المال', 'type' => 'equity'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'name_ar' => 'الأرباح المبقاة', 'type' => 'equity'],
            ['code' => '4100', 'name' => 'Transaction Fees', 'name_ar' => 'رسوم المعاملات', 'type' => 'revenue'],
            ['code' => '4200', 'name' => 'FX Spread', 'name_ar' => 'فارق العملة', 'type' => 'revenue'],
            ['code' => '5100', 'name' => 'Operating Expenses', 'name_ar' => 'مصروفات تشغيل', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Technology Expenses', 'name_ar' => 'مصروفات تقنية', 'type' => 'expense'],
            ['code' => '6100', 'name' => 'Referral Rewards', 'name_ar' => 'مكافآت الإحالة', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            LedgerAccount::create([
                'id' => Str::ulid()->toBase32(),
                'code' => $account['code'],
                'name' => $account['name'],
                'name_ar' => $account['name_ar'],
                'type' => $account['type'],
                'balance' => 0,
                'currency' => 'SYP',
                'is_system' => true,
            ]);
        }
    }
}
