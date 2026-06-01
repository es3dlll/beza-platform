# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AgentDashboardController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AgentDashboardService;
use Illuminate\Http\JsonResponse;

class AgentDashboardController extends Controller
{
    public function __construct(
        private readonly AgentDashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        $agent = auth()->user()->agent;
        $stats = $this->dashboardService->getStats($agent);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function toggleAvailability(): JsonResponse
    {
        $agent = auth()->user()->agent;
        $agent->update(['available' => !$agent->available]);

        return response()->json([
            'success' => true,
            'message' => $agent->available ? 'أنت متاح الآن' : 'أنت غير متاح الآن',
            'available' => $agent->available,
        ]);
    }
}
```

## Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentDashboardRequest extends FormRequest
{
    public function authorize(): true
    {
        return $this->user() && $this->user()->is_agent;
    }

    public function rules(): array
    {
        return [];
    }
}
```
