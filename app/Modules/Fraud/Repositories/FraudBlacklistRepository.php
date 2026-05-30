<?php

declare(strict_types=1);

namespace Modules\Fraud\Repositories;

use Modules\Fraud\Models\FraudBlacklistEntry;

class FraudBlacklistRepository
{
    public function isBlocked(string $type, string $value): bool
    {
        return FraudBlacklistEntry::where('type', $type)
            ->where('value', $value)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function add(array $data): FraudBlacklistEntry
    {
        return FraudBlacklistEntry::create($data);
    }

    public function remove(string $id): void
    {
        FraudBlacklistEntry::destroy($id);
    }

    public function paginate(int $perPage = 15, ?string $type = null): iterable
    {
        $query = FraudBlacklistEntry::query();
        if ($type) {
            $query->where('type', $type);
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
