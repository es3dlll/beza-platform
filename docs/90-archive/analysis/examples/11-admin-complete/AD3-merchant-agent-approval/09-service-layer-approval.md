# 09 - MerchantApprovalService

```php
<?php
// app/Services/Admin/MerchantApprovalService.php

namespace App\Services\Admin;

use App\Events\Admin\MerchantApproved;
use App\Events\Admin\MerchantRejected;
use App\Exceptions\Admin\ApplicationAlreadyProcessedException;
use App\Exceptions\Admin\DocumentsNotReviewedException;
use App\Exceptions\Admin\KycNotVerifiedException;
use App\Models\Admin\AdminActivityLog;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantApprovalService
{
    public function getPendingApplications(): Collection
    {
        return Merchant::with(['user', 'documents'])
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function getApplicationDetail(int $id): Merchant
    {
        return Merchant::with(['user.wallets', 'documents', 'reviewer'])
            ->findOrFail($id);
    }

    public function approve(int $merchantId, int $reviewerId): void
    {
        $merchant = Merchant::with('user')->findOrFail($merchantId);

        $this->validateForApproval($merchant);

        DB::transaction(function () use ($merchant, $reviewerId) {
            $merchant->approve($reviewerId);

            AdminActivityLog::create([
                'admin_id'    => $reviewerId,
                'action'      => 'approve_merchant',
                'target_type' => 'merchant',
                'target_id'   => $merchant->id,
            ]);
        });

        MerchantApproved::dispatch($merchant);

        Log::info("Merchant approved", [
            'merchant_id' => $merchantId,
            'business'    => $merchant->business_name,
            'by_admin'    => $reviewerId,
        ]);
    }

    public function reject(int $merchantId, string $reason, int $reviewerId): void
    {
        $merchant = Merchant::with('user')->findOrFail($merchantId);

        if ($merchant->status !== 'pending') {
            throw new ApplicationAlreadyProcessedException();
        }

        DB::transaction(function () use ($merchant, $reason, $reviewerId) {
            $merchant->reject($reason, $reviewerId);

            AdminActivityLog::create([
                'admin_id'    => $reviewerId,
                'action'      => 'reject_merchant',
                'target_type' => 'merchant',
                'target_id'   => $merchant->id,
                'metadata'    => ['reason' => $reason],
            ]);
        });

        MerchantRejected::dispatch($merchant, $reason);

        Log::info("Merchant rejected", [
            'merchant_id' => $merchantId,
            'reason'      => $reason,
            'by_admin'    => $reviewerId,
        ]);
    }

    private function validateForApproval(Merchant $merchant): void
    {
        if ($merchant->status !== 'pending') {
            throw new ApplicationAlreadyProcessedException();
        }

        if ($merchant->user->kyc_status !== 'verified') {
            throw new KycNotVerifiedException();
        }

        $pendingDocs = $merchant->documents()->where('status', 'pending')->count();
        if ($pendingDocs > 0) {
            throw new DocumentsNotReviewedException();
        }
    }
}
```
