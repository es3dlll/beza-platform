<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\OpenFinance\Models\OpenFinanceApp;

final class OpenFinanceAppFactory extends Factory
{
    protected $model = OpenFinanceApp::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'app_id' => $this->faker->unique()->uuid,
            'app_secret' => Str::random(32),
            'redirect_uris' => json_encode([$this->faker->url]),
            'scopes' => json_encode(['profile:read', 'transactions:read']),
            'is_active' => true,
        ];
    }
}
