<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\ShippingService;
use App\Support\ApiResponse;

final class ShippingController extends Controller
{
    use ApiResponse;
    public function __construct(
        private ShippingService $shipping,
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'governorate' => 'required|string|max:50',
            'weight_grams' => 'required|integer|min:1',
            'zone_id' => 'nullable|string',
        ]);

        $fee = $this->shipping->calculateFee(
            $data['governorate'],
            (int) $data['weight_grams'],
            $data['zone_id'] ?? null,
        );

        return $this->respond(['fee' => $fee]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|string',
            'shipping_address' => 'required|string',
            'governorate' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'carrier' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $shipment = $this->shipping->createShipment(
            $data['order_id'],
            $data,
        );

        return $this->respondCreated($shipment);
    }

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => 'required|string|max:100',
        ]);

        $shipment = $this->shipping->trackShipment($data['tracking_number']);

        return $this->respond($shipment);
    }

    public function zones(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'name' => 'required|string|max:50',
                'name_ar' => 'required|string|max:50',
                'governorates' => 'required|array|min:1',
                'governorates.*' => 'string|max:50',
                'base_fee' => 'required|integer|min:0',
                'per_kg_fee' => 'sometimes|integer|min:0',
                'estimated_days' => 'sometimes|integer|min:1',
                'is_active' => 'sometimes|boolean',
            ]);

            $zone = $this->shipping->createZone($data);

            return $this->respondCreated($zone);
        }

        $zones = $this->shipping->listZones();

        return $this->respond($zones);
    }

    public function listByOrder(string $orderId): JsonResponse
    {
        $shipments = $this->shipping->listByOrder($orderId);

        return $this->respond($shipments);
    }
}
