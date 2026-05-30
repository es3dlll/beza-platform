<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Enums\CodStatus;
use Modules\Marketplace\Models\CodCollection;
use Modules\Marketplace\Models\Shipment;

final class CodService
{
    public function collect(string $shipmentId, string $agentId): CodCollection
    {
        $shipment = Shipment::findOrFail($shipmentId);

        $collection = CodCollection::create([
            'shipment_id' => $shipmentId,
            'order_id' => $shipment->order_id,
            'amount' => $shipment->order->total_amount,
            'agent_id' => $agentId,
            'status' => CodStatus::Collected,
            'collected_at' => now(),
        ]);

        $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);

        return $collection->fresh();
    }

    public function remit(string $collectionId): CodCollection
    {
        $collection = CodCollection::findOrFail($collectionId);

        $collection->update([
            'status' => CodStatus::Remitted,
            'remitted_at' => now(),
        ]);

        return $collection->fresh();
    }

    public function listByAgent(string $agentId): iterable
    {
        return CodCollection::where('agent_id', $agentId)
            ->with(['shipment', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listPending(): iterable
    {
        return CodCollection::where('status', CodStatus::Pending)
            ->with(['shipment', 'order'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
