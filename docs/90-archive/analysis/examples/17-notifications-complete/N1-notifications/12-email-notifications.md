# 12 - إشعارات البريد الإلكتروني (Email Notifications)

```php
<?php

namespace App\Services\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MailChannel
{
    public function send(User $user, array $compiled, array $data): array
    {
        $email = $user->email;
        if (!$email) {
            return ['success' => false, 'error' => 'User has no email'];
        }

        try {
            Mail::html($compiled['body'], function ($message) use ($user, $compiled) {
                $message->to($user->email, $user->name)
                    ->subject($compiled['title'])
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return ['success' => true, 'email' => $email];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Email failed for {$email}: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

## Mailable Class

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.notification', [
                'title' => $this->title,
                'body' => $this->body,
            ])->render(),
        );
    }
}
```

## Blade Template

```blade
{{-- resources/views/emails/notification.blade.php --}}
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f4f4; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="20" style="background: white; border-radius: 10px;">
                    <tr>
                        <td style="text-align: center; padding: 30px 0;">
                            <img src="{{ asset('images/logo.png') }}" alt="Beza" height="50">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h2 style="color: #333;">{{ $title }}</h2>
                            <p style="color: #666; line-height: 1.8;">{{ $body }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```
