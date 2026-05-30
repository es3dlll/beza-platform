<?php

declare(strict_types=1);

namespace Modules\Settlement\Repositories;

use Illuminate\Support\Collection;
use Modules\Settlement\Models\Settlement;

final class SettlementRepository
{
    public function findById(string $id): ?Settlement
    {
        return Settlement::with('lines')->find($id);
    }

    public function findByReference(string $type, string $id): ?Settlement
    {
        return Settlement::where('reference_type', $type)
            ->where('reference_id', $id)
            ->first();
    }

    public function findByStatus(string $status): Collection
    {
        return Settlement::where('status', $status)->get();
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): Collection
    {
        return Settlement::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();
    }

    public function save(Settlement $settlement): Settlement
    {
        $settlement->save();
        return $settlement;
    }
}
