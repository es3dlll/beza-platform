<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'id' => (string) str()->ulid(),
            'phone' => '0000000000',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
