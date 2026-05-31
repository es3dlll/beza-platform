<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->string('remittance_id', 32)->unique();
            $table->string('idempotency_key', 64)->unique()->index();
            $table->string('sender_id', 32)->index();
            $table->string('recipient_name', 255);
            $table->string('recipient_phone', 20);
            $table->string('recipient_country', 2);
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->bigInteger('source_amount');
            $table->bigInteger('destination_amount');
            $table->integer('buy_rate');
            $table->integer('spread_bps');
            $table->bigInteger('fee_amount');
            $table->bigInteger('total_charge');
            $table->string('status', 20)->default('PENDING')->index();
            $table->string('compliance_tier', 20)->default('LOW');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->json('audit_trail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
