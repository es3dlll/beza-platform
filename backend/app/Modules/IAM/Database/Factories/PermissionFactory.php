<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\IAM\Models\Permission;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->unique()->word,
            'slug' => $this->faker->unique()->slug(1),
            'group' => $this->faker->word,
            'description' => $this->faker->sentence,
        ];
    }
}
