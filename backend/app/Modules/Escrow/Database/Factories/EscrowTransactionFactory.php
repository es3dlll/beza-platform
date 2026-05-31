<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Database\Factories;

use App\Models\User;
use App\Modules\Escrow\Models\EscrowTransaction;
use App\Modules\Marketplace\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EscrowTransactionFactory extends Factory
{
    protected $model = EscrowTransaction::class;

    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'seller_id' => Seller::factory(),
            'marketplace_ref_id' => 'PROD-' . $this->faker->unique()->numerify('######'),
            'amount_fils' => $this->faker->randomElement([100_000, 250_000, 500_000, 1_000_000]),
            'fee_fils' => fn(array $a) => (int)round($a['amount_fils'] * 0.01),
            'status' => 'initiated',
        ];
    }

    public function funded(): static
    {
        return $this->state(fn() => ['status' => 'funded']);
    }

    public function released(): static
    {
        return $this->state(fn() => ['status' => 'released']);
    }

    public function disputed(): static
    {
        return $this->state(fn() => ['status' => 'disputed']);
    }
}
