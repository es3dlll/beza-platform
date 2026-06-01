# 10 - ApprovalNotificationService

```php
<?php
// app/Services/Admin/ApprovalNotificationService.php

namespace App\Services\Admin;

use App\Models\Merchant;
use App\Models\User;
use App\Notifications\MerchantApplicationStatus;
use App\Notifications\AgentApplicationStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ApprovalNotificationService
{
    public function notifyMerchantApproved(Merchant $merchant): void
    {
        try {
            $merchant->user->notify(new MerchantApplicationStatus(
                status: 'approved',
                businessName: $merchant->business_name,
                message: 'تهانينا! تمت الموافقة على طلب تسجيلك كتاجر في منصة Beza. يمكنك الآن البدء في بيع منتجاتك.',
            ));

            // إشعار للمشرفين الآخرين
            $this->notifyAdmins(
                "تمت الموافقة على تاجر جديد: {$merchant->business_name}"
            );
        } catch (\Throwable $e) {
            Log::error("Failed to notify merchant approval", [
                'merchant_id' => $merchant->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    public function notifyMerchantRejected(Merchant $merchant, string $reason): void
    {
        try {
            $merchant->user->notify(new MerchantApplicationStatus(
                status: 'rejected',
                businessName: $merchant->business_name,
                message: "نأسف، لم تتم الموافقة على طلب تسجيلك كتاجر. السبب: {$reason}. يمكنك تقديم طلب جديد بعد استيفاء الشروط.",
                rejectionReason: $reason,
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to notify merchant rejection", [
                'merchant_id' => $merchant->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    public function notifyAgentApproved($agent): void
    {
        try {
            $agent->user->notify(new AgentApplicationStatus(
                status: 'approved',
                officeName: $agent->office_name,
                message: 'تمت الموافقة على طلب تسجيلك كوكيل في منصة Beza.',
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to notify agent approval", [
                'agent_id' => $agent->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    public function notifyAgentRejected($agent, string $reason): void
    {
        try {
            $agent->user->notify(new AgentApplicationStatus(
                status: 'rejected',
                officeName: $agent->office_name,
                message: "نأسف، لم تتم الموافقة على طلب تسجيلك كوكيل. السبب: {$reason}.",
                rejectionReason: $reason,
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to notify agent rejection", [
                'agent_id' => $agent->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdmins(string $message): void
    {
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new \App\Notifications\Admin\AdminAlertNotification($message));
    }
}
```
