<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 20)->nullable()->unique();
            $table->string('account_number', 50)->nullable()->unique();
            $table->string('name', 200);
            $table->string('name_ar', 200)->nullable();
            $table->string('type', 20);
            $table->string('category', 50)->nullable();
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('available_balance')->default(0);
            $table->bigInteger('balance_usd')->default(0);
            $table->string('owner_type')->nullable();
            $table->string('owner_id')->nullable();
            $table->string('parent_id', 26)->nullable();
            $table->text('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
            $table->string('module', 50)->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('ledger_accounts');

            $table->index('type');
            $table->index('owner_type');
            $table->index('owner_id');
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');
    }
};
