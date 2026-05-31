<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Database\Factories;

use App\Modules\Escrow\Models\DisputeCase;
use App\Modules\Escrow\Models\EscrowTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

final class DisputeCaseFactory extends Factory
{
    protected $model = DisputeCase::class;

    public function definition(): array
    {
        return [
            'escrow_transaction_id' => EscrowTransaction::factory(),
            'raised_by' => fn(array $a) => EscrowTransaction::find($a['escrow_transaction_id'])?->buyer_id ?? 'unknown',
            'reason' => $this->faker->randomElement(['product_not_received', 'damaged', 'wrong_item', 'quality_issue', 'other']),
            'description' => $this->faker->paragraph(),
            'documents' => [$this->faker->imageUrl()],
            'status' => 'open',
            'decision' => null,
            'decision_reason' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn() => [
            'status' => 'resolved',
            'decision' => 'buyer',
            'decision_reason' => 'تم التحقق من صحة ادعاء المشتري',
            'resolved_at' => now(),
        ]);
    }
}
