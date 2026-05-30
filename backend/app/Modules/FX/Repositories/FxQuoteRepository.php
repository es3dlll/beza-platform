<?php

declare(strict_types=1);

namespace Modules\FX\Repositories;

use Illuminate\Support\Collection;
use Modules\FX\Models\FxQuote;

final class FxQuoteRepository
{
    public function findById(string $id): ?FxQuote
    {
        return FxQuote::find($id);
    }

    public function findActive(string $id): ?FxQuote
    {
        return FxQuote::where('id', $id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function findExpired(): Collection
    {
        return FxQuote::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();
    }

    public function findByRequestor(string $requestorId, string $requestorType, int $limit = 20): Collection
    {
        return FxQuote::where('requestor_id', $requestorId)
            ->where('requestor_type', $requestorType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function save(FxQuote $quote): FxQuote
    {
        $quote->save();
        return $quote;
    }
}
