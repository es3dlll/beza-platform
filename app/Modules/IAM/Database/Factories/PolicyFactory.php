<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\IAM\Models\Policy;

class PolicyFactory extends Factory
{
    protected $model = Policy::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'resource' => $this->faker->word,
            'action' => $this->faker->randomElement(['create', 'read', 'update', 'delete']),
            'effect' => $this->faker->randomElement(['allow', 'deny']),
            'conditions' => null,
        ];
    }
}
