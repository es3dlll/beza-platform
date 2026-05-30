<?php

declare(strict_types=1);

namespace Modules\Remittance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Remittance\Models\Beneficiary;

final class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'full_name' => $this->faker->name,
            'phone' => $this->faker->numerify('9639########'),
            'bank_name' => $this->faker->randomElement(['BSO', 'SIIB', 'Bemo Saudi Fransi']),
            'bank_account' => $this->faker->numerify('################'),
            'iban' => $this->faker->regexify('SY[0-9]{20}'),
            'relationship' => $this->faker->randomElement(['family', 'friend', 'other']),
            'country' => 'SY',
            'city' => $this->faker->city,
            'is_active' => true,
        ];
    }
}
