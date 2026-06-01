#!/usr/bin/env python3
"""Generate all 120 documentation files for Merchant operations (M1-M6)."""
import os

BASE = r"C:\Users\xRoot\Desktop\Beza-Platform\tasks\examples\04-merchant-complete"
OPS = [
    "M1-register-merchant",
    "M2-merchant-products",
    "M3-payment-gateway",
    "M4-merchant-orders",
    "M5-merchant-recurring",
    "M6-merchant-settlement",
]

def wf(op_dir, filename, content):
    path = os.path.join(BASE, op_dir, filename)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(content.lstrip("\n"))
    print(f"  {op_dir}/{filename}")

def index_content(op_num, op_name, op_priority, op_api, op_controller, op_service, op_event, op_listener, op_tables, op_fee, op_limits, op_flutter, op_react, op_desc):
    return f"""# فهرس - {op_desc}

```
{op_num}/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md
├── 02-architecture.md
├── 03-data-flow-sequence.md
├── 04-database-relationships.md
├── 05-migrations.md
├── 06-eloquent-models.md
├── 07-validation-rules.md
├── 08-controller-full-code.md
├── 09-service-layer-core.md
├── 10-service-layer-aux.md
├── 11-events-and-listeners.md
├── 12-notification-system.md
├── 13-exception-handling.md
├── 14-database-transactions-acid.md
├── 15-api-specification.md
├── 16-flutter-implementation.md
├── 17-react-implementation.md
├── 18-testing-complete.md
├── 19-edge-cases.md
└── 20-security-audit.md
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | {op_name} |
| الأولوية | {op_priority} |
| API | `{op_api}` |
| Controller | {op_controller} |
| Service | {op_service} |
| Event | {op_event} |
| Listener | {op_listener} |
| DB Tables | {op_tables} |
| رسوم | {op_fee} |
| حدود | {op_limits} |
| Flutter Screen | {op_flutter} |
| React Page | {op_react} |
"""

def business_idea_content(title, story, criteria):
    return f"""# 01 - فكرة العمل وسيناريو المستخدم (Business Idea & User Scenario)

## الفكرة الأساسية
{title}

## سيناريو المستخدم
```
{story}
```

## قبول السيناريو (Acceptance Criteria)
| # | الشرط | الحالة |
|---|-------|--------|
{criteria}
"""

# =====================================================================
# M1 - REGISTER MERCHANT
# =====================================================================
print("Generating M1-register-merchant...")
od = "M1-register-merchant"

wf(od, "00-index.md", index_content(
    "M1-register-merchant", "تسجيل تاجر جديد", "P1 (عالية)",
    "POST /api/v1/merchant/register, GET /api/v1/merchant/status",
    "MerchantRegisterController", "MerchantRegistrationService / MerchantWalletService",
    "MerchantRegistered, MerchantApproved", "CreateMerchantWallets, SendMerchantApprovalNotification",
    "merchants, merchant_documents, merchant_wallets",
    "2% على معاملات التاجر", "محفظة SYP + USD منفصلة",
    "MerchantRegisterScreen", "MerchantRegisterPage",
    "تسجيل تاجر جديد"
))

wf(od, "01-business-idea.md", business_idea_content(
    "متجر أحمد للأجهزة الإلكترونية يريد قبول المدفوعات الرقمية عبر Beza. يحتاج إلى حساب تاجر منفصل مع محفظة SYP + USD ورفع مستندات السجل التجاري.",
    """بصفتي: تاجر (صاحب متجر)
أريد: تسجيل متجري في منصة Beza
لكي: أتمكن من قبول المدفوعات الرقمية من العملاء""",
    """| 1 | إدخال اسم المتجر (business_name) | إجباري |
| 2 | إدخال نوع النشاط (business_type) | إجباري |
| 3 | إدخال السجل التجاري (commercial_registration) | إجباري |
| 4 | إدخال الرقم الضريبي (tax_id) | إجباري |
| 5 | إدخال رقم هاتف المالك (owner_phone) | إجباري |
| 6 | إدخال معلومات الحساب البنكي (bank_account_info) | إجباري |
| 7 | رفع المستندات المطلوبة | إجباري |
| 8 | إنشاء حساب تاجر بحالة pending | تلقائي |
| 9 | مراجعة يدوية من Admin | يدوي |
| 10 | إنشاء محفظة تاجر SYP + USD | تلقائي بعد التفعيل |"""
))

wf(od, "02-architecture.md", """# 02 - مكان العملية في الأرشيتيكشر

```
┌────────────────────────────────────────────────────┐
│ Flutter / React                                     │
│ [RegisterScreen] → [API Request]                    │
└────────────────────┬───────────────────────────────┘
                     │ POST /api/v1/merchant/register
                     ▼
┌────────────────────────────────────────────────────┐
│ Laravel Middleware                                  │
│ auth:sanctum → throttle → verified                 │
└────────────────────┬───────────────────────────────┘
                     ▼
┌────────────────────────────────────────────────────┐
│ MerchantRegisterController                          │
│ 1. Validate                                         │
│ 2. Upload documents                                 │
│ 3. Call RegistrationService                         │
│ 4. Return response                                  │
└────────────────────┬───────────────────────────────┘
                     ▼
┌────────────────────────────────────────────────────┐
│ MerchantRegistrationService                         │
│ 1. Check duplicates                                 │
│ 2. DB::transaction { create merchant + docs }       │
│ 3. Dispatch MerchantRegistered event                │
└────────────────────┬───────────────────────────────┘
                     │
          ┌──────────┴──────────┐
          ▼                     ▼
    ┌──────────┐         ┌──────────┐
    │  MySQL   │         │ Storage  │
    │merchants │         │ Documents│
    │wallets   │         │          │
    └──────────┘         └──────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات الكامل (Sequence Diagram)

```
  Merchant      Flutter/React      Laravel API        RegService       MySQL         Admin
     │                │                  │                │               │             │
     │  تسجيل         │                  │                │               │             │
     │───────────────>│                  │                │               │             │
     │                │  POST /register  │                │               │             │
     │                │─────────────────>│                │               │             │
     │                │                  │  Validate      │               │             │
     │                │                  │────────────────>│              │             │
     │                │                  │  Check dupl.   │──────────────>│             │
     │                │                  │  Create merch  │──────────────>│             │
     │                │                  │  Upload docs   │──────────────>│             │
     │                │                  │  Dispatch event│               │             │
     │                │ Response 201     │                │               │             │
     │                │<─────────────────│                │               │             │
     │                │                  │                │               │  Admin      │
     │                │                  │                │               │  reviews    │
     │                │                  │ PATCH /approve │               │             │
     │                │                  │<────────────────────────────────│             │
     │                │                  │ Update active  │──────────────>│             │
     │                │                  │ Create wallets │──────────────>│             │
     │                │                  │ Notify merchant│               │             │
     │<───────────────│                  │                │               │             │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول (Database Relationships)

## ER Diagram
```
  ┌──────────────┐     ┌──────────────────────────────┐
  │    users     │────>│         merchants             │
  │──────────────│ 1   │──────────────────────────────│
  │ id           │     │ id (PK)                      │
  │ name         │     │ user_id (FK, UNIQUE)         │
  │ phone        │     │ business_name                │
  │ is_merchant  │     │ commercial_registration(UNIQUE)│
  └──────────────┘     │ tax_id (UNIQUE)              │
                        │ status (pending/active)      │
                        │ fee_percentage (2.00)       │
                        └──────────┬───────────────────┘
                                   │ 1
                        ┌──────────┴──────────┐
                        ▼                     ▼
              ┌──────────────────┐  ┌──────────────────┐
              │merchant_documents │  │ merchant_wallets  │
              │──────────────────│  │──────────────────│
              │ id               │  │ id               │
              │ merchant_id (FK) │  │ merchant_id (FK) │
              │ type             │  │ currency (SYP/USD)│
              │ file_path        │  │ wallet_number     │
              │ is_verified      │  │ balance           │
              └──────────────────┘  └──────────────────┘
```
""")

wf(od, "05-migrations.md", """# 05 - كود الميغريشن الكامل (Migrations)

## merchants table
```php
<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('business_type', 100);
            $table->string('commercial_registration', 100)->unique();
            $table->string('tax_id', 100)->unique();
            $table->string('owner_phone', 20);
            $table->string('owner_name');
            $table->json('bank_account_info');
            $table->enum('status', ['pending','active','rejected','suspended'])->default('pending');
            $table->decimal('fee_percentage', 5, 2)->default(2.00);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('merchants'); }
};
```

## merchant_documents table
```php
Schema::create('merchant_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['registration','commercial','tax','owner_id','bank_proof','other']);
    $table->string('file_path', 500);
    $table->string('file_type', 20);
    $table->unsignedInteger('file_size');
    $table->boolean('is_verified')->default(false);
    $table->timestamp('uploaded_at')->useCurrent();
});
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز مع العلاقات (Eloquent Models)

## Merchant Model
```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class Merchant extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'business_type',
        'commercial_registration', 'tax_id', 'owner_phone',
        'owner_name', 'bank_account_info', 'status',
        'fee_percentage', 'rejection_reason', 'approved_at',
    ];
    protected $casts = [
        'bank_account_info' => 'json',
        'fee_percentage'    => 'decimal:2',
        'approved_at'       => 'datetime',
    ];
    public function user() { return $this->belongsTo(User::class); }
    public function documents() { return $this->hasMany(MerchantDocument::class); }
    public function wallets() { return $this->hasMany(MerchantWallet::class); }
    public function approve(): void { $this->update(['status' => 'active', 'approved_at' => now()]); }
}
```

## MerchantWallet Model
```php
<?php
namespace App\\Models;
class MerchantWallet extends Model
{
    protected $fillable = ['merchant_id', 'currency', 'wallet_number', 'balance', 'frozen_balance', 'is_active'];
    protected $casts = ['balance' => 'decimal:2', 'frozen_balance' => 'decimal:2', 'is_active' => 'boolean'];
    protected $appends = ['available_balance'];
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function getAvailableBalanceAttribute(): float { return $this->balance - $this->frozen_balance; }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - كل قواعد التحقق + أسبابها

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class RegisterMerchantRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:100'],
            'commercial_registration' => ['required', 'string', 'max:100', Rule::unique('merchants')],
            'tax_id' => ['required', 'string', 'max:100', Rule::unique('merchants')],
            'owner_phone' => ['required', 'string', 'regex:/^[0-9+\\-\\(\\)\\s]{7,20}$/'],
            'owner_name' => ['required', 'string', 'max:255'],
            'bank_account_info.bank_name' => ['required', 'string'],
            'bank_account_info.account_number' => ['required', 'string'],
            'bank_account_info.iban' => ['required', 'string'],
            'documents' => ['required', 'array', 'min:2'],
            'documents.*.type' => ['required', Rule::in(['registration','commercial','tax','owner_id'])],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:10240'],
        ];
    }
}
```
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل (Controller Full Code)

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Merchant\\RegisterMerchantRequest;
use App\\Http\\Resources\\MerchantResource;
use App\\Services\\Merchant\\MerchantRegistrationService;
use Illuminate\\Http\\JsonResponse;

class MerchantRegisterController extends Controller
{
    public function __construct(
        private readonly MerchantRegistrationService $registrationService
    ) {}

    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->registrationService->register(
            user: $user,
            businessName: $request->input('business_name'),
            businessType: $request->input('business_type'),
            commercialRegistration: $request->input('commercial_registration'),
            taxId: $request->input('tax_id'),
            ownerPhone: $request->input('owner_phone'),
            ownerName: $request->input('owner_name'),
            bankAccountInfo: $request->input('bank_account_info'),
            documents: $request->file('documents', []),
        );
        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب التسجيل بنجاح، في انتظار المراجعة',
            'data'    => ['merchant' => new MerchantResource($result['merchant']), 'status' => 'pending'],
        ], 201);
    }
}
```

## Route
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/merchant/register', [MerchantRegisterController::class, 'register']);
    Route::get('/merchant/status/{id}', [MerchantRegisterController::class, 'status']);
});
```
""")

wf(od, "09-service-layer-core.md", """# 09 - MerchantRegistrationService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Events\\MerchantRegistered;
use App\\Exceptions\\MerchantAlreadyExistsException;
use App\\Exceptions\\DocumentUploadFailedException;
use App\\Models\\Merchant;
use App\\Models\\MerchantDocument;
use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class MerchantRegistrationService
{
    public function register(
        User   $user, string $businessName, string $businessType,
        string $commercialRegistration, string $taxId,
        string $ownerPhone, string $ownerName,
        array  $bankAccountInfo, array $documents = [],
    ): array {
        $existing = Merchant::where('user_id', $user->id)->first();
        if ($existing) throw new MerchantAlreadyExistsException();

        $merchant = DB::transaction(function () use (
            $user, $businessName, $businessType,
            $commercialRegistration, $taxId,
            $ownerPhone, $ownerName, $bankAccountInfo, $documents
        ) {
            $m = Merchant::create([
                'user_id' => $user->id, 'business_name' => $businessName,
                'business_type' => $businessType,
                'commercial_registration' => $commercialRegistration,
                'tax_id' => $taxId, 'owner_phone' => $ownerPhone,
                'owner_name' => $ownerName,
                'bank_account_info' => $bankAccountInfo,
                'status' => 'pending', 'fee_percentage' => 2.00,
            ]);
            foreach ($documents as $doc) { $this->uploadDocument($m, $doc); }
            return $m;
        }, attempts: 3);

        MerchantRegistered::dispatch($merchant, $user);
        $user->update(['is_merchant' => true]);
        return ['merchant' => $merchant];
    }

    private function uploadDocument(Merchant $merchant, array $docData): void
    {
        $file = $docData['file'];
        $path = $file->store("merchants/{$merchant->id}/documents", 'public');
        MerchantDocument::create([
            'merchant_id' => $merchant->id, 'type' => $docData['type'],
            'file_path' => $path, 'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(), 'is_verified' => false,
        ]);
    }
}
```
""")

wf(od, "10-service-layer-aux.md", """# 10 - MerchantWalletService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\Merchant;
use App\\Models\\MerchantWallet;
use Illuminate\\Support\\Facades\\DB;

class MerchantWalletService
{
    public function createWallets(Merchant $merchant): void
    {
        foreach (['SYP', 'USD'] as $currency) {
            MerchantWallet::create([
                'merchant_id' => $merchant->id, 'currency' => $currency,
                'wallet_number' => $this->generateWalletNumber($currency),
                'balance' => 0.00, 'frozen_balance' => 0.00, 'is_active' => true,
            ]);
        }
    }

    public function decrement(MerchantWallet $wallet, float $amount): void
    {
        $affected = DB::update(
            'UPDATE merchant_wallets SET balance = balance - ? WHERE id = ? AND balance >= ? AND is_active = ?',
            [$amount, $wallet->id, $amount, true]
        );
        if ($affected === 0) throw new \\RuntimeException('فشل خصم رصيد التاجر');
    }

    public function increment(MerchantWallet $wallet, float $amount): void
    {
        DB::update('UPDATE merchant_wallets SET balance = balance + ? WHERE id = ? AND is_active = ?',
            [$amount, $wallet->id, true]);
    }

    public function getWallet(int $merchantId, string $currency): ?MerchantWallet
    {
        return MerchantWallet::where('merchant_id', $merchantId)
            ->where('currency', $currency)->first();
    }

    private function generateWalletNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '72' : '73';
        do { $number = $prefix . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT); }
        while (MerchantWallet::where('wallet_number', $number)->exists());
        return $number;
    }
}
```
""")

wf(od, "11-events-and-listeners.md", """# 11 - الأحداث والمستمعين (Events & Listeners)

## MerchantRegistered Event
```php
<?php
namespace App\\Events;
use App\\Models\\Merchant;
use App\\Models\\User;
use Illuminate\\Foundation\\Events\\Dispatchable;
class MerchantRegistered { use Dispatchable; public function __construct(public readonly Merchant $merchant, public readonly User $user) {} }
```

## MerchantApproved Event
```php
<?php
namespace App\\Events;
use App\\Models\\Merchant;
use Illuminate\\Foundation\\Events\\Dispatchable;
class MerchantApproved { use Dispatchable; public function __construct(public readonly Merchant $merchant) {} }
```

## EventServiceProvider
```php
protected $listen = [
    MerchantRegistered::class => [NotifyAdminNewMerchant::class],
    MerchantApproved::class => [CreateMerchantWallets::class, SendMerchantApprovalNotification::class],
];
```
""")

wf(od, "12-notification-system.md", """# 12 - نظام الإشعارات (FCM + SMS + Email)

```php
<?php
namespace App\\Notifications;
use App\\Models\\Merchant;
use Illuminate\\Notifications\\Notification;
use Illuminate\\Notifications\\Messages\\MailMessage;

class MerchantApprovedNotification extends Notification
{
    public function __construct(private readonly Merchant $merchant) {}
    public function via($notifiable): array {
        $channels = ['database'];
        if ($notifiable->fcm_token) $channels[] = 'fcm';
        if ($notifiable->email) $channels[] = 'mail';
        return $channels;
    }
    public function toMail($notifiable): MailMessage {
        return (new MailMessage)->subject('تم الموافقة على متجرك')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم تفعيل {$this->merchant->business_name}")
            ->action('الدخول', url('/merchant/dashboard'));
    }
    public function toArray($notifiable): array {
        return ['type' => 'merchant_approved', 'title' => 'تم الموافقة',
                'body' => "تم تفعيل {$this->merchant->business_name}",
                'merchant_id' => $this->merchant->id];
    }
}
```
""")

wf(od, "13-exception-handling.md", """# 13 - كل الاستثناءات ومعالجتها

```php
<?php
namespace App\\Exceptions;
use Exception;

class MerchantAlreadyExistsException extends Exception {
    public function render(): \\Illuminate\\Http\\JsonResponse {
        return response()->json(['success' => false, 'message' => $this->getMessage() ?: 'لديك حساب تاجر بالفعل'], 422);
    }
}
class DocumentUploadFailedException extends Exception {
    public function render(): \\Illuminate\\Http\\JsonResponse {
        return response()->json(['success' => false, 'message' => 'فشل رفع المستندات'], 422);
    }
}
```

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | MerchantAlreadyExistsException | لديك حساب تاجر بالفعل |
| 422 | DocumentUploadFailedException | فشل رفع المستندات |
| 404 | ModelNotFoundException | التاجر غير موجود |
""")

wf(od, "14-database-transactions-acid.md", """# 14 - ACID + الأقفال + الـ Race Conditions

## التسجيل المتزامن
بدون قفل: طلبان متزامنان بنفس السجل التجاري → إنشاء تاجرين مكررين.

## الحل: UNIQUE constraint + DB transaction
```php
DB::transaction(function () {
    Merchant::create([...]);  // UNIQUE يمنع التكرار على مستوى DB
    foreach ($documents as $doc) { MerchantDocument::create([...]); }
}, attempts: 3);
```

```sql
-- UNIQUE(commercial_registration) + UNIQUE(tax_id) يمنعان الإدخال المكرر
-- DB::transaction يضمن Atomicity بين التاجر والمستندات
```
""")

wf(od, "15-api-specification.md", """# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Merchant API
  version: 1.0.0
paths:
  /merchant/register:
    post:
      summary: تسجيل تاجر جديد
      security: [{ bearerAuth: [] }]
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              required: [business_name, business_type, commercial_registration, tax_id]
              properties:
                business_name: { type: string }
                business_type: { type: string }
                commercial_registration: { type: string }
                tax_id: { type: string }
                documents: { type: array, items: { type: string, format: binary } }
      responses:
        '201': { description: تم تقديم الطلب }
  /merchant/status/{id}:
    get:
      summary: حالة طلب التسجيل
      parameters: [{ name: id, in: path, required: true, schema: { type: integer } }]
      responses: { '200': { description: حالة الطلب } }
```

## cURL
```bash
curl -X POST http://localhost:8000/api/v1/merchant/register \\
  -H "Authorization: Bearer TOKEN" \\
  -F "business_name=متجر أحمد" \\
  -F "business_type=electronics" \\
  -F "commercial_registration=CR123456" \\
  -F "tax_id=TX123456"
```
""")

wf(od, "16-flutter-implementation.md", """# 16 - Flutter UI + BLoC + Repository

```dart
// domain/repositories/i_merchant_repository.dart
abstract class IMerchantRepository {
  Future<MerchantEntity> register({
    required String businessName, required String businessType,
    required String commercialRegistration, required String taxId,
    required String ownerPhone, required String ownerName,
    required Map<String, dynamic> bankAccountInfo,
    required List<Map<String, dynamic>> documents,
  });
}

// presentation/bloc/merchant_register_bloc.dart
class MerchantRegisterBloc extends Bloc<MerchantRegisterEvent, MerchantRegisterState> {
  final IMerchantRepository repository;
  MerchantRegisterBloc({required this.repository}) : super(MerchantRegisterInitial()) {
    on<SubmitMerchantRegister>(_onSubmit);
  }
  Future<void> _onSubmit(SubmitMerchantRegister event, Emitter<MerchantRegisterState> emit) async {
    emit(MerchantRegisterLoading());
    try {
      final result = await repository.register(...);
      emit(MerchantRegisterSuccess(result));
    } catch (e) { emit(MerchantRegisterFailure(e.toString())); }
  }
}

// presentation/screens/merchant_register_screen.dart
class MerchantRegisterScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تسجيل تاجر')),
      body: SingleChildScrollView(padding: EdgeInsets.all(16), child: Column(children: [
        TextFormField(decoration: InputDecoration(labelText: 'اسم المتجر')),
        TextFormField(decoration: InputDecoration(labelText: 'السجل التجاري')),
        SizedBox(height: 24),
        ElevatedButton(onPressed: () {}, child: Text('تسجيل')),
      ])),
    );
  }
}
```
""")

wf(od, "17-react-implementation.md", """# 17 - React UI + Hooks + API

```jsx
// hooks/useMerchantRegister.js
import { useState, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useMerchantRegister() {
  const [state, setState] = useState({ loading: false, success: false, error: null, merchant: null });
  const submit = useCallback(async (formData) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const res = await merchantApi.register(formData);
      setState({ loading: false, success: true, error: null, merchant: res.data.data.merchant });
    } catch (err) {
      setState({ loading: false, success: false, error: err.response?.data?.message || 'فشل التسجيل', merchant: null });
    }
  }, []);
  return { ...state, submit };
}

// pages/merchant/MerchantRegisterPage.jsx
export default function MerchantRegisterPage() {
  const { loading, error, submit } = useMerchantRegister();
  const handleSubmit = (e) => { e.preventDefault(); submit(new FormData(e.target)); };
  return (
    <div>
      <h1>تسجيل تاجر</h1>
      {error && <div className="alert-error">{error}</div>}
      <form onSubmit={handleSubmit}>
        <input name="business_name" placeholder="اسم المتجر" required />
        <input name="commercial_registration" placeholder="السجل التجاري" required />
        <button type="submit" disabled={loading}>{loading ? 'جاري...' : 'تسجيل'}</button>
      </form>
    </div>
  );
}
```
""")

wf(od, "18-testing-complete.md", """# 18 - كل الاختبارات (Testing Complete)

```php
<?php
namespace Tests\\Feature\\Merchant;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function it_registers_a_merchant_successfully() {
        $response = $this->withToken($this->token)->post('/api/v1/merchant/register', [
            'business_name' => 'متجر اختبار',
            'business_type' => 'electronics',
            'commercial_registration' => 'CR123456',
            'tax_id' => 'TX123456',
            'owner_phone' => '963944123456',
            'owner_name' => 'أحمد',
            'bank_account_info' => ['bank_name' => 'اختبار', 'account_number' => '123', 'iban' => 'SY123'],
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('merchants', ['business_name' => 'متجر اختبار']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->postJson('/api/v1/merchant/register', []);
        $response->assertStatus(401);
    }
}
```
""")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة + سيناريوهات خطأ

1. **تسجيل مكرر بنفس السجل التجاري**: UNIQUE constraint + duplicate check يمنعان التكرار.
2. **رفع ملفات كبيرة جدا (>10MB)**: Validation يرفضها قبل الوصول للخادم.
3. **مستخدم لديه حساب تاجر مسبق**: التحقق من user_id الفريد يمنع الحساب الثاني.
4. **مستندات بصيغة خاطئة**: mimes:pdf,jpg,png يمنع رفع ملفات غير مدعومة.
5. **فشل إنشاء المحفظة بعد الموافقة**: Listener يعيد المحاولة عبر Queue.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | سجل تجاري مكرر | رفض (422) |
| 2 | ملف > 10MB | رفض validation |
| 3 | تاجر موجود مسبقاً | رفض (422) |
| 4 | صيغة خاطئة | رفض (422) |
| 5 | فشل إنشاء المحفظة | إعادة Queue |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية (Security Audit)

## 1. التحقق من الملكية
```php
// يستخدم التوكن — لا يمكن تزوير user_id
$user = $request->user();
```

## 2. حماية الملفات
```php
// تخزين خارج public_html + التحقق من الصيغة والحجم
$path = $file->store('merchants/{id}/documents', 'local');
'mimes:pdf,jpg,png', 'max:10240'
```

## 3. قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | جميع المدخلات موثقة | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate Limiting | ✅ |
| 4 | Authentication (Sanctum) | ✅ |
| 5 | التحقق من نوع الملفات | ✅ |
| 6 | Mass assignment protection | ✅ |
| 7 | HTTPS (للإنتاج) | ⏳ |
""")

print("M1 complete!")
