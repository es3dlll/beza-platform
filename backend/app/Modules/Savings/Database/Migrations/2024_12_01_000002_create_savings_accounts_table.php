<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('savings_goal_id')->nullable();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('total_contributions')->default(0);
            $table->bigInteger('total_profit')->default(0);
            $table->bigInteger('total_withdrawn')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_contribution_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('savings_goal_id')->references('id')->on('savings_goals');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
