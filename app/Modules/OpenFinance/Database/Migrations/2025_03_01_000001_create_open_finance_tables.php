<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_finance_apps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('name', 100);
            $table->string('redirect_uris', 500);
            $table->string('client_id', 64)->unique();
            $table->string('client_secret', 128);
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::create('open_finance_consents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('app_id');
            $table->json('granted_scopes');
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('app_id')->references('id')->on('open_finance_apps');
        });
        Schema::create('open_finance_access_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('consent_id');
            $table->string('token', 128);
            $table->json('scopes');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->foreign('consent_id')->references('id')->on('open_finance_consents');
        });
    }

    public function down(): void { Schema::dropIfExists('open_finance_access_tokens'); Schema::dropIfExists('open_finance_consents'); Schema::dropIfExists('open_finance_apps'); }
};
