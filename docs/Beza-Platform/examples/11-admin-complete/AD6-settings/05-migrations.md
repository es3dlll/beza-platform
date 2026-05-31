# 05 - كود الميغريشن (Migrations)

## جدول الإعدادات

```php
<?php
// database/migrations/2024_06_01_000050_create_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->enum('group', ['general', 'fees', 'limits', 'exchange'])->default('general');
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // إدخال القيم الافتراضية
        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $defaults = [
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'kyc_required',     'value' => 'true',  'type' => 'boolean', 'group' => 'general'],
            ['key' => 'fee_transfer',     'value' => '0',     'type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_exchange',     'value' => '0.5',   'type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_card_load',    'value' => '1.5',   'type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_merchant_percent','value' => '2.5','type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_agent_cash_out','value' => '1.0',  'type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_withdraw_bank','value' => '1.0',   'type' => 'number',  'group' => 'fees'],
            ['key' => 'fee_deposit_card', 'value' => '2.5',   'type' => 'number',  'group' => 'fees'],
            ['key' => 'max_transfer_usd', 'value' => '2000',  'type' => 'number',  'group' => 'limits'],
            ['key' => 'max_transfer_syp', 'value' => '2000000','type' => 'number', 'group' => 'limits'],
            ['key' => 'min_deposit_usd',  'value' => '10',    'type' => 'number',  'group' => 'limits'],
            ['key' => 'min_deposit_syp',  'value' => '10000', 'type' => 'number',  'group' => 'limits'],
            ['key' => 'exchange_rate',    'value' => '13000',  'type' => 'number', 'group' => 'exchange'],
            ['key' => 'exchange_margin',  'value' => '0.5',   'type' => 'number',  'group' => 'exchange'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->insert($setting + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```
