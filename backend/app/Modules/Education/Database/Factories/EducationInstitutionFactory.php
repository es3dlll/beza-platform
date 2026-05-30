<?php

declare(strict_types=1);

namespace Modules\Education\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Education\Models\EducationInstitution;

class EducationInstitutionFactory extends Factory
{
    protected $model = EducationInstitution::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->company . ' University',
            'code' => $this->faker->unique()->lexify('UNI-????'),
            'type' => $this->faker->randomElement(['university', 'school', 'institute', 'center']),
            'governorate' => $this->faker->randomElement(['دمشق', 'حلب', 'حمص', 'اللاذقية', 'طرطوس']),
            'is_active' => true,
        ];
    }
}
