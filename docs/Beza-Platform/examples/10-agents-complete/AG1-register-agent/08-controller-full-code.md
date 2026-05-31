# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AgentRegistrationController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentRegistrationRequest;
use App\Http\Resources\AgentResource;
use App\Services\AgentRegistrationService;
use Illuminate\Http\JsonResponse;

class AgentRegistrationController extends Controller
{
    public function __construct(
        private readonly AgentRegistrationService $registrationService
    ) {}

    public function register(AgentRegistrationRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->registrationService->register($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب التسجيل بنجاح. في انتظار مراجعة الإدارة.',
            'data' => $result,
        ], 201);
    }
}
```

## Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentRegistrationRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:agent_requests,phone|regex:/^09[0-9]{8}$/',
            'id_number' => 'required|string|unique:agent_requests,id_number|max:20',
            'id_photo' => 'required|image|mimes:jpg,jpeg,png|max:10240',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً',
            'id_number.unique' => 'رقم الهوية مستخدم مسبقاً',
            'id_photo.max' => 'حجم الصورة يجب أن لا يتجاوز 10MB',
        ];
    }
}
```

## Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
```
