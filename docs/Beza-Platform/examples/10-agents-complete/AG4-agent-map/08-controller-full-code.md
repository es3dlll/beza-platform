# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AgentMapController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NearbyAgentsRequest;
use App\Http\Resources\AgentMapResource;
use App\Services\AgentMapService;
use Illuminate\Http\JsonResponse;

class AgentMapController extends Controller
{
    public function __construct(
        private readonly AgentMapService $mapService
    ) {}

    public function nearby(NearbyAgentsRequest $request): JsonResponse
    {
        $agents = $this->mapService->findNearby(
            $request->input('lat', 33.5138),
            $request->input('lng', 36.2765),
            $request->input('radius', 5),
            $request->input('available_only', true),
        );

        return response()->json([
            'success' => true,
            'data' => AgentMapResource::collection($agents),
            'meta' => [
                'count' => $agents->count(),
                'radius_km' => $request->input('radius', 5),
            ],
        ]);
    }

    public function updateLocation(): JsonResponse
    {
        $agent = auth()->user()->agent;
        request()->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $agent->update([
            'location_lat' => request('lat'),
            'location_lng' => request('lng'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الموقع',
        ]);
    }
}
```

## Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyAgentsRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
            'available_only' => 'nullable|boolean',
        ];
    }
}
```

## Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentMapResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'shop_name' => $this->shop_name,
            'address' => $this->address,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'distance_km' => $this->distance_km ?? null,
            'available' => $this->available,
            'rating' => $this->rating ?? 0,
            'phone' => $this->user->phone,
        ];
    }
}
```
