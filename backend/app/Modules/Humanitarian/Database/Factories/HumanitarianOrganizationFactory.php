<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Humanitarian\Models\HumanitarianOrganization;

final class HumanitarianOrganizationFactory extends Factory
{
    protected $model = HumanitarianOrganization::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->company,
            'code' => $this->faker->unique()->lexify('NGO-????'),
            'type' => $this->faker->randomElement(['ngo', 'un', 'red_crescent', 'charity']),
            'is_approved' => false,
            'status' => 'pending',
            'metadata' => null,
        ];
    }
}
