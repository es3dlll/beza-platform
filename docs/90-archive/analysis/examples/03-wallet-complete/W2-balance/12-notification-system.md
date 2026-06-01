# 12 - نظام الإشعارات (Notification System) — الرصيد

## نظرة عامة

عرض الرصيد نفسه لا يحتاج إشعاراً (لأنها عملية READ). لكن التغييرات التي تطرأ على الرصيد تتطلب إشعارات فورية للمستخدم. هذا الملف يغطي جميع الإشعارات المتعلقة بالرصيد والتي تُرسل من خدمات أخرى (W1: تحويل، W3: صرافة) أو مجدولات مستقلة.

## أنواع الإشعارات

| النوع | المشغل | القناة | الأولوية |
|-------|--------|--------|----------|
| تغيير الرصيد (إيداع/خصم) | حدث BalanceUpdated | FCM + DB | عادية |
| ملخص الرصيد اليومي | Cron job (مجدول) | FCM + Email | منخفضة |
| رصيد منخفض (تحت الحد) | CheckBalance Job | FCM + DB | عالية |
| معاملة كبيرة (أكثر من حد) | حدث LargeTransaction | FCM + SMS | عالية |
| نشاط غير عادي (احتيال) | Fraud Detection Service | FCM + Email | قصوى |

## 1. إشعار تغيير الرصيد (Balance Change Notification)

يُرسَل عند كل عملية تغيّر الرصيد (تحويل، إيداع، سحب، صرافة):

### PHP — BalanceNotification

```php
<?php
// app/Notifications/BalanceChanged.php

namespace App\Notifications;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Kreait\Firebase\Messaging\CloudMessage;

class BalanceChanged extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Wallet $wallet,
        private readonly string $type,      // 'credit' | 'debit'
        private readonly float  $amount,
        private readonly string $currency,
        private readonly string $description,
        private readonly ?float $oldBalance = null,
    ) {}

    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toFcm(User $notifiable): CloudMessage
    {
        $prefix  = $this->type === 'credit' ? '➕' : '➖';
        $title   = $this->type === 'credit' ? 'إيداع في المحفظة' : 'خصم من المحفظة';

        return CloudMessage::withTarget('token', $notifiable->fcm_token)
            ->withNotification([
                'title' => "$prefix {$title}",
                'body'  => "{$this->description}: {$this->amount} {$this->currency}",
            ])
            ->withData([
                'type'     => 'balance_change',
                'change'   => $this->type,
                'amount'   => (string) $this->amount,
                'currency' => $this->currency,
                'balance'  => (string) $this->wallet->available_balance,
            ]);
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type'          => 'balance_change',
            'change_type'   => $this->type,
            'amount'        => $this->amount,
            'currency'      => $this->currency,
            'description'   => $this->description,
            'new_balance'   => $this->wallet->available_balance,
            'old_balance'   => $this->oldBalance,
            'wallet_id'     => $this->wallet->id,
        ];
    }
}
```

### Flutter — عرض الإشعار في التطبيق

```dart
// lib/features/notifications/widgets/balance_notification_card.dart

class BalanceNotificationCard extends StatelessWidget {
  final Map<String, dynamic> notification;

  const BalanceNotificationCard({required this.notification});

  @override
  Widget build(BuildContext context) {
    final isCredit = notification['change_type'] == 'credit';
    final icon = isCredit ? Icons.arrow_downward : Icons.arrow_upward;
    final color = isCredit ? Colors.green : Colors.red;
    final prefix = isCredit ? '+' : '-';
    final amount = notification['amount'];
    final currency = notification['currency'] ?? 'SYP';

    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.1),
          child: Icon(icon, color: color),
        ),
        title: Text(
          '${notification['description']}',
          style: TextStyle(fontWeight: FontWeight.w500),
        ),
        subtitle: Text(
          '$prefix $amount $currency',
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
        trailing: Text(
          notification['new_balance'].toString(),
          style: TextStyle(color: Colors.grey),
        ),
      ),
    );
  }
}
```

## 2. ملخص الرصيد اليومي (Daily Balance Summary)

يُرسَل مرة يومياً لعرض حركة الرصيد:

### PHP — Scheduled Job

```php
<?php
// app/Console/Commands/SendDailyBalanceSummary.php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DailyBalanceSummary;
use Illuminate\Console\Command;

class SendDailyBalanceSummary extends Command
{
    protected $signature = 'balance:send-daily-summary
                           {--date= : تاريخ محدد (Y-m-d)}';
    protected $description = 'إرسال ملخص الرصيد اليومي للمستخدمين';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();

        User::whereHas('wallet')
            ->whereHas('notificationPreferences', fn($q) =>
                $q->where('daily_balance_summary', true)
            )
            ->chunk(200, function ($users) use ($date) {
                foreach ($users as $user) {
                    $user->notify(new DailyBalanceSummary($user, $date));
                }
                $this->info("تم إرسال الملخص لـ {$users->count()} مستخدم");
            });

        return Command::SUCCESS;
    }
}
```

### تسجيل الجدولة

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('balance:send-daily-summary')
             ->dailyAt('20:00')
             ->withoutOverlapping()
             ->runInBackground();
}
```

### Notification

```php
<?php
// app/Notifications/DailyBalanceSummary.php

namespace App\Notifications;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DailyBalanceSummary extends Notification
{
    public function __construct(
        private readonly User   $user,
        private readonly string $date,
    ) {}

    public function via(User $notifiable): array
    {
        return $notifiable->email ? ['mail', 'database'] : ['database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $wallet    = $notifiable->wallet;
        $transactions = WalletTransaction::where('user_id', $notifiable->id)
            ->whereDate('created_at', $this->date)
            ->get();

        $income  = $transactions->where('type', 'credit')->sum('amount');
        $expense = $transactions->where('type', 'debit')->sum('amount');

        return (new MailMessage)
            ->subject("📊 ملخص الرصيد اليومي — {$this->date}")
            ->greeting("مرحباً {$notifiable->name}")
            ->line("الرصيد الحالي: {$wallet->available_balance} {$wallet->currency}")
            ->line("إجمالي الوارد: {$income} {$wallet->currency}")
            ->line("إجمالي المنصرف: {$expense} {$wallet->currency}")
            ->line("عدد المعاملات: {$transactions->count()}")
            ->action('عرض التفاصيل', url('/wallet/transactions'));
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type'  => 'daily_summary',
            'date'  => $this->date,
            'title' => 'ملخص الرصيد اليومي',
        ];
    }
}
```

## 3. تنبيه انخفاض الرصيد (Low Balance Alert)

يُرسَل عندما يقل الرصيد عن حد معين يحدده المستخدم:

### Job متخصص

```php
<?php
// app/Jobs/CheckLowBalance.php

namespace App\Jobs;

use App\Models\User;
use App\Models\Wallet;
use App\Notifications\LowBalanceAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckLowBalance implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $thresholds = User::whereHas('wallet')
            ->whereHas('notificationPreferences', fn($q) =>
                $q->whereNotNull('low_balance_threshold')
            )
            ->pluck('id', 'low_balance_threshold'); // map: id => threshold

        foreach ($thresholds as $userId => $threshold) {
            $wallet = Wallet::where('user_id', $userId)->first();
            if ($wallet && $wallet->available_balance < $threshold) {
                // تجنب الإشعارات المتكررة
                $lastAlert = cache()->get("low_balance_alert:{$userId}");
                if ($lastAlert && $lastAlert->diffInHours(now()) < 24) {
                    continue; // تم الإشعار خلال آخر 24 ساعة
                }

                $user = User::find($userId);
                $user?->notify(new LowBalanceAlert($wallet, $threshold));

                cache()->put(
                    "low_balance_alert:{$userId}",
                    now(),
                    now()->addHours(24)
                );
            }
        }
    }
}
```

### تحديد الحد الأدنى من قبل المستخدم

```php
<?php
// app/Http/Controllers/Api/WalletNotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletNotificationController extends Controller
{
    public function updateLowBalanceThreshold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threshold' => 'nullable|numeric|min:0|max:10000000',
        ]);

        $prefs = $request->user()->notificationPreferences;
        $prefs->update([
            'low_balance_threshold' => $validated['threshold'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['threshold']
                ? "تم تعيين حد التنبيه عند {$validated['threshold']}"
                : 'تم إلغاء تنبيه انخفاض الرصيد',
        ]);
    }
}
```

### واجهة Dart لضبط الحد

```dart
// lib/features/wallet/screens/low_balance_threshold_screen.dart

class LowBalanceThresholdScreen extends StatefulWidget {
  @override
  State<LowBalanceThresholdScreen> createState() =>
      _LowBalanceThresholdScreenState();
}

class _LowBalanceThresholdScreenState
    extends State<LowBalanceThresholdScreen> {
  final _controller = TextEditingController();
  bool _saving = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تنبيه انخفاض الرصيد')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('أرسل تنبيهاً عندما يقل الرصيد عن:'),
            SizedBox(height: 16),
            TextField(
              controller: _controller,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'الحد الأدنى (SYP)',
                suffixText: 'SYP',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'اتركه فارغاً لتعطيل التنبيه',
              style: TextStyle(color: Colors.grey),
            ),
            SizedBox(height: 24),
            ElevatedButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? CircularProgressIndicator()
                  : Text('حفظ'),
            ),
          ],
        ),
      ),
    );
  }
}
```

## 4. تنبيه المعاملة الكبيرة (Large Transaction Alert)

عندما تتجاوز قيمة المعاملة حداً معيناً:

```php
// في WalletEventService (عند تسجيل أي معاملة)
private function checkLargeTransaction(Wallet $wallet, float $amount): void
{
    $threshold = config('wallet.large_transaction_threshold', 500000); // SYP

    if ($amount >= $threshold) {
        $user = $wallet->user;

        // إشعار فوري
        $user->notify(new LargeTransactionAlert(
            wallet: $wallet,
            amount: $amount,
            currency: $wallet->currency,
            transactionType: 'withdrawal',
        ));

        // تسجيل للرقابة المالية
        LargeTransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount'  => $amount,
            'currency' => $wallet->currency,
            'ip'      => request()->ip(),
        ]);

        Log::info('معاملة كبيرة', [
            'user_id' => $user->id,
            'amount'  => $amount,
        ]);
    }
}
```

```php
<?php
// app/Notifications/LargeTransactionAlert.php

namespace App\Notifications;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LargeTransactionAlert extends Notification
{
    public function __construct(
        private readonly Wallet $wallet,
        private readonly float  $amount,
        private readonly string $currency,
        private readonly string $transactionType,
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'fcm', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('💰 معاملة كبيرة في محفظتك')
            ->line("تم تنفيذ معاملة بقيمة {$this->amount} {$this->currency}")
            ->line("نوع المعاملة: {$this->transactionType}")
            ->line("الرصيد المتبقي: {$this->wallet->available_balance} {$this->currency}")
            ->line('إذا لم تكن أنت من نفذ هذه المعاملة، يرجى التواصل مع الدعم فوراً.')
            ->action('مراجعة المعاملات', url('/wallet/transactions'));
    }
}
```

## 5. تنبيه الاحتيال (Fraud Alert)

اكتشاف النشاط غير العادي على الرصيد:

```php
<?php
// app/Services/FraudDetectionService.php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\FraudAlert;
use Illuminate\Support\Facades\Cache;

class FraudDetectionService
{
    private const MAX_TRANSACTIONS_PER_HOUR = 10;
    private const MAX_AMOUNT_PER_HOUR       = 2_000_000; // SYP
    private const SUSPICIOUS_COUNTRIES       = ['RU', 'NG', 'CN'];

    public function analyze(Wallet $wallet, WalletTransaction $transaction): void
    {
        $flags = [];

        // 1. كثافة المعاملات
        $recentCount = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= self::MAX_TRANSACTIONS_PER_HOUR) {
            $flags[] = 'كثافة معاملات عالية';
        }

        // 2. حجم المعاملات في الساعة
        $totalAmount = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', now()->subHour())
            ->sum('amount');

        if ($totalAmount >= self::MAX_AMOUNT_PER_HOUR) {
            $flags[] = 'حجم معاملات كبير خلال ساعة';
        }

        // 3. IP غير معتاد
        $ip = request()->ip();
        $country = geoip($ip)->iso_code ?? 'XX';
        if (in_array($country, self::SUSPICIOUS_COUNTRIES)) {
            $flags[] = "محاولة من دولة غير معتادة ({$country})";
        }

        // 4. سرعة غير طبيعية
        $lastTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->skip(1) // تجاهل المعاملة الحالية
            ->first();

        if ($lastTx && $lastTx->created_at->diffInSeconds($transaction->created_at) < 5) {
            $flags[] = 'معاملة سريعة جداً (أقل من 5 ثوان)';
        }

        if (! empty($flags)) {
            $wallet->user->notify(new FraudAlert(
                wallet: $wallet,
                flags: $flags,
                transaction: $transaction,
            ));

            Log::critical('احتمال احتيال', [
                'user_id'      => $wallet->user_id,
                'flags'         => $flags,
                'transaction_id' => $transaction->id,
                'ip'            => $ip,
            ]);
        }
    }
}
```

## 6. الحالات الطرفية (Edge Cases)

| المشكلة | المعالجة |
|---------|----------|
| إشعارات متكررة لانخفاض الرصيد | Cache يمنع الإشعار لمدة 24 ساعة |
| مستخدم مع 1000 معاملة في اليوم | دمج الإشعارات — إشعار واحد كل 5 دقائق |
| FCM token منتهي | حذف التوكن — إرسال بريد إلكتروني بديل |
| رصيد سلبي (سماحية السحب) | تنبيه فوري — إيقاف العمليات |
| معاملة أثناء إيقاف الإشعارات | تسجيل في DB فقط — بدون FCM |
| عدة عملات في المحفظة | إشعار منفصل لكل عملة |
| تغيير حد التنبيه بعد تجاوزه | التحقق عند كل معاملة جديدة وليس بأثر رجعي |
| مستخدم حذف التطبيق | إرسال بريد إلكتروني بدلاً من FCM |

## 7. ملخص الإشعارات وقنواتها

| الإشعار | FCM | Email | DB | SMS | قابل للتعطيل |
|---------|-----|-------|----|-----|--------------|
| تغيير الرصيد | ✅ | ❌ | ✅ | ❌ | نعم |
| ملخص يومي | ✅ | ✅ | ✅ | ❌ | نعم (اختياري) |
| رصيد منخفض | ✅ | ❌ | ✅ | ❌ | نعم (مع تحديد الحد) |
| معاملة كبيرة | ✅ | ✅ | ✅ | ✅ (اختياري) | لا |
| تنبيه احتيال | ✅ | ✅ | ✅ | ✅ | لا (إجباري) |
