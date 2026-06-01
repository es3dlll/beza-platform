# 04 - جدول audit_logs (Database Schema)

## هيكل الجدول

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type');                    // login, transfer, etc.
    $table->morphs('loggable');                      // loggable_type + loggable_id
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->json('data');                            // تفاصيل الحدث
    $table->ipAddress('ip')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();

    $table->index(['event_type', 'created_at']);     // للبحث السريع
    $table->index('user_id');                        // أحداث مستخدم معين
    $table->index(['loggable_type', 'loggable_id']); // أحداث مورد معين
});
```

## فئات الأحداث

```php
// App\Enums\AuditEventType.php
enum AuditEventType: string
{
    // Authentication
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case LOGIN_FAILED = 'login_failed';

    // Security
    case TWO_FACTOR_ENABLED = '2fa_enabled';
    case TWO_FACTOR_DISABLED = '2fa_disabled';
    case PIN_CHANGED = 'pin_changed';
    case PASSWORD_CHANGED = 'password_changed';
    case PIN_FAILED = 'pin_failed';

    // Transactions
    case TRANSFER_CREATED = 'transfer_created';
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
    case EXCHANGE = 'exchange';

    // Wallet
    case WALLET_UPDATED = 'wallet_updated';
    case WALLET_FROZEN = 'wallet_frozen';
    case WALLET_UNFROZEN = 'wallet_unfrozen';

    // KYC
    case KYC_SUBMITTED = 'kyc_submitted';
    case KYC_VERIFIED = 'kyc_verified';
    case KYC_REJECTED = 'kyc_rejected';

    // Admin
    case ADMIN_ACTION = 'admin_action';
    case USER_BLOCKED = 'user_blocked';
    case USER_SUSPENDED = 'user_suspended';
}
```
