# 08 - بذور البيانات (Seeders)

## RolesAndPermissionsSeeder

```php
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء المستخدم المشرف
        $admin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'مدير النظام',
            'email' => 'admin@beza.com',
            'phone' => '963900000000',
            'password' => Hash::make('Admin@123'),
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
            'kyc_status' => 'verified',
            'is_admin' => true,
        ]);

        // إنشاء محافظ للمشرف
        foreach (['SYP' => 1000000, 'USD' => 10000] as $currency => $balance) {
            Wallet::create([
                'user_id' => $admin->id,
                'currency' => $currency,
                'wallet_number' => '62' . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'balance' => $balance,
                'is_active' => true,
            ]);
        }
    }
}
```

## SettingsSeeder

```php
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'transfer_daily_limit_usd', 'value' => '2000', 'group' => 'transfers'],
            ['key' => 'transfer_daily_limit_syp', 'value' => '2000000', 'group' => 'transfers'],
            ['key' => 'transfer_fee_percentage', 'value' => '0', 'group' => 'transfers'],
            ['key' => 'exchange_rate_usd_syp', 'value' => '13000', 'group' => 'exchange'],
            ['key' => 'max_pin_attempts', 'value' => '5', 'group' => 'security'],
            ['key' => 'pin_lockout_minutes', 'value' => '15', 'group' => 'security'],
            ['key' => 'kyc_required_for_amount', 'value' => '500', 'group' => 'kyc'],
            ['key' => 'referral_reward_amount', 'value' => '5', 'group' => 'referrals'],
            ['key' => 'agent_commission_rate', 'value' => '0.5', 'group' => 'agents'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'system'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
```

## تشغيل Seeders

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=InvestmentPoolsSeeder
```
