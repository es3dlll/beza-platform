<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'healthy',
                ],
            ]);
    }

    public function test_health_endpoint_contains_required_fields(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'timestamp',
                'app',
                'env',
                'php_version',
                'laravel_version',
            ],
            'meta' => [
                'checks' => [
                    'database',
                    'cache',
                    'storage',
                ],
            ],
        ]);
    }
}
