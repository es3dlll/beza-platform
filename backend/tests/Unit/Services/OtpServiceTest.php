<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Events\OtpGenerated;
use Modules\Identity\Models\OtpCode;
use Modules\Identity\Models\User;
use Modules\Identity\Services\OtpService;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpService = $this->app->make(OtpService::class);
    }

    public function test_generates_six_digit_otp(): void
    {
        Event::fake([OtpGenerated::class]);
        $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', $event->code);
            $this->assertSame('963123456789', $event->phone);
            $this->assertSame(OtpService::PURPOSE_REGISTER, $event->purpose);
            return true;
        });
    }

    public function test_otp_has_expiry_time(): void
    {
        $otp = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $this->assertNotNull($otp->expires_at);
        $this->assertTrue($otp->expires_at->isFuture());
    }

    public function test_otp_is_stored_as_hash(): void
    {
        $otp = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $this->assertStringStartsWith('$2y$', $otp->code_hash);
    }

    public function test_verify_returns_true_for_valid_otp(): void
    {
        $plainCode = '';
        Event::fake([OtpGenerated::class]);
        $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);
        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) use (&$plainCode) {
            $plainCode = $event->code;
            return true;
        });

        $result = $this->otpService->verify('963123456789', $plainCode, OtpService::PURPOSE_REGISTER);

        $this->assertTrue($result);
    }

    public function test_verify_returns_false_for_wrong_code(): void
    {
        $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $result = $this->otpService->verify('963123456789', '000000', OtpService::PURPOSE_REGISTER);

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_expired_otp(): void
    {
        OtpCode::create([
            'user_id' => null,
            'phone' => '963123456789',
            'purpose' => OtpService::PURPOSE_REGISTER,
            'code_hash' => bcrypt('123456'),
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->subMinutes(10),
        ]);

        $result = $this->otpService->verify('963123456789', '123456', OtpService::PURPOSE_REGISTER);

        $this->assertFalse($result);
    }

    public function test_verify_increments_attempts_on_failure(): void
    {
        $otp = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $this->otpService->verify('963123456789', 'wrong1', OtpService::PURPOSE_REGISTER);
        $this->otpService->verify('963123456789', 'wrong2', OtpService::PURPOSE_REGISTER);

        $otp->refresh();
        $this->assertEquals(2, $otp->attempts);
    }

    public function test_rate_limiting_blocks_after_max_attempts(): void
    {
        $otp = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        for ($i = 0; $i < 5; $i++) {
            $otp->increment('attempts');
        }

        $result = $this->otpService->verify('963123456789', '000000', OtpService::PURPOSE_REGISTER);

        $this->assertFalse($result);
    }

    public function test_verified_at_is_set_on_successful_verification(): void
    {
        $plainCode = '';
        Event::fake([OtpGenerated::class]);
        $otp = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);
        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) use (&$plainCode) {
            $plainCode = $event->code;
            return true;
        });

        $this->otpService->verify('963123456789', $plainCode, OtpService::PURPOSE_REGISTER);

        $otp->refresh();
        $this->assertNotNull($otp->verified_at);
    }

    public function test_generating_new_otp_invalidates_previous_unverified_codes(): void
    {
        $first = $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $this->otpService->generate('963123456789', OtpService::PURPOSE_REGISTER);

        $first->refresh();
        $this->assertTrue($first->expires_at->isPast());
    }

    public function test_generate_and_send_works(): void
    {
        $otp = $this->otpService->generateAndSend('963123456789', OtpService::PURPOSE_REGISTER);

        $this->assertInstanceOf(OtpCode::class, $otp);
        $this->assertDatabaseHas('otp_codes', ['id' => $otp->id]);
    }
}
