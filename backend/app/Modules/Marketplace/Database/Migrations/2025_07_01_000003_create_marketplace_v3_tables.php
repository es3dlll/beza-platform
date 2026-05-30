<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 50);
            $table->string('name_ar', 50);
            $table->json('governorates');
            $table->bigInteger('base_fee');
            $table->bigInteger('per_kg_fee')->default(0);
            $table->integer('estimated_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id');
            $table->string('carrier', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('shipping_address');
            $table->string('governorate', 50);
            $table->string('recipient_name', 100);
            $table->string('recipient_phone', 20);
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('cod_collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('shipment_id');
            $table->ulid('order_id');
            $table->bigInteger('amount');
            $table->ulid('agent_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('remitted_at')->nullable();
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 10)->default('digital')->after('type');
        });

        DB::table('shipping_zones')->insert([
            [
                'id' => (string) str()->ulid(),
                'name' => 'Zone 1 - Damascus & Rural Damascus',
                'name_ar' => 'المنطقة 1 - دمشق وريف دمشق',
                'governorates' => json_encode(['Damascus', 'Rural Damascus']),
                'base_fee' => 5000,
                'per_kg_fee' => 1000,
                'estimated_days' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) str()->ulid(),
                'name' => 'Zone 2 - Coastal & Central',
                'name_ar' => 'المنطقة 2 - الساحل والوسط',
                'governorates' => json_encode(['Aleppo', 'Homs', 'Hama', 'Latakia', 'Tartous']),
                'base_fee' => 7000,
                'per_kg_fee' => 1500,
                'estimated_days' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) str()->ulid(),
                'name' => 'Zone 3 - Remote Areas',
                'name_ar' => 'المنطقة 3 - المناطق النائية',
                'governorates' => json_encode([
                    'Deir ez-Zor', 'Al-Hasakah', 'Ar-Raqqa', 'Daraa',
                    'As-Suwayda', 'Quneitra', 'Idlib',
                ]),
                'base_fee' => 10000,
                'per_kg_fee' => 2000,
                'estimated_days' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });

        Schema::dropIfExists('cod_collections');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_zones');
    }
};
