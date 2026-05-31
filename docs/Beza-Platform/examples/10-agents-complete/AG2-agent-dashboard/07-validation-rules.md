# 07 - قواعد التحقق (Validation Rules) - لوحة تحكم الوكيل

## التحقق من معاملات GET (خفيف الوزن)

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => [
                'sometimes',
                'date',
                'before_or_equal:today',
                'after_or_equal:2024-01-01',
            ],
            'date_to' => [
                'sometimes',
                'date',
                'before_or_equal:today',
                'after_or_equal:date_from',
            ],
            'period' => [
                'sometimes',
                'string',
                'in:today,week,month,year,custom',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'تاريخ البداية غير صحيح.',
            'date_from.before_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو قبل ذلك.',
            'date_from.after_or_equal' => 'تاريخ البداية يجب أن يكون بعد 2024-01-01.',

            'date_to.date' => 'تاريخ النهاية غير صحيح.',
            'date_to.before_or_equal' => 'تاريخ النهاية يجب أن يكون اليوم أو قبل ذلك.',
            'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',

            'period.in' => 'الفترة يجب أن تكون: today, week, month, year, custom.',
        ];
    }
}

class TransactionListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => [
                'sometimes',
                'integer',
                'min:5',
                'max:100',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'cursor' => [
                'sometimes',
                'string',
                'max:500',
            ],
            'type' => [
                'sometimes',
                'string',
                'in:cash_in,cash_out,all',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:pending,completed,failed',
            ],
            'sort_by' => [
                'sometimes',
                'string',
                'in:created_at,amount',
            ],
            'sort_order' => [
                'sometimes',
                'string',
                'in:asc,desc',
            ],
            'date_from' => [
                'sometimes',
                'date',
                'before_or_equal:today',
            ],
            'date_to' => [
                'sometimes',
                'date',
                'before_or_equal:today',
                'after_or_equal:date_from',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقماً صحيحاً.',
            'per_page.min' => 'الحد الأدنى لعدد العناصر في الصفحة هو 5.',
            'per_page.max' => 'الحد الأقصى لعدد العناصر في الصفحة هو 100.',

            'page.integer' => 'رقم الصفحة يجب أن يكون رقماً صحيحاً.',
            'page.min' => 'رقم الصفحة يجب أن يكون 1 على الأقل.',

            'type.in' => 'نوع المعاملة يجب أن يكون cash_in أو cash_out أو all.',
            'status.in' => 'حالة المعاملة غير صالحة.',

            'sort_by.in' => 'الترتيب يجب أن يكون حسب created_at أو amount.',
            'sort_order.in' => 'اتجاه الترتيب يجب أن يكون asc أو desc.',

            'date_from.date' => 'تاريخ البداية غير صحيح.',
            'date_to.date' => 'تاريخ النهاية غير صحيح.',
            'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
        ];
    }
}
```

## معالج الطلبات في الكنترولر

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardStatsRequest;
use App\Http\Requests\TransactionListRequest;
use App\Http\Resources\AgentTransactionResource;
use App\Http\Resources\DashboardStatsResource;
use App\Services\DashboardStatisticsService;
use App\Services\AgentWalletService;
use App\Exceptions\InvalidDateRangeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    public function __construct(
        private DashboardStatisticsService $statsService,
        private AgentWalletService $walletService,
    ) {}

    /**
     * عرض إحصائيات لوحة التحكم
     */
    public function stats(DashboardStatsRequest $request): JsonResponse
    {
        $agent = $request->user()->agent;

        $filters = $request->validated();
        $stats = $this->statsService->getDashboardStats($agent, $filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * عرض الرصيد الفوري
     */
    public function balance(Request $request): JsonResponse
    {
        $agent = $request->user()->agent;
        $balance = $this->walletService->checkBalance($agent);

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * عرض قائمة المعاملات مع الترقيم
     */
    public function transactions(TransactionListRequest $request): JsonResponse
    {
        $agent = $request->user()->agent;

        $query = $agent->transactions()
            ->when($request->type && $request->type !== 'all', fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc');

        $transactions = $request->cursor
            ? $query->cursorPaginate($request->per_page ?? 20)
            : $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AgentTransactionResource::collection($transactions),
            'meta' => [
                'has_more' => $transactions->hasMorePages(),
                'next_cursor' => $transactions->nextCursor()?->encode(),
                'per_page' => $transactions->perPage(),
            ],
        ]);
    }
}
```

## ملخص قواعد التحقق

| نقطة النهاية (Endpoint) | الحقل | القاعدة | الرسالة |
|------------------------|-------|---------|---------|
| /api/v1/agent/dashboard/stats | date_from | optional, date, ≤ today | تاريخ غير صحيح |
| /api/v1/agent/dashboard/stats | date_to | optional, date, ≤ today, ≥ date_from | تاريخ غير صحيح |
| /api/v1/agent/dashboard/stats | period | optional, in:today,week,month,year,custom | فترة غير صالحة |
| /api/v1/agent/transactions | per_page | optional, int, 5-100 | عدد العناصر غير صحيح |
| /api/v1/agent/transactions | type | optional, in:cash_in,cash_out,all | نوع غير صالح |
| /api/v1/agent/transactions | status | optional, in:pending,completed,failed | حالة غير صالحة |
| /api/v1/agent/transactions | sort_by | optional, in:created_at,amount | ترتيب غير صالح |
| /api/v1/agent/transactions | sort_order | optional, in:asc,desc | اتجاه غير صالح |

نظراً لأن لوحة التحكم تعتمد بشكل أساسي على طرق GET، فإن التحقق بسيط وخفيف الوزن مع رسائل خطأ واضحة بالعربية.
