# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AgentSettlementController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettlementRequest;
use App\Http\Resources\SettlementResource;
use App\Services\AgentSettlementService;
use Illuminate\Http\JsonResponse;

class AgentSettlementController extends Controller
{
    public function __construct(
        private readonly AgentSettlementService $settlementService
    ) {}

    public function settle(SettlementRequest $request): JsonResponse
    {
        $agent = auth()->user()->agent;
        $result = $this->settlementService->requestSettlement($agent, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب التسوية بنجاح. في انتظار موافقة الإدارة.',
            'data' => $result,
        ], 201);
    }

    public function history(): JsonResponse
    {
        $agent = auth()->user()->agent;
        $settlements = $agent->settlements()->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => SettlementResource::collection($settlements),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $agent = auth()->user()->agent;
        $settlement = $agent->settlements()->findOrFail($id);

        if ($settlement->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء طلب تمت الموافقة عليه مسبقاً',
            ], 400);
        }

        $settlement->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب التسوية',
        ]);
    }
}
```

## Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettlementRequest extends FormRequest
{
    public function authorize(): true
    {
        return $this->user() && $this->user()->is_agent;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1000|max:10000000',
            'bank_account_id' => 'required|exists:agent_bank_accounts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'الحد الأدنى للتسوية 1,000 SYP',
            'amount.max' => 'الحد الأقصى للتسوية 10,000,000 SYP',
            'bank_account_id.exists' => 'الحساب البنكي المحدد غير موجود',
        ];
    }
}
```

## Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'net_amount' => $this->net_amount,
            'bank_account' => $this->bankAccount->account_number,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'processed_at' => $this->processed_at,
        ];
    }
}
```
