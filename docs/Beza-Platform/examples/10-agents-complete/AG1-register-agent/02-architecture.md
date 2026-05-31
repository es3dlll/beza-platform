# 02 - العمارة الفنية (Architecture) - تسجيل الوكيل

## نظرة عامة على تدفق تسجيل الوكيل

```
[Flutter App] → [API Gateway] → [RegisterAgentController] → [RegisterAgentService]
    ↓                                                           ↓
[Admin Approval] ← [AdminDashboard] ← [PendingApprovalJob] ← [AgentRequest Model]
    ↓
[Wallet Creation] ← [WalletService::createWallet()]
    ↓
[Agent Dashboard Access]
```

## هيكل Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAgentRegistrationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول أولاً',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if ($user->hasPendingAgentRequest()) {
            return response()->json([
                'message' => 'لديك طلب تسجيل وكيل معلق بالفعل',
                'code' => 'PENDING_REQUEST_EXISTS',
            ], 409);
        }

        if ($user->isAgent()) {
            return response()->json([
                'message' => 'أنت وكيل مسجل بالفعل',
                'code' => 'ALREADY_AGENT',
            ], 409);
        }

        return $next($request);
    }
}

class AdminApprovalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'غير مصرح بالوصول. يجب أن تكون مسؤولاً',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        Log::info('Admin approval action', [
            'admin_id' => $user->id,
            'action' => $request->route()->getName(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
```

## تدفق البيانات الكامل

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRequest;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Events\AgentRegistered;
use App\Events\AgentRequestApproved;
use App\Exceptions\AgentRegistrationFailedException;

class AgentRegistrationOrchestrator
{
    public function __construct(
        private RegisterAgentService $registerService,
        private WalletService $walletService,
        private AdminApprovalService $approvalService,
    ) {}

    /**
     * تدفق التسجيل الكامل من Flutter إلى إنشاء المحفظة
     */
    public function orchestrateRegistration(array $data, User $user): Agent
    {
        return DB::transaction(function () use ($data, $user) {
            // 1. إنشاء طلب الوكيل
            $agentRequest = $this->registerService->createRequest($data, $user);
            Event::dispatch(new AgentRequestSubmitted($agentRequest));

            // 2. الموافقة من الأدمن (قد تكون غير متزامنة)
            $approved = $this->approvalService->processApproval($agentRequest);
            if (!$approved) {
                throw new AgentRegistrationFailedException(
                    'لم تتم الموافقة على طلب تسجيل الوكيل',
                    $agentRequest->id
                );
            }
            Event::dispatch(new AgentRequestApproved($agentRequest));

            // 3. إنشاء سجل الوكيل
            $agent = $this->registerService->createAgentFromRequest($agentRequest);
            $user->assignRole('agent');

            // 4. إنشاء المحفظة
            $wallet = $this->walletService->createWalletForAgent($agent);
            Event::dispatch(new AgentRegistered($agent, $wallet));

            return $agent;
        });
    }
}
```

## محولات API (API Transformers)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentRegistrationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'location' => [
                'lat' => $this->location?->getLat(),
                'lng' => $this->location?->getLng(),
            ],
            'commission_rate' => $this->commission_rate,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'created_at' => $this->created_at->toIso8601String(),
            'message' => 'تم تقديم طلب تسجيل الوكيل بنجاح. في انتظار موافقة الإدارة.',
        ];
    }
}
```

## هيكل المجلدات

```
app/
├── Exceptions/
│   ├── AgentRegistrationFailedException.php
│   ├── AgentAlreadyExistsException.php
│   ├── LocationOutOfBoundsException.php
│   └── CommissionRateInvalidException.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── AgentRegistrationController.php
│   ├── Middleware/
│   │   ├── CheckAgentRegistrationAccess.php
│   │   └── AdminApprovalMiddleware.php
│   ├── Requests/
│   │   └── RegisterAgentRequest.php
│   └── Resources/
│       ├── AgentRegistrationResource.php
│       └── WalletResource.php
├── Models/
│   ├── Agent.php
│   ├── AgentRequest.php
│   └── Wallet.php
└── Services/
    ├── AgentRegistrationOrchestrator.php
    ├── RegisterAgentService.php
    ├── WalletService.php
    └── AdminApprovalService.php
```

## ملاحظات العمارة

1. **التماسك العالي (High Cohesion):** كل خدمة مسؤولة عن مهمة واحدة محددة.
2. **الاقتران المنخفض (Low Coupling):** الخدمات تتواصل عبر الواجهات والأحداث.
3. **المعاملات (Transactions):** كل العمليات الحساسة تتم داخل `DB::transaction`.
4. **الأحداث (Events):** يتم إطلاق الأحداث بعد كل خطوة لإتاحة المعالجة غير المتزامنة.
5. **الطبقات (Layers):** Controller ← Service ← Model مع فصل كامل للمسؤوليات.
