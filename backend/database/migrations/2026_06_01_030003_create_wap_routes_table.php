<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wap_routes', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('pattern', 255);
            $table->string('target', 255);
            $table->json('roles')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $defaults = [
            ['method' => '*', 'pattern' => '/api/v1/wap/auth/login', 'target' => 'AuthController@login', 'roles' => ['*'], 'priority' => 0],
            ['method' => '*', 'pattern' => '/api/v1/wap/wallet/transfer', 'target' => 'WalletController@transfer', 'roles' => ['user', 'merchant'], 'priority' => 1],
            ['method' => 'GET', 'pattern' => '/api/v1/wap/merchant/*', 'target' => 'MerchantController', 'roles' => ['merchant'], 'priority' => 2],
            ['method' => 'GET', 'pattern' => '/api/v1/wap/agent/*', 'target' => 'AgentController', 'roles' => ['agent'], 'priority' => 3],
        ];

        foreach ($defaults as $route) {
            \App\Models\WapRoute::create($route);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wap_routes');
    }
};
