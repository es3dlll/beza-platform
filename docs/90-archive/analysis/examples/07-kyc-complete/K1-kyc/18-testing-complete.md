# 18 - كل الاختبارات (Testing Complete)

## Feature Test — KycTest

```php
<?php
// tests/Feature/KycTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class KycTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status'     => 'active',
            'kyc_status' => 'not_submitted',
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function user_can_submit_kyc_documents()
    {
        $response = $this->withToken($this->token)->post('/api/v1/kyc/submit', [
            'front_id'      => UploadedFile::fake()->image('front.jpg', 1000, 800),
            'back_id'       => UploadedFile::fake()->image('back.jpg', 1000, 800),
            'selfie'        => UploadedFile::fake()->image('selfie.jpg', 1000, 800),
            'address_proof' => UploadedFile::fake()->image('address.jpg', 1000, 800),
            'doc_type'      => 'ID',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('pending', $this->user->fresh()->kyc_status);
    }

    /** @test */
    public function cannot_submit_while_pending()
    {
        $this->user->update(['kyc_status' => 'pending']);

        $response = $this->withToken($this->token)->post('/api/v1/kyc/submit', [
            'front_id'      => UploadedFile::fake()->image('front.jpg'),
            'back_id'       => UploadedFile::fake()->image('back.jpg'),
            'selfie'        => UploadedFile::fake()->image('selfie.jpg'),
            'address_proof' => UploadedFile::fake()->image('address.jpg'),
            'doc_type'      => 'ID',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function requires_all_documents()
    {
        $response = $this->withToken($this->token)->post('/api/v1/kyc/submit', [
            'doc_type' => 'ID',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function rejects_small_images()
    {
        $response = $this->withToken($this->token)->post('/api/v1/kyc/submit', [
            'front_id'      => UploadedFile::fake()->image('front.jpg', 100, 100),
            'back_id'       => UploadedFile::fake()->image('back.jpg', 100, 100),
            'selfie'        => UploadedFile::fake()->image('selfie.jpg', 100, 100),
            'address_proof' => UploadedFile::fake()->image('address.jpg', 100, 100),
            'doc_type'      => 'ID',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('rejected', $this->user->fresh()->kyc_status);
    }

    /** @test */
    public function user_can_check_status()
    {
        $response = $this->withToken($this->token)->get('/api/v1/kyc/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.status.kyc_status', 'not_submitted');
    }
}
```
