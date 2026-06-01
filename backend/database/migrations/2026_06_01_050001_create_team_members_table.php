<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->string('role', 30);
            $table->unsignedTinyInteger('level');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->bigInteger('daily_deposit_limit')->nullable();
            $table->bigInteger('daily_withdrawal_limit')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
