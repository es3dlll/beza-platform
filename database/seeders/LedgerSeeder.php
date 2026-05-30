<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Ledger\Models\LedgerAccount;

class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Asset accounts (1000-1999)
            [
                'code' => '1001', 'name' => 'Cash - Operating', 'name_ar' => 'النقدية - التشغيلية',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1002', 'name' => 'Cash - CBS Reserve', 'name_ar' => 'النقدية - احتياطي مصرف سورية المركزي',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1101', 'name' => 'Bank - BSO Current', 'name_ar' => 'البنك - بنك سورية والمهجر جارٍ',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1102', 'name' => 'Bank - Bemo Current', 'name_ar' => 'البنك - بنك بيمو جارٍ',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1103', 'name' => 'Bank - SIIB Current', 'name_ar' => 'البنك - بنك سورية الدولي الإسلامي جارٍ',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1201', 'name' => 'Accounts Receivable - Agents', 'name_ar' => 'ذمم مدينة - وكلاء',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '1202', 'name' => 'Accounts Receivable - Merchants', 'name_ar' => 'ذمم مدينة - تجار',
                'type' => 'asset', 'category' => 'current_asset', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],

            // Liability accounts (2000-2999)
            [
                'code' => '2001', 'name' => 'Customer Wallets - SYP', 'name_ar' => 'محافظ العملاء - ل.س',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'SYP',
                'module' => 'wallet', 'is_system' => true,
            ],
            [
                'code' => '2002', 'name' => 'Customer Wallets - USD', 'name_ar' => 'محافظ العملاء - دولار',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'USD',
                'module' => 'wallet', 'is_system' => true,
            ],
            [
                'code' => '2101', 'name' => 'Agent Float - SYP', 'name_ar' => 'رصيد الوكيل - ل.س',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'SYP',
                'module' => 'wallet', 'is_system' => true,
            ],
            [
                'code' => '2201', 'name' => 'Merchant Payable', 'name_ar' => 'ذمم دائنة - تجار',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'SYP',
                'module' => 'wallet', 'is_system' => true,
            ],
            [
                'code' => '2301', 'name' => 'Biller Payable', 'name_ar' => 'ذمم دائنة - فوترة',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'SYP',
                'module' => 'wallet', 'is_system' => true,
            ],
            [
                'code' => '2401', 'name' => 'Settlement Payable', 'name_ar' => 'ذمم دائنة - تسوية',
                'type' => 'liability', 'category' => 'current_liability', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],

            // Income accounts (4000-4999)
            [
                'code' => '4001', 'name' => 'Transfer Fee Income', 'name_ar' => 'إيرادات رسوم التحويل',
                'type' => 'income', 'category' => 'operating_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '4002', 'name' => 'FX Margin Income', 'name_ar' => 'إيرادات هامش الصرافة',
                'type' => 'income', 'category' => 'operating_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '4003', 'name' => 'Agent Commission Income', 'name_ar' => 'إيرادات عمولات الوكلاء',
                'type' => 'income', 'category' => 'operating_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '4004', 'name' => 'Merchant MDR Income', 'name_ar' => 'إيرادات رسوم التجار',
                'type' => 'income', 'category' => 'operating_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '4005', 'name' => 'Remittance Fee Income', 'name_ar' => 'إيرادات رسوم الحوالات',
                'type' => 'income', 'category' => 'operating_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],

            // Expense accounts (5000-5999)
            [
                'code' => '5001', 'name' => 'SMS Cost', 'name_ar' => 'تكلفة الرسائل النصية',
                'type' => 'expense', 'category' => 'operating_expense', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '5002', 'name' => 'Bank Transfer Fees', 'name_ar' => 'رسوم التحويل المصرفي',
                'type' => 'expense', 'category' => 'operating_expense', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '5003', 'name' => 'Fraud Loss Provision', 'name_ar' => 'مخصص خسائر الاحتيال',
                'type' => 'expense', 'category' => 'operating_expense', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],

            // Suspense accounts (9000-9999)
            [
                'code' => '9001', 'name' => 'FX Suspense - SYP', 'name_ar' => 'حساب تعليق الصرافة - ل.س',
                'type' => 'suspense', 'category' => 'other_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '9002', 'name' => 'FX Suspense - USD', 'name_ar' => 'حساب تعليق الصرافة - دولار',
                'type' => 'suspense', 'category' => 'other_income', 'currency' => 'USD',
                'module' => 'ledger', 'is_system' => true,
            ],
            [
                'code' => '9003', 'name' => 'Unidentified Transactions', 'name_ar' => 'معاملات غير محددة',
                'type' => 'suspense', 'category' => 'other_income', 'currency' => 'SYP',
                'module' => 'ledger', 'is_system' => true,
            ],
        ];

        foreach ($accounts as $account) {
            LedgerAccount::firstOrCreate(
                ['code' => $account['code']],
                array_merge($account, ['id' => (string) Str::ulid()])
            );
        }
    }
}
