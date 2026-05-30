<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\IAM\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'slug' => $this->faker->unique()->slug(1),
            'description' => $this->faker->sentence,
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_system' => true,
        ]);
    }
}
