# 18 - كل الاختبارات (Testing Complete)

## Feature Test — LandingContactTest

```php
<?php
// tests/Feature/Landing/LandingContactTest.php

namespace Tests\Feature\Landing;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LandingContactTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_submits_contact_form()
    {
        $response = $this->postJson('/api/contact', [
            'name'    => 'أحمد محمد',
            'email'   => 'ahmed@beza.example',
            'subject' => 'استفسار عن الخدمات',
            'message' => 'أرغب في معرفة المزيد عن خدمات Beza للمدفوعات الرقمية',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إرسال رسالتك بنجاح، سنتواصل معك قريباً',
            ]);

        $this->assertDatabaseHas('contacts', [
            'email'   => 'ahmed@beza.example',
            'subject' => 'استفسار عن الخدمات',
            'is_read' => false,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $response = $this->postJson('/api/contact', [
            'name'    => 'أحمد',
            'email'   => 'not-an-email',
            'subject' => 'موضوع',
            'message' => 'رسالة طويلة بما يكفي',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_message_min_length()
    {
        $response = $this->postJson('/api/contact', [
            'name'    => 'أحمد',
            'email'   => 'ahmed@beza.example',
            'subject' => 'موضوع',
            'message' => 'قصيرة',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_accepts_optional_phone()
    {
        $response = $this->postJson('/api/contact', [
            'name'    => 'أحمد',
            'email'   => 'ahmed@beza.example',
            'phone'   => '963944123456',
            'subject' => 'موضوع',
            'message' => 'رسالة اختبارية طويلة بما يكفي',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', ['phone' => '963944123456']);
    }

    /** @test */
    public function it_handles_multiple_submissions()
    {
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/contact', [
                'name'    => "مستخدم {$i}",
                'email'   => "user{$i}@beza.example",
                'subject' => 'موضوع',
                'message' => 'رسالة اختبارية طويلة بما يكفي للتحقق',
            ]);

            $response->assertStatus(201);
        }

        $this->assertCount(3, Contact::all());
    }
}
```

## Feature Test — NewsletterTest

```php
<?php
// tests/Feature/Landing/NewsletterTest.php

namespace Tests\Feature\Landing;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_subscribes_email()
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email'  => 'user@beza.example',
            'source' => 'footer',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم الاشتراك في النشرة البريدية بنجاح',
            ]);

        $this->assertDatabaseHas('subscribers', [
            'email'     => 'user@beza.example',
            'is_active' => true,
            'source'    => 'footer',
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_email()
    {
        Subscriber::factory()->create(['email' => 'user@beza.example']);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'user@beza.example',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_unsubscribes_email()
    {
        Subscriber::factory()->create([
            'email'     => 'user@beza.example',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/newsletter/unsubscribe', [
            'email' => 'user@beza.example',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('subscribers', [
            'email'     => 'user@beza.example',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_validates_email_required()
    {
        $response = $this->postJson('/api/newsletter/subscribe', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
```

## Feature Test — MerchantInquiryTest

```php
<?php
// tests/Feature/Landing/MerchantInquiryTest.php

namespace Tests\Feature\Landing;

use App\Models\MerchantInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantInquiryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_submits_merchant_inquiry()
    {
        $response = $this->postJson('/api/merchant-inquiry', [
            'company_name'  => 'متجر النور',
            'contact_name'  => 'أحمد محمد',
            'email'         => 'ahmed@store.com',
            'phone'         => '963944123456',
            'business_type' => 'ملابس',
            'monthly_volume'=> 50000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('merchant_inquiries', [
            'company_name' => 'متجر النور',
            'status'       => 'new',
        ]);
    }

    /** @test */
    public function it_validates_merchant_inquiry()
    {
        $response = $this->postJson('/api/merchant-inquiry', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'contact_name', 'email', 'phone']);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/Landing/LandingPestTest.php

use App\Models\Subscriber;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

test('submits contact form', function () {
    postJson('/api/contact', [
        'name' => 'أحمد', 'email' => 'a@b.com',
        'subject' => 'موضوع', 'message' => 'رسالة طويلة بما يكفي',
    ])->assertStatus(201)->assertJson(['success' => true]);
});

test('subscribes to newsletter', function () {
    postJson('/api/newsletter/subscribe', ['email' => 'test@test.com'])
        ->assertStatus(201);
    assertDatabaseHas('subscribers', ['email' => 'test@test.com', 'is_active' => true]);
});

test('rejects duplicate subscription', function () {
    Subscriber::factory()->create(['email' => 'dup@test.com']);
    postJson('/api/newsletter/subscribe', ['email' => 'dup@test.com'])
        ->assertStatus(422);
});
```

## Next.js Tests (Jest)

```typescript
// __tests__/components/Hero.test.tsx
import { render, screen } from '@testing-library/react';
import Hero from '@/components/Hero';

describe('Hero', () => {
  it('renders headline', () => {
    render(<Hero />);
    expect(screen.getByText('حوّل أموالك بسهولة وأمان')).toBeTruthy();
  });

  it('renders download buttons', () => {
    render(<Hero />);
    expect(screen.getByText('Google Play')).toBeTruthy();
    expect(screen.getByText('App Store')).toBeTruthy();
  });
});
```

```typescript
// __tests__/components/FAQ.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import FAQ from '@/components/FAQ';

describe('FAQ', () => {
  it('toggles answer on click', () => {
    render(<FAQ />);
    const button = screen.getByText('ما هي Beza؟');
    fireEvent.click(button);
    expect(screen.getByText(/منصة رقمية للمدفوعات/)).toBeTruthy();
  });
});
```

## تشغيل الاختبارات

```bash
# Laravel tests
php artisan test --filter=Landing
php artisan test --filter=NewsletterTest

# Next.js tests
npm test
npm run test -- --coverage

# Pest
./vendor/bin/pest --filter=LandingPestTest
```
