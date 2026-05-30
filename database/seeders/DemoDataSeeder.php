<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DemoDataSeeder extends Seeder
{
    private string $now;

    public function run(): void
    {
        $this->now = now()->toDateTimeString();

        $this->seedUsers();
        $this->seedProfiles();
        $this->seedUserRoles();
        $this->seedWallets();
        $this->seedWalletTransactions();
        $this->seedFxRates();
        $this->seedAgents();
        $this->seedFloatAccounts();
        $this->seedMerchants();
        $this->seedMerchantStores();
        $this->seedBillProviders();
        $this->seedRemittanceCorridors();
        $this->seedSavingsGoals();
        $this->seedCards();
        $this->seedLoyaltyTiers();
        $this->seedPayrollEmployers();
        $this->seedLoanProducts();
        $this->seedEducationInstitutions();
        $this->seedHumanitarianOrganizations();
        $this->seedFraudRules();
        $this->seedJournalEntries();
    }

    private function ulid(string $_prefix = ''): string
    {
        return (string) Str::ulid();
    }

    // ─── Users ───────────────────────────────────────────────────────────

    private function seedUsers(): void
    {
        $users = [
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000001',
                'phone_country_code' => '963',
                'first_name' => 'إداري',
                'last_name' => 'النظام',
                'email' => 'admin@beza.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_2_enhanced',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000002',
                'phone_country_code' => '963',
                'first_name' => 'أحمد',
                'last_name' => 'الخالد',
                'email' => 'ahmad@example.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_2_enhanced',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000003',
                'phone_country_code' => '963',
                'first_name' => 'سارة',
                'last_name' => 'الأحمد',
                'email' => 'sara@example.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_1_basic',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000004',
                'phone_country_code' => '963',
                'first_name' => 'محمود',
                'last_name' => 'حسن',
                'email' => 'mahmoud@example.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_0_unverified',
                'locale' => 'ar',
                'phone_verified_at' => null,
                'last_login_at' => null,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000005',
                'phone_country_code' => '963',
                'first_name' => 'وكيل',
                'last_name' => 'الخدمات',
                'email' => 'agent@beza.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_2_enhanced',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000006',
                'phone_country_code' => '963',
                'first_name' => 'تاجر',
                'last_name' => 'الإلكتروني',
                'email' => 'merchant@beza.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_2_enhanced',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
            [
                'id' => $this->ulid('user_'),
                'phone' => '963900000007',
                'phone_country_code' => '963',
                'first_name' => 'رب عمل',
                'last_name' => 'المنشأة',
                'email' => 'employer@beza.sy',
                'password' => Hash::make('password'),
                'status' => 'active',
                'kyc_tier' => 'tier_2_enhanced',
                'locale' => 'ar',
                'phone_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }

    // ─── Profiles ────────────────────────────────────────────────────────

    private function seedProfiles(): void
    {
        $profiles = [
            [
                'id' => $this->ulid(),
                'user_id' => $this->getUserId(1),
                'full_name' => 'إداري النظام',
                'national_id' => '01010123456',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'address' => 'دمشق - المزة',
                'city' => 'دمشق',
                'province' => 'دمشق',
            ],
            [
                'id' => $this->ulid(),
                'user_id' => $this->getUserId(2),
                'full_name' => 'أحمد الخالد',
                'national_id' => '02020212345',
                'date_of_birth' => '1988-05-15',
                'gender' => 'male',
                'address' => 'حلب - الجميلية',
                'city' => 'حلب',
                'province' => 'حلب',
            ],
            [
                'id' => $this->ulid(),
                'user_id' => $this->getUserId(3),
                'full_name' => 'سارة الأحمد',
                'national_id' => '03030354321',
                'date_of_birth' => '1995-09-20',
                'gender' => 'female',
                'address' => 'اللاذقية - الشاطئ الأزرق',
                'city' => 'اللاذقية',
                'province' => 'اللاذقية',
            ],
            [
                'id' => $this->ulid(),
                'user_id' => $this->getUserId(5),
                'full_name' => 'وكيل الخدمات',
                'national_id' => '04040467890',
                'date_of_birth' => '1985-03-10',
                'gender' => 'male',
                'address' => 'دمشق - باب توما',
                'city' => 'دمشق',
                'province' => 'دمشق',
            ],
            [
                'id' => $this->ulid(),
                'user_id' => $this->getUserId(6),
                'full_name' => 'تاجر الإلكتروني',
                'national_id' => '05050598765',
                'date_of_birth' => '1992-07-22',
                'gender' => 'male',
                'address' => 'دمشق - مركز المدينة',
                'city' => 'دمشق',
                'province' => 'دمشق',
            ],
        ];

        foreach ($profiles as $profile) {
            DB::table('profiles')->insert($profile);
        }
    }

    // ─── User Roles ──────────────────────────────────────────────────────

    private function seedUserRoles(): void
    {
        $roleIds = [];
        $roles = DB::table('roles')->get(['id', 'name']);
        foreach ($roles as $role) {
            $roleIds[$role->name] = $role->id;
        }

        $assignments = [
            ['user_id' => $this->getUserId(1), 'role_id' => $roleIds['super_admin']],
            ['user_id' => $this->getUserId(2), 'role_id' => $roleIds['admin']],
            ['user_id' => $this->getUserId(5), 'role_id' => $roleIds['ops_manager']],
        ];

        foreach ($assignments as $assignment) {
            DB::table('iam_user_roles')->insert([
                'user_id' => $assignment['user_id'],
                'role_id' => $assignment['role_id'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    // ─── Wallets ─────────────────────────────────────────────────────────

    private function seedWallets(): void
    {
        $ledgerAccounts = [];
        $accounts = DB::table('ledger_accounts')->where('is_system', true)->get(['id', 'code']);
        foreach ($accounts as $acc) {
            $ledgerAccounts[$acc->code] = $acc->id;
        }

        $wallets = [
            // Super admin: SYP + USD
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(1),
                'currency' => 'SYP',
                'balance' => 500000000,
                'available_balance' => 500000000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 50000000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(1),
                'currency' => 'USD',
                'balance' => 10000000,
                'available_balance' => 10000000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 500000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2002'],
            ],
            // Ahmad: SYP + USD
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(2),
                'currency' => 'SYP',
                'balance' => 250000000,
                'available_balance' => 250000000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 20000000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(2),
                'currency' => 'USD',
                'balance' => 500000,
                'available_balance' => 500000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 200000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2002'],
            ],
            // Sara: SYP only
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(3),
                'currency' => 'SYP',
                'balance' => 50000000,
                'available_balance' => 50000000,
                'status' => 'active',
                'kyc_tier_required' => 1,
                'daily_limit' => 5000000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
            // Mahmoud (pending): SYP with zero balance
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(4),
                'currency' => 'SYP',
                'balance' => 0,
                'available_balance' => 0,
                'status' => 'active',
                'kyc_tier_required' => 0,
                'daily_limit' => 500000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
            // Agent: SYP
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(5),
                'currency' => 'SYP',
                'balance' => 100000000,
                'available_balance' => 100000000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 50000000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
            // Merchant: SYP
            [
                'id' => $this->ulid('w_'),
                'user_id' => $this->getUserId(6),
                'currency' => 'SYP',
                'balance' => 75000000,
                'available_balance' => 75000000,
                'status' => 'active',
                'kyc_tier_required' => 2,
                'daily_limit' => 20000000,
                'daily_used' => 0,
                'ledger_account_id' => $ledgerAccounts['2001'],
            ],
        ];

        foreach ($wallets as $wallet) {
            DB::table('wallets')->insert($wallet);
        }
    }

    // ─── Wallet Transactions ─────────────────────────────────────────────

    private function seedWalletTransactions(): void
    {
        $walletIds = DB::table('wallets')->where('balance', '>', 0)->pluck('id');
        $cfeIds = [];
        $cfeRows = DB::table('cfe_transactions')->get(['id']);
        foreach ($cfeRows as $row) {
            $cfeIds[] = $row->id;
        }

        foreach ($walletIds as $i => $walletId) {
            DB::table('wallet_transactions')->insert([
                'id' => $this->ulid('wtxn_'),
                'wallet_id' => $walletId,
                'type' => 'deposit',
                'amount' => 10000000 * ($i + 1),
                'currency' => 'SYP',
                'balance_before' => 0,
                'balance_after' => 10000000 * ($i + 1),
                'reference_type' => 'cbs_deposit',
                'reference_id' => $this->ulid(),
                'status' => 'completed',
                'description' => 'إيداع أولي عبر مصرف سورية المركزي',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    // ─── FX Rates ────────────────────────────────────────────────────────

    private function seedFxRates(): void
    {
        $rates = [
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'USD',
                'bid_rate' => 12500.00,
                'mid_rate' => 12600.00,
                'ask_rate' => 12700.00,
                'spread_pct' => 1.59,
                'rate_type' => 'official',
                'source' => 'CBS',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'USD',
                'bid_rate' => 14800.00,
                'mid_rate' => 15000.00,
                'ask_rate' => 15200.00,
                'spread_pct' => 2.67,
                'rate_type' => 'parallel',
                'source' => 'market',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'EUR',
                'bid_rate' => 13500.00,
                'mid_rate' => 13650.00,
                'ask_rate' => 13800.00,
                'spread_pct' => 2.20,
                'rate_type' => 'official',
                'source' => 'CBS',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'TRY',
                'bid_rate' => 410.00,
                'mid_rate' => 420.00,
                'ask_rate' => 430.00,
                'spread_pct' => 4.76,
                'rate_type' => 'parallel',
                'source' => 'market',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'AED',
                'bid_rate' => 3400.00,
                'mid_rate' => 3430.00,
                'ask_rate' => 3460.00,
                'spread_pct' => 1.75,
                'rate_type' => 'parallel',
                'source' => 'market',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
            [
                'base_currency' => 'SYP',
                'quote_currency' => 'SAR',
                'bid_rate' => 3300.00,
                'mid_rate' => 3335.00,
                'ask_rate' => 3370.00,
                'spread_pct' => 2.10,
                'rate_type' => 'parallel',
                'source' => 'market',
                'valid_from' => now()->subDay()->toDateTimeString(),
                'valid_to' => now()->addDay()->toDateTimeString(),
                'published_at' => $this->now,
            ],
        ];

        foreach ($rates as $rate) {
            DB::table('fx_rates')->insert(array_merge($rate, ['id' => $this->ulid()]));
        }
    }

    // ─── Agents ──────────────────────────────────────────────────────────

    private function seedAgents(): void
    {
        $agents = [
            [
                'user_id' => $this->getUserId(5),
                'business_name' => 'خدمات المزة للتحويل',
                'trade_license' => 'TR-2024-001',
                'agent_type' => 'financial',
                'status' => 'active',
                'governorate' => 'دمشق',
                'city' => 'دمشق',
                'area' => 'المزة',
                'address' => 'شارع الثورة - مقابل الجامعة',
                'latitude' => 33.5138,
                'longitude' => 36.2765,
                'daily_cash_in_limit' => 100000000,
                'daily_cash_out_limit' => 50000000,
                'max_commission_per_txn' => 500000,
                'commission_rate' => 0.5,
                'wallet_id' => null,
                'phone' => '963900000010',
                'alt_phone' => null,
                'coverage_radius' => 5,
                'liquidity_score' => 85,
                'approved_at' => $this->now,
                'approved_by' => $this->getUserId(1),
            ],
            [
                'user_id' => $this->getUserId(2),
                'business_name' => 'وكالة حلب للصرافة',
                'trade_license' => 'TR-2024-002',
                'agent_type' => 'exchange',
                'status' => 'approved',
                'governorate' => 'حلب',
                'city' => 'حلب',
                'area' => 'الجميلية',
                'address' => 'شارع الباب الفرج',
                'latitude' => 36.2028,
                'longitude' => 37.1344,
                'daily_cash_in_limit' => 50000000,
                'daily_cash_out_limit' => 25000000,
                'max_commission_per_txn' => 300000,
                'commission_rate' => 0.75,
                'wallet_id' => null,
                'phone' => '963900000011',
                'alt_phone' => '963900000012',
                'coverage_radius' => 3,
                'liquidity_score' => 70,
                'approved_at' => $this->now,
                'approved_by' => $this->getUserId(1),
            ],
            [
                'user_id' => $this->getUserId(3),
                'business_name' => 'وكالة اللاذقية المالية',
                'trade_license' => 'TR-2024-003',
                'agent_type' => 'financial',
                'status' => 'active',
                'governorate' => 'اللاذقية',
                'city' => 'اللاذقية',
                'area' => 'الشاطئ الأزرق',
                'address' => 'شارع 8 آذار',
                'latitude' => 35.5192,
                'longitude' => 35.7820,
                'daily_cash_in_limit' => 25000000,
                'daily_cash_out_limit' => 15000000,
                'max_commission_per_txn' => 200000,
                'commission_rate' => 0.5,
                'wallet_id' => null,
                'phone' => '963900000013',
                'alt_phone' => null,
                'coverage_radius' => 4,
                'liquidity_score' => 60,
                'approved_at' => $this->now,
                'approved_by' => $this->getUserId(1),
            ],
        ];

        foreach ($agents as $agent) {
            DB::table('agents')->insert(array_merge($agent, ['id' => $this->ulid()]));
        }
    }

    // ─── Merchants ───────────────────────────────────────────────────────

    private function seedMerchants(): void
    {
        $merchants = [
            [
                'user_id' => $this->getUserId(6),
                'business_name' => 'BezaTech POS',
                'business_name_ar' => 'بيزا تك نقاط البيع',
                'commercial_registration' => 'CR-2024-001',
                'tax_number' => 'TX-12345',
                'phone' => '963900000020',
                'email' => 'merchant@bezatech.sy',
                'governorate' => 'دمشق',
                'city' => 'دمشق',
                'address' => 'شارع بغداد',
                'category' => 'electronics',
                'status' => 'active',
                'monthly_volume_syp' => 50000000,
                'mdr_percentage' => 0.50,
                'mdr_min_syp' => 10000,
                'mdr_max_syp' => 500000,
                'max_txn_amount' => 5000000,
                'is_micro_merchant' => false,
                'approved_at' => $this->now,
            ],
            [
                'user_id' => $this->getUserId(6),
                'business_name' => 'Al Sham Supermarket',
                'business_name_ar' => 'سوبرماركت الشام',
                'commercial_registration' => 'CR-2024-002',
                'tax_number' => 'TX-67890',
                'phone' => '963900000021',
                'email' => 'info@almsuper.sy',
                'governorate' => 'دمشق',
                'city' => 'دمشق',
                'address' => 'شارع الحمرا',
                'category' => 'retail',
                'status' => 'active',
                'monthly_volume_syp' => 20000000,
                'mdr_percentage' => 1.00,
                'mdr_min_syp' => 25000,
                'mdr_max_syp' => 250000,
                'max_txn_amount' => 2000000,
                'is_micro_merchant' => true,
                'approved_at' => $this->now,
            ],
            [
                'user_id' => $this->getUserId(6),
                'business_name' => "Mama's Restaurant",
                'business_name_ar' => 'مطعم ماما',
                'commercial_registration' => 'CR-2024-003',
                'tax_number' => 'TX-11111',
                'phone' => '963900000022',
                'email' => 'info@mama.sy',
                'governorate' => 'اللاذقية',
                'city' => 'اللاذقية',
                'address' => 'الكورنيش الغربي',
                'category' => 'food_beverage',
                'status' => 'active',
                'monthly_volume_syp' => 10000000,
                'mdr_percentage' => 1.25,
                'mdr_min_syp' => 15000,
                'mdr_max_syp' => 150000,
                'max_txn_amount' => 1000000,
                'is_micro_merchant' => true,
                'approved_at' => $this->now,
            ],
        ];

        foreach ($merchants as $merchant) {
            DB::table('merchants')->insert(array_merge($merchant, ['id' => $this->ulid('merc_')]));
        }
    }

    // ─── Merchant Stores ────────────────────────────────────────────────

    private function seedMerchantStores(): void
    {
        $merchantIds = DB::table('merchants')->pluck('id')->toArray();
        $stores = [
            ['merchant_id' => $merchantIds[0], 'name' => 'BezaTech Downtown', 'name_ar' => 'بيزا تك وسط المدينة', 'phone' => '963900000020', 'governorate' => 'دمشق', 'city' => 'دمشق', 'address' => 'شارع بغداد', 'is_active' => true],
            ['merchant_id' => $merchantIds[0], 'name' => 'BezaTech Online', 'name_ar' => 'بيزا تك أونلاين', 'phone' => null, 'governorate' => 'دمشق', 'city' => 'دمشق', 'address' => null, 'is_active' => true],
            ['merchant_id' => $merchantIds[1], 'name' => 'Al Sham Branch 1', 'name_ar' => 'الشام فرع ١', 'phone' => '963900000021', 'governorate' => 'دمشق', 'city' => 'دمشق', 'address' => 'شارع الحمرا', 'is_active' => true],
        ];

        foreach ($stores as $store) {
            DB::table('merchant_stores')->insert(array_merge($store, ['id' => $this->ulid()]));
        }
    }

    // ─── Bill Providers ──────────────────────────────────────────────────

    private function seedBillProviders(): void
    {
        $providers = [
            [
                'code' => 'SYRIATEL',
                'name' => 'Syriatel',
                'name_ar' => 'سيريتل',
                'category' => 'telecom',
                'account_label' => 'رقم الجوال',
                'account_format_regex' => '/^9639[3456]\d{7}$/',
                'supported_account_types' => ['mobile'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.syriatel.sy/bills'],
            ],
            [
                'code' => 'MTN',
                'name' => 'MTN Syria',
                'name_ar' => 'إم تي إن سوريا',
                'category' => 'telecom',
                'account_label' => 'رقم الجوال',
                'account_format_regex' => '/^9639[123]\d{7}$/',
                'supported_account_types' => ['mobile'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.mtn.sy/bills'],
            ],
            [
                'code' => 'PSEE_DAMASCUS',
                'name' => 'Damascus Electricity',
                'name_ar' => 'كهرباء دمشق',
                'category' => 'electricity',
                'account_label' => 'رقم المشترك',
                'account_format_regex' => '/^\d{10}$/',
                'supported_account_types' => ['electricity'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.psee.gov.sy/inquiry'],
            ],
            [
                'code' => 'DWSS_DAMASCUS',
                'name' => 'Damascus Water',
                'name_ar' => 'مياه دمشق',
                'category' => 'water',
                'account_label' => 'رقم المشترك',
                'account_format_regex' => '/^\d{8}$/',
                'supported_account_types' => ['water'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.dwss.gov.sy/inquiry'],
            ],
            [
                'code' => 'SYR_POST',
                'name' => 'Syrian Post',
                'name_ar' => 'البريد السوري',
                'category' => 'government',
                'account_label' => 'رقم المعاملة',
                'account_format_regex' => '/^\d{12}$/',
                'supported_account_types' => ['government'],
                'fee_percentage' => 0.50,
                'fee_min_syp' => 10000,
                'fee_max_syp' => 50000,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.syrianpost.gov.sy/payments'],
            ],
            [
                'code' => 'SUPPLY_MINISTRY',
                'name' => 'Supply Ministry',
                'name_ar' => 'وزارة التموين',
                'category' => 'government',
                'account_label' => 'رقم البطاقة',
                'account_format_regex' => '/^\d{9}$/',
                'supported_account_types' => ['government'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.ticare.gov.sy/inquiry'],
            ],
            [
                'code' => 'INTERNET_SD',
                'name' => 'Syrian Telecom Internet',
                'name_ar' => 'الإنترنت - الاتصالات السورية',
                'category' => 'internet',
                'account_label' => 'رقم الاشتراك',
                'account_format_regex' => '/^\d{8}$/',
                'supported_account_types' => ['internet'],
                'fee_percentage' => 0.00,
                'fee_min_syp' => 0,
                'fee_max_syp' => 0,
                'is_active' => true,
                'integration_config' => ['type' => 'api', 'endpoint' => 'https://api.ste.gov.sy/bills'],
            ],
        ];

        foreach ($providers as $provider) {
            DB::table('bill_providers')->insert(array_merge($provider, ['id' => $this->ulid()]));
        }
    }

    // ─── Remittance Corridors ────────────────────────────────────────────

    private function seedRemittanceCorridors(): void
    {
        $corridors = [
            [
                'name' => 'Syria → UAE Transfers',
                'source_country' => 'SY',
                'source_currency' => 'SYP',
                'target_currency' => 'AED',
                'fx_rate_source' => 'parallel',
                'fixed_spread_pct' => 2.00,
                'fee_type' => 'sliding',
                'fee_structure' => [
                    ['min' => 0, 'max' => 1000000, 'fee' => 50000],
                    ['min' => 1000001, 'max' => 5000000, 'fee' => 75000],
                    ['min' => 5000001, 'max' => 10000000, 'fee' => 100000],
                    ['min' => 10000001, 'max' => 50000000, 'fee' => 150000],
                ],
                'min_amount' => 100000,
                'max_amount' => 50000000,
                'daily_limit_per_sender' => 100000000,
                'monthly_limit_per_sender' => 500000000,
                'is_active' => true,
                'supported_payout_methods' => ['bank_deposit', 'cash_pickup', 'mobile_wallet'],
                'compliance_requirements' => ['valid_id', 'source_of_funds', 'relationship_proof'],
                'partner_name' => 'Al Ansari Exchange',
            ],
            [
                'name' => 'Syria → Saudi Arabia Transfers',
                'source_country' => 'SY',
                'source_currency' => 'SYP',
                'target_currency' => 'SAR',
                'fx_rate_source' => 'parallel',
                'fixed_spread_pct' => 2.50,
                'fee_type' => 'sliding',
                'fee_structure' => [
                    ['min' => 0, 'max' => 1000000, 'fee' => 50000],
                    ['min' => 1000001, 'max' => 5000000, 'fee' => 75000],
                ],
                'min_amount' => 100000,
                'max_amount' => 25000000,
                'daily_limit_per_sender' => 50000000,
                'monthly_limit_per_sender' => 250000000,
                'is_active' => true,
                'supported_payout_methods' => ['bank_deposit', 'cash_pickup'],
                'compliance_requirements' => ['valid_id', 'source_of_funds'],
                'partner_name' => 'Al Rajhi Bank',
            ],
            [
                'name' => 'Syria → Turkey Transfers',
                'source_country' => 'SY',
                'source_currency' => 'SYP',
                'target_currency' => 'TRY',
                'fx_rate_source' => 'parallel',
                'fixed_spread_pct' => 3.00,
                'fee_type' => 'flat',
                'fee_structure' => [['min' => 0, 'max' => 999999999, 'fee' => 75000]],
                'min_amount' => 100000,
                'max_amount' => 10000000,
                'daily_limit_per_sender' => 25000000,
                'monthly_limit_per_sender' => 100000000,
                'is_active' => true,
                'supported_payout_methods' => ['cash_pickup', 'mobile_wallet'],
                'compliance_requirements' => ['valid_id'],
                'partner_name' => 'PTT Transfer',
            ],
        ];

        foreach ($corridors as $corridor) {
            DB::table('corridors')->insert(array_merge($corridor, ['id' => $this->ulid()]));
        }
    }

    // ─── Savings Goals ───────────────────────────────────────────────────

    private function seedSavingsGoals(): void
    {
        $goals = [
            [
                'user_id' => $this->getUserId(2),
                'name' => 'New Car Fund',
                'name_ar' => 'صندوق السيارة الجديدة',
                'target_amount' => 50000000,
                'current_amount' => 12500000,
                'currency' => 'SYP',
                'status' => 'active',
                'target_date' => now()->addMonths(12)->toDateString(),
                'category' => 'vehicle',
                'icon' => 'directions_car',
                'color' => '#4CAF50',
                'auto_sweep_enabled' => true,
                'auto_sweep_amount' => 500000,
                'auto_sweep_frequency' => 'weekly',
                'completed_at' => null,
            ],
            [
                'user_id' => $this->getUserId(2),
                'name' => 'Hajj Savings',
                'name_ar' => 'ادخار الحج',
                'target_amount' => 100000000,
                'current_amount' => 30000000,
                'currency' => 'SYP',
                'status' => 'active',
                'target_date' => now()->addMonths(24)->toDateString(),
                'category' => 'travel',
                'icon' => 'flight',
                'color' => '#2196F3',
                'auto_sweep_enabled' => false,
                'auto_sweep_amount' => 0,
                'auto_sweep_frequency' => null,
                'completed_at' => null,
            ],
            [
                'user_id' => $this->getUserId(3),
                'name' => 'Education Fund',
                'name_ar' => 'صندوق التعليم',
                'target_amount' => 15000000,
                'current_amount' => 15000000,
                'currency' => 'SYP',
                'status' => 'completed',
                'target_date' => now()->subMonth()->toDateString(),
                'category' => 'education',
                'icon' => 'school',
                'color' => '#FF9800',
                'auto_sweep_enabled' => false,
                'auto_sweep_amount' => 0,
                'auto_sweep_frequency' => null,
                'completed_at' => now()->subDays(5)->toDateTimeString(),
            ],
        ];

        foreach ($goals as $goal) {
            DB::table('savings_goals')->insert(array_merge($goal, ['id' => $this->ulid('sv_')]));
        }
    }

    // ─── Cards ───────────────────────────────────────────────────────────

    private function seedCards(): void
    {
        $cards = [
            [
                'user_id' => $this->getUserId(2),
                'card_type' => 'debit',
                'status' => 'active',
                'cardholder_name' => 'AHMAD AL KHALED',
                'card_number_last4' => '4521',
                'expiry_month' => 12,
                'expiry_year' => 2028,
                'currency' => 'SYP',
                'daily_limit' => 5000000,
                'weekly_limit' => 25000000,
                'monthly_limit' => 100000000,
                'daily_used' => 0,
                'weekly_used' => 0,
                'monthly_used' => 0,
                'single_txn_limit' => 2000000,
                'is_virtual' => false,
                'international_enabled' => false,
                'atm_enabled' => true,
                'contactless_enabled' => true,
                'ecommerce_enabled' => true,
                'activated_at' => $this->now,
                'suspended_at' => null,
                'expires_at' => now()->addYears(4)->toDateTimeString(),
            ],
            [
                'user_id' => $this->getUserId(3),
                'card_type' => 'debit',
                'status' => 'active',
                'cardholder_name' => 'SARA AL AHMAD',
                'card_number_last4' => '7832',
                'expiry_month' => 6,
                'expiry_year' => 2027,
                'currency' => 'SYP',
                'daily_limit' => 2000000,
                'weekly_limit' => 10000000,
                'monthly_limit' => 40000000,
                'daily_used' => 500000,
                'weekly_used' => 2000000,
                'monthly_used' => 5000000,
                'single_txn_limit' => 1000000,
                'is_virtual' => true,
                'international_enabled' => true,
                'atm_enabled' => true,
                'contactless_enabled' => true,
                'ecommerce_enabled' => true,
                'activated_at' => $this->now,
                'suspended_at' => null,
                'expires_at' => now()->addYears(3)->toDateTimeString(),
            ],
        ];

        foreach ($cards as $card) {
            DB::table('cards')->insert(array_merge($card, ['id' => $this->ulid('card_')]));
        }
    }

    // ─── Loyalty Tiers ───────────────────────────────────────────────────

    private function seedLoyaltyTiers(): void
    {
        $tiers = [
            ['name' => 'Bronze', 'name_ar' => 'برونزي', 'level' => 1, 'min_points' => 0, 'points_multiplier' => 1.0, 'cashback_rate' => 0.0, 'benefits' => ['basic_support', 'monthly_offers'], 'is_active' => true],
            ['name' => 'Silver', 'name_ar' => 'فضي', 'level' => 2, 'min_points' => 1000, 'points_multiplier' => 1.25, 'cashback_rate' => 0.005, 'benefits' => ['priority_support', 'cashback_0.5', 'birthday_reward'], 'is_active' => true],
            ['name' => 'Gold', 'name_ar' => 'ذهبي', 'level' => 3, 'min_points' => 5000, 'points_multiplier' => 1.5, 'cashback_rate' => 0.01, 'benefits' => ['dedicated_support', 'cashback_1', 'fee_waivers', 'exclusive_offers'], 'is_active' => true],
            ['name' => 'Platinum', 'name_ar' => 'بلاتيني', 'level' => 4, 'min_points' => 25000, 'points_multiplier' => 2.0, 'cashback_rate' => 0.02, 'benefits' => ['personal_manager', 'cashback_2', 'all_fee_waivers', 'priority_routing', 'concierge'], 'is_active' => true],
        ];

        foreach ($tiers as $tier) {
            DB::table('loyalty_tiers')->insert(array_merge($tier, ['id' => $this->ulid('loy_')]));
        }
    }

    // ─── Payroll Employers ───────────────────────────────────────────────

    private function seedPayrollEmployers(): void
    {
        $employers = [
            [
                'user_id' => $this->getUserId(7),
                'company_name' => 'Beza Tech Ltd',
                'company_name_ar' => 'شركة بيزا للتكنولوجيا',
                'commercial_registration' => 'CR-PAY-001',
                'tax_number' => 'TX-EMP-001',
                'phone' => '963900000030',
                'email' => 'hr@beza.sy',
                'governorate' => 'دمشق',
                'city' => 'دمشق',
                'address' => 'شارع المزرعة',
                'status' => 'active',
                'monthly_payroll_limit' => 100000000,
                'used_monthly_payroll' => 35000000,
                'employee_count' => 25,
                'approved_at' => $this->now,
            ],
            [
                'user_id' => $this->getUserId(7),
                'company_name' => 'Al Wosta Trading',
                'company_name_ar' => 'شركة الوسطى التجارية',
                'commercial_registration' => 'CR-PAY-002',
                'tax_number' => 'TX-EMP-002',
                'phone' => '963900000031',
                'email' => 'payroll@alwosta.sy',
                'governorate' => 'حلب',
                'city' => 'حلب',
                'address' => 'شارع العزيزية',
                'status' => 'active',
                'monthly_payroll_limit' => 50000000,
                'used_monthly_payroll' => 15000000,
                'employee_count' => 12,
                'approved_at' => $this->now,
            ],
        ];

        foreach ($employers as $employer) {
            DB::table('employers')->insert(array_merge($employer, ['id' => $this->ulid('emp_')]));
        }
    }

    // ─── Loan Products ───────────────────────────────────────────────────

    private function seedLoanProducts(): void
    {
        $products = [
            ['name' => 'Personal Loan', 'name_ar' => 'قرض شخصي', 'min_amount' => 5000000, 'max_amount' => 50000000, 'interest_rate' => 8.5, 'min_term_days' => 90, 'max_term_days' => 1095, 'required_documents' => ['id', 'salary_certificate', 'bank_statement'], 'is_active' => true],
            ['name' => 'Business Loan', 'name_ar' => 'قرض تجاري', 'min_amount' => 25000000, 'max_amount' => 500000000, 'interest_rate' => 10.0, 'min_term_days' => 180, 'max_term_days' => 1825, 'required_documents' => ['id', 'commercial_registration', 'tax_certificate', 'financial_statements'], 'is_active' => true],
            ['name' => 'Education Loan', 'name_ar' => 'قرض تعليمي', 'min_amount' => 2000000, 'max_amount' => 25000000, 'interest_rate' => 5.0, 'min_term_days' => 90, 'max_term_days' => 730, 'required_documents' => ['id', 'acceptance_letter', 'fee_schedule'], 'is_active' => true],
            ['name' => 'Solar Energy Loan', 'name_ar' => 'قرض الطاقة الشمسية', 'min_amount' => 5000000, 'max_amount' => 50000000, 'interest_rate' => 6.0, 'min_term_days' => 180, 'max_term_days' => 1460, 'required_documents' => ['id', 'quotation', 'property_deed'], 'is_active' => true],
        ];

        foreach ($products as $product) {
            DB::table('loan_products')->insert(array_merge($product, ['id' => $this->ulid('lnp_')]));
        }
    }

    // ─── Education Institutions ──────────────────────────────────────────

    private function seedEducationInstitutions(): void
    {
        $institutions = [
            ['name' => 'University of Damascus', 'name_ar' => 'جامعة دمشق', 'code' => 'UD', 'type' => 'university', 'phone' => '96311000001', 'email' => 'info@damascusuniv.edu.sy', 'is_active' => true],
            ['name' => 'University of Aleppo', 'name_ar' => 'جامعة حلب', 'code' => 'UA', 'type' => 'university', 'phone' => '96321000001', 'email' => 'info@aleppouniv.edu.sy', 'is_active' => true],
            ['name' => 'Tishreen University', 'name_ar' => 'جامعة تشرين', 'code' => 'TU', 'type' => 'university', 'phone' => '96341000001', 'email' => 'info@tishreen.edu.sy', 'is_active' => true],
            ['name' => 'Al Baath University', 'name_ar' => 'جامعة البعث', 'code' => 'BU', 'type' => 'university', 'phone' => '96333000001', 'email' => 'info@albaath.edu.sy', 'is_active' => true],
            ['name' => 'Higher Institute of Business Administration', 'name_ar' => 'المعهد العالي لإدارة الأعمال', 'code' => 'HIBA', 'type' => 'institute', 'phone' => '96311000002', 'email' => 'info@hiba.edu.sy', 'is_active' => true],
            ['name' => 'Arabic Language Institute', 'name_ar' => 'معهد تعليم اللغة العربية', 'code' => 'ALI', 'type' => 'institute', 'phone' => '96311000003', 'email' => 'info@ali.edu.sy', 'is_active' => true],
        ];

        foreach ($institutions as $institution) {
            DB::table('education_institutions')->insert(array_merge($institution, ['id' => $this->ulid()]));
        }
    }

    // ─── Humanitarian Organizations ──────────────────────────────────────

    private function seedHumanitarianOrganizations(): void
    {
        $orgs = [
            ['name' => 'Syrian Arab Red Crescent', 'name_ar' => 'الهلال الأحمر العربي السوري', 'code' => 'SARC', 'type' => 'ngo', 'description' => 'National humanitarian organization providing relief and healthcare', 'description_ar' => 'منظمة إنسانية وطنية تقدم الإغاثة والرعاية الصحية', 'is_active' => true],
            ['name' => 'UNDP Syria', 'name_ar' => 'برنامج الأمم المتحدة الإنمائي في سوريا', 'code' => 'UNDP', 'type' => 'un', 'description' => 'UN Development Programme supporting recovery and resilience', 'description_ar' => 'برنامج الأمم المتحدة الإنمائي لدعم التعافي والصمود', 'is_active' => true],
            ['name' => 'WFP Syria', 'name_ar' => 'برنامج الغذاء العالمي في سوريا', 'code' => 'WFP', 'type' => 'un', 'description' => 'World Food Programme providing food assistance', 'description_ar' => 'برنامج الغذاء العالمي لتقديم المساعدات الغذائية', 'is_active' => true],
            ['name' => 'UNICEF Syria', 'name_ar' => 'اليونيسيف في سوريا', 'code' => 'UNICEF', 'type' => 'un', 'description' => 'UN Children\'s Fund supporting children and mothers', 'description_ar' => 'منظمة الأمم المتحدة للطفولة لدعم الأطفال والأمهات', 'is_active' => true],
            ['name' => 'Benevolence Aid', 'name_ar' => 'الإغاثة الخيرية', 'code' => 'BA', 'type' => 'ngo', 'description' => 'Local NGO providing humanitarian aid and development', 'description_ar' => 'منظمة محلية تقدم المساعدات الإنسانية والتنموية', 'is_active' => true],
        ];

        foreach ($orgs as $org) {
            DB::table('humanitarian_organizations')->insert(array_merge($org, ['id' => $this->ulid()]));
        }
    }

    // ─── Fraud Rules ─────────────────────────────────────────────────────

    private function seedFraudRules(): void
    {
        $rules = [
            ['name' => 'High Velocity Withdrawals', 'rule_type' => 'velocity', 'description' => 'Flag multiple withdrawals within 5 minutes', 'severity' => 'medium', 'parameters' => ['time_window_minutes' => 5, 'max_count' => 3, 'amount_threshold' => 5000000], 'risk_score' => 40, 'is_active' => true],
            ['name' => 'Unusual Large Transfer', 'rule_type' => 'amount', 'description' => 'Flag single transfer above threshold for KYC tier', 'severity' => 'high', 'parameters' => ['tier_0_max' => 500000, 'tier_1_max' => 2000000, 'tier_2_max' => 10000000], 'risk_score' => 70, 'is_active' => true],
            ['name' => 'New Device Login', 'rule_type' => 'device', 'description' => 'Flag login from unrecognized device', 'severity' => 'low', 'parameters' => ['require_otp' => true], 'risk_score' => 20, 'is_active' => true],
            ['name' => 'Round Amount Pattern', 'rule_type' => 'pattern', 'description' => 'Flag transactions in round amounts (suspected structuring)', 'severity' => 'medium', 'parameters' => ['amounts' => [1000000, 2000000, 5000000], 'time_window_days' => 1, 'min_count' => 3], 'risk_score' => 50, 'is_active' => true],
            ['name' => 'Geographic Anomaly', 'rule_type' => 'geo', 'description' => 'Flag transactions from unusual locations', 'severity' => 'high', 'parameters' => ['allowed_countries' => ['SY'], 'block_anomalous' => true], 'risk_score' => 80, 'is_active' => true],
        ];

        foreach ($rules as $rule) {
            DB::table('fraud_rules')->insert(array_merge($rule, ['id' => $this->ulid()]));
        }
    }

    // ─── Journal Entries (initial balances) ──────────────────────────────

    private function seedJournalEntries(): void
    {
        $ledgerAccounts = [];
        $accounts = DB::table('ledger_accounts')->where('is_system', true)->get(['id', 'code', 'type']);
        foreach ($accounts as $acc) {
            $ledgerAccounts[$acc->code] = $acc;
        }

        $entries = [
            [
                'description' => 'رصيد افتتاحي - النقدية التشغيلية',
                'debit_account' => '1001',
                'credit_account' => '2001',
                'amount' => 500000000,
            ],
            [
                'description' => 'تمويل محافظ العملاء',
                'debit_account' => '1001',
                'credit_account' => '2001',
                'amount' => 500000000,
            ],
        ];

        foreach ($entries as $entry) {
            $journalId = $this->ulid();
            DB::table('journal_entries')->insert([
                'id' => $journalId,
                'reference_type' => 'system',
                'reference_id' => $this->ulid(),
                'description' => $entry['description'],
                'entry_date' => now()->toDateString(),
                'currency' => 'SYP',
                'total_debit' => $entry['amount'],
                'total_credit' => $entry['amount'],
                'status' => 'posted',
                'created_by' => $this->getUserId(1),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            DB::table('journal_lines')->insert([
                ['id' => $this->ulid(), 'journal_entry_id' => $journalId, 'account_id' => $ledgerAccounts[$entry['debit_account']]->id, 'type' => 'debit', 'amount' => $entry['amount'], 'currency' => 'SYP', 'description' => $entry['description'], 'created_at' => $this->now, 'updated_at' => $this->now],
                ['id' => $this->ulid(), 'journal_entry_id' => $journalId, 'account_id' => $ledgerAccounts[$entry['credit_account']]->id, 'type' => 'credit', 'amount' => $entry['amount'], 'currency' => 'SYP', 'description' => $entry['description'], 'created_at' => $this->now, 'updated_at' => $this->now],
            ]);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function getUserId(int $index): string
    {
        static $ids = [];
        if (empty($ids)) {
            $users = DB::table('users')->orderBy('phone')->get(['id', 'phone']);
            foreach ($users as $u) {
                $ids[] = $u->id;
            }
        }
        return $ids[$index - 1] ?? '';
    }
}
