<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Database\Factories;

use App\Modules\FinancialCore\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'type' => 'post',
            'status' => 'posted',
            'wallet_id' => Str::ulid()->toBase32(),
            'from_account_id' => Str::ulid()->toBase32(),
            'to_account_id' => Str::ulid()->toBase32(),
            'amount' => 100000,
            'currency' => 'SYP',
            'fee_amount' => 0,
            'description' => 'Test transaction',
            'description_ar' => 'معاملة اختبار',
        ];
    }
}
