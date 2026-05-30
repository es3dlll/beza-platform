<?php

declare(strict_types=1);

namespace Modules\GovCollections\Services;

use Illuminate\Support\Str;
use Modules\GovCollections\Models\GovServiceProvider;
use Modules\GovCollections\Models\GovPaymentInquiry;
use Modules\GovCollections\Models\GovCollection;
use Modules\GovCollections\Enums\GovCollectionStatus;
use Modules\GovCollections\Exceptions\GovServiceProviderNotFoundException;
use Modules\GovCollections\Exceptions\GovInquiryExpiredException;

final class GovCollectionService
{
    public function findProvider(string $id): GovServiceProvider
    {
        $p = GovServiceProvider::find($id);
        if (!$p) throw new GovServiceProviderNotFoundException($id);
        return $p;
    }

    public function listProviders(): iterable
    {
        return GovServiceProvider::where('is_active', true)->get();
    }

    public function inquire(string $userId, string $providerId, string $serviceCode, string $accountNumber): GovPaymentInquiry
    {
        $this->findProvider($providerId);
        $amount = random_int(1000, 500000);
        return GovPaymentInquiry::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'provider_id' => $providerId,
            'service_code' => $serviceCode,
            'account_number' => $accountNumber,
            'amount_due' => $amount,
            'fee' => 0,
            'status' => 'active',
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function pay(string $userId, string $inquiryId, string $channel = 'mobile'): GovCollection
    {
        $inquiry = GovPaymentInquiry::findOrFail($inquiryId);
        if ($inquiry->expires_at && $inquiry->expires_at->isPast()) {
            throw new GovInquiryExpiredException;
        }
        $collection = GovCollection::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'provider_id' => $inquiry->provider_id,
            'inquiry_id' => $inquiryId,
            'service_code' => $inquiry->service_code,
            'account_number' => $inquiry->account_number,
            'amount' => $inquiry->amount_due,
            'fee' => $inquiry->fee,
            'status' => GovCollectionStatus::PAID->value,
            'channel' => $channel,
            'receipt_number' => 'GV-' . strtoupper(Str::random(10)),
            'paid_at' => now(),
        ]);
        $inquiry->update(['status' => 'paid']);
        return $collection;
    }

    public function history(string $userId): iterable
    {
        return GovCollection::where('user_id', $userId)->orderByDesc('created_at')->get();
    }
}
