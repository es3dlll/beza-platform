<?php

declare(strict_types=1);

namespace Modules\USSD\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\USSD\Services\UssdMenuEngine;
use Tests\TestCase;

class UssdTest extends TestCase
{
    use RefreshDatabase;

    private UssdMenuEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = $this->app->make(UssdMenuEngine::class);
    }

    public function test_initial_menu_returns_main_options(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-1',
            msisdn: '963900000001',
            text: '*123#',
        );

        $this->assertEquals('menu', $result['action']);
        $this->assertStringContainsString('مرحباً', $result['text']);
        $this->assertStringContainsString('الرصيد', $result['text']);
    }

    public function test_empty_text_returns_menu(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-2',
            msisdn: '963900000001',
            text: '',
        );

        $this->assertEquals('menu', $result['action']);
        $this->assertStringContainsString('مرحباً', $result['text']);
    }

    public function test_invalid_option_returns_error(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-3',
            msisdn: '963900000001',
            text: '9',
        );

        $this->assertStringContainsString('غير صحيح', $result['text']);
    }

    public function test_invalid_pin_rejected(): void
    {
        // First select option 4 (change PIN)
        $this->engine->handle(
            sessionId: 'test-session-4',
            msisdn: '963900000001',
            text: '4',
        );

        // Then enter invalid pin (not 6 digits)
        $result = $this->engine->handle(
            sessionId: 'test-session-4',
            msisdn: '963900000001',
            text: '123',
        );

        $this->assertStringContainsString('6 أرقام', $result['text']);
    }

    public function test_balance_returns_end_action(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-5',
            msisdn: '963900000001',
            text: '1',
        );

        $this->assertEquals('end', $result['action']);
    }

    public function test_mini_statement_returns_end_action(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-6',
            msisdn: '963900000001',
            text: '2',
        );

        $this->assertEquals('end', $result['action']);
    }

    public function test_agent_locator_returns_end_action(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-7',
            msisdn: '963900000001',
            text: '3',
        );

        $this->assertEquals('end', $result['action']);
    }

    public function test_language_toggle_detected(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-8',
            msisdn: '963900000001',
            text: '*123*2#',
        );

        $this->assertStringContainsString('اللغة', $result['text']);
    }

    public function test_end_action_clears_session(): void
    {
        $result = $this->engine->handle(
            sessionId: 'test-session-9',
            msisdn: '963900000001',
            text: '1',
        );

        $this->assertEquals('', $result['session_id']);
        $this->assertEquals('end', $result['action']);
    }
}
