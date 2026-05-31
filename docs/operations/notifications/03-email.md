# Email Notification Pattern

> Single source of truth for email delivery across ALL Beza Platform features.

## Delivery Architecture

```
[Feature Service] → Event (RabbitMQ) → [Email Service] → Laravel Mail (SMTP) → Recipient
                                                      ↘ Database (log + attachments)
```

### Configuration
```php
// config/mail.php
'default' => env('MAIL_MAILER', 'ses'),
'mailers' => [
    'ses' => [
        'transport' => 'ses',
        'key' => env('AWS_SES_ACCESS_KEY_ID'),
        'secret' => env('AWS_SES_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'me-south-1'),
    ],
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST', 'email-smtp.me-south-1.amazonaws.com'),
        'port' => 587,
        'encryption' => 'tls',
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],
],
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'noreply@beza.com'),
    'name' => env('MAIL_FROM_NAME', 'Beza Pay'),
],
'reply_to' => [
    'address' => env('MAIL_REPLY_TO', 'support@beza.com'),
    'name' => 'دعم بيزا',
],
```

## Templates

### Blade Mailables
All emails use Laravel Mailables with Blade templates:

```php
// app/Mail/TransferReceipt.php
class TransferReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transfer $transfer,
        public User $recipient,
    ) {}

    public function build(): self
    {
        $locale = $this->recipient->preferred_locale ?? 'ar';

        return $this
            ->from('noreply@beza.com', 'Beza Pay')
            ->subject(__('emails.transfer_receipt.subject', [], $locale))
            ->view("emails.{$locale}.transfer-receipt")
            ->attachData(
                $this->generatePdfReceipt(),
                'receipt-' . $this->transfer->reference_id . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
```

### Template List

| Template Key | Subject (AR) | Subject (EN) | Frequency |
|-------------|-------------|-------------|-----------|
| `welcome` | مرحباً بك في بيزا! | Welcome to Beza! | Once (registration) |
| `transfer_receipt` | إيصال تحويل - {reference_id} | Transfer Receipt - {reference_id} | Per transfer |
| `kyc_approved` | تم قبول طلب التوثيق | KYC Application Approved | Once |
| `kyc_rejected` | تم رفض طلب التوثيق | KYC Application Rejected | Once |
| `monthly_statement` | كشف حساب شهري - {month} | Monthly Statement - {month} | Monthly |
| `password_changed` | تم تغيير كلمة المرور | Password Changed | On change |
| `security_alert` | تنبيه أمني | Security Alert | On suspicious login |
| `promotional` | {campaign_title} | {campaign_title} | Marketing (opt-in) |

### Template Structure (Arabic Example)
```blade
{{-- resources/views/emails/ar/transfer-receipt.blade.php --}}
@extends('emails.ar.layout')

@section('content')
<table dir="rtl" style="width:100%;font-family:'Tajawal',sans-serif;">
    <tr>
        <td style="padding:20px;text-align:center;">
            <h1 style="color:#1B5E20;">إيصال تحويل</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:10px 20px;">
            <p>مرحباً {{ $recipient->full_name }}،</p>
            <p>تم تحويل <strong>{{ number_format($transfer->amount, 0) }} ل.س</strong> بنجاح.</p>
            <p>رقم المرجع: <strong>{{ $transfer->reference_id }}</strong></p>
            <p>التاريخ: <strong>{{ $transfer->created_at->format('Y/m/d') }}</strong></p>
        </td>
    </tr>
    <tr>
        <td style="padding:20px;text-align:center;">
            <a href="{{ url('/transactions/' . $transfer->id) }}" 
               style="background:#1B5E20;color:#FFF;padding:12px 24px;border-radius:8px;text-decoration:none;">
                عرض التفاصيل
            </a>
        </td>
    </tr>
</table>
@endsection
```

## Receipt Attachments

### PDF Generation
```php
use Barryvdh\DomPDF\Facade\Pdf;

function generatePdfReceipt(Transfer $transfer, User $user): string {
    $pdf = Pdf::loadView('pdfs.transfer-receipt', [
        'transfer' => $transfer,
        'user' => $user,
        'locale' => $user->preferred_locale ?? 'ar',
    ]);

    $pdf->setPaper('a4');
    $pdf->setOptions([
        'isRtl' => $user->preferred_locale === 'ar',
        'defaultFont' => 'Tajawal',
    ]);

    return $pdf->output();
}
```

### PDF Content
- Header: Beza Pay logo + transaction reference
- Sender info: name, phone, wallet ID
- Recipient info: name, phone, wallet ID
- Amount (in SYP and USD equivalent)
- Fee breakdown
- Date & time (SY timezone)
- QR code with transaction hash for verification
- Footer: "This is a computer-generated receipt" / "هذا إيصال صادر آلياً"

## Monthly Statements

### Statement Generation
```php
// Scheduled: first day of each month at 06:00 SYT
$schedule->job(new GenerateMonthlyStatements())->monthlyOn(1, '06:00');
```

### Statement Content
```
Beza Pay - Monthly Statement
Month: May 2025
Account: +963 944 XXX XXX

Opening Balance: 100,000 SYP
Total Credits: 250,000 SYP (12 transactions)
Total Debits: 180,000 SYP (8 transactions)
Closing Balance: 170,000 SYP

Transaction History:
Date       | Type     | Amount   | Counterparty        | Reference
01/05/2025 | Transfer | +50,000  | Ahmed Hassan        | REF-12345
...
```

### Storage
- Statements stored in S3: `statements/{tenant_id}/{user_id}/{YYYY-MM}.pdf`
- Pre-signed URL expiry: 7 days
- Statements retained: 10 years (compliance requirement)

## Marketing Emails (Opt-In)

### Consent Tracking
```sql
CREATE TABLE email_preferences (
    user_id     UUID PRIMARY KEY REFERENCES users(id),
    marketing   BOOLEAN NOT NULL DEFAULT FALSE,
    updates     BOOLEAN NOT NULL DEFAULT TRUE,   -- Can't opt out of transactional
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### Opt-in Requirements
- Explicit consent required for marketing emails
- Checkbox on registration: "I agree to receive promotional emails"
- Unsubscribe link in EVERY marketing email (one-click unsubscribe)
- Unsubscribe processed within 24 hours (SES handles automatically)
- Monthly suppression list upload to SES

### Marketing Rate Limit
- Max 4 marketing emails per user per month
- Campaigns approved by compliance before sending
- Tracking: open rate, click rate, unsubscribe rate reported monthly to compliance

## Email Logging

```sql
CREATE TABLE email_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL,
    user_id         UUID,
    to_email        TEXT NOT NULL,            -- encrypted
    template_key    TEXT NOT NULL,
    subject         TEXT NOT NULL,
    status          TEXT NOT NULL,            -- 'sent' | 'delivered' | 'bounced' | 'complaint' | 'opened' | 'clicked'
    message_id      TEXT,                     -- SES message ID
    bounce_type     TEXT,                     -- 'permanent' | 'transient'
    complaint_type  TEXT,
    opened_at       TIMESTAMPTZ,
    clicked_at      TIMESTAMPTZ,
    correlation_id  UUID NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_email_logs_status ON email_logs(status, created_at);
CREATE INDEX idx_email_logs_user ON email_logs(user_id, created_at);
```

### SES Notifications (SNS)
- SES delivers bounce/complaint/delivery notifications via SNS
- SNS triggers SQS queue → processed by `HandleEmailNotifications` job
- Permanent bounces: mark email as invalid, alert user to update
- Complaints: immediately suppress, flag for compliance review
