<?php

declare(strict_types=1);

namespace Modules\Settlement\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\Paginator;
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

    public function findByStatus(string $status, int $perPage = 0): Collection|Paginator
    {
        $query = Settlement::where('status', $status)->orderBy('created_at', 'desc');
        if ($perPage > 0) {
            return $query->paginate($perPage);
        }
        return $query->get();
    }

    public function findByType(string $type, int $perPage = 0): Collection|Paginator
    {
        $query = Settlement::where('settlement_type', $type)->orderBy('created_at', 'desc');
        if ($perPage > 0) {
            return $query->paginate($perPage);
        }
        return $query->get();
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
