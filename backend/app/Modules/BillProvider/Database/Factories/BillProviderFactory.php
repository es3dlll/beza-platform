<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Database\Factories;

use App\Modules\BillProvider\Models\BillProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BillProviderFactory extends Factory
{
    protected $model = BillProvider::class;

    public function definition(): array
    {
        $categories = ['electricity', 'water', 'telecom', 'internet', 'gas', 'insurance'];
        $category = $this->faker->randomElement($categories);

        return [
            'name' => match ($category) {
                'electricity' => 'الشركة العامة للكهرباء',
                'water' => 'مؤسسة المياه',
                'telecom' => 'الاتصالات السورية',
                'internet' => 'شبكة بيزا',
                'gas' => 'الغاز السوري',
                'insurance' => 'التأمين السوري',
            },
            'category' => $category,
            'external_id' => 'EXT-' . strtoupper($category) . '-' . $this->faker->unique()->numerify('####'),
            'is_active' => true,
            'logo_url' => null,
            'support_phone' => $this->faker->phoneNumber(),
            'config' => [
                'payment_endpoint' => 'https://api.example.com/pay/' . $category,
                'callback_enabled' => true,
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attrs) => ['is_active' => false]);
    }
}
