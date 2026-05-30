<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\Shipment;
use Modules\Marketplace\Models\ShippingZone;

final class ShippingService
{
    public function calculateFee(string $governorate, int $weightGrams, ?string $zoneId = null): int
    {
        $query = ShippingZone::where('is_active', true);

        if ($zoneId !== null) {
            $query->where('id', $zoneId);
        }

        $zone = $query->get()->first(fn (ShippingZone $z) => in_array($governorate, $z->governorates ?? []));

        if ($zone === null) {
            $zone = ShippingZone::where('is_active', true)->orderBy('base_fee', 'desc')->first();
        }

        if ($zone === null) {
            return 0;
        }

        $kg = (int) ceil($weightGrams / 1000);

        return $zone->base_fee + ($zone->per_kg_fee * $kg);
    }

    public function createShipment(string $orderId, array $address): Shipment
    {
        return Shipment::create([
            'order_id' => $orderId,
            'shipping_address' => $address['shipping_address'],
            'governorate' => $address['governorate'],
            'recipient_name' => $address['recipient_name'],
            'recipient_phone' => $address['recipient_phone'],
            'notes' => $address['notes'] ?? null,
            'carrier' => $address['carrier'] ?? null,
            'tracking_number' => $address['tracking_number'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function trackShipment(string $trackingNumber): Shipment
    {
        return Shipment::where('tracking_number', $trackingNumber)
            ->with(['order', 'codCollections'])
            ->firstOrFail();
    }

    public function listByOrder(string $orderId): iterable
    {
        return Shipment::where('order_id', $orderId)
            ->with('codCollections')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listByUser(string $userId): iterable
    {
        return Shipment::whereHas('order', fn ($q) => $q->where('user_id', $userId))
            ->with(['order', 'codCollections'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listZones(): iterable
    {
        return ShippingZone::where('is_active', true)->orderBy('base_fee')->get();
    }

    public function createZone(array $data): ShippingZone
    {
        return ShippingZone::create($data);
    }
}
