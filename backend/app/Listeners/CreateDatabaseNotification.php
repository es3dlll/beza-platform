<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Notification\Models\Notification;

final class CreateDatabaseNotification
{
    public function handle(object $event): void
    {
        try {
            $userId = null;

            if (method_exists($event, 'userId') && property_exists($event, 'userId') && $event->userId) {
                $userId = $event->userId;
            } elseif (method_exists($event, 'getUserId')) {
                $userId = $event->getUserId();
            }

            if (!$userId) {
                return;
            }

            $title = $this->resolveTitle($event);
            if (!$title) {
                return;
            }

            Notification::create([
                'user_id' => $userId,
                'type' => class_basename($event),
                'channel' => 'database',
                'title' => $title,
                'body' => $this->resolveBody($event),
                'data' => $this->resolveData($event),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create notification', [
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveTitle(object $event): ?string
    {
        $class = $event::class;

        if (str_contains($class, 'Auth\Events\UserLoggedIn')) return 'تم تسجيل الدخول';
        if (str_contains($class, 'Auth\Events\PinCreated')) return 'تم إنشاء الرقم السري';
        if (str_contains($class, 'Identity\Events\UserRegistered')) return 'مرحباً بك في بزة';
        if (str_contains($class, 'Wallet\Events\WalletCreated')) return 'تم إنشاء المحفظة';
        if (str_contains($class, 'Wallet\Events\WalletCredited')) return 'إيداع في المحفظة';
        if (str_contains($class, 'Wallet\Events\WalletDebited')) return 'سحب من المحفظة';
        if (str_contains($class, 'Wallet\Events\WalletTransferInitiated')) return 'تحويل مالي';
        if (str_contains($class, 'Agent\Events\AgentRegistered')) return 'تم تقديم طلب الوكالة';
        if (str_contains($class, 'Agent\Events\AgentApproved')) return 'تم الموافقة على الوكالة';
        if (str_contains($class, 'Agent\Events\AgentCashInCompleted')) return 'إيداع نقدي لدى الوكيل';
        if (str_contains($class, 'Agent\Events\AgentCashOutCompleted')) return 'سحب نقدي من الوكيل';
        if (str_contains($class, 'Cards\Events\CardCreated')) return 'تم إصدار البطاقة';
        if (str_contains($class, 'Cards\Events\CardActivated')) return 'تم تفعيل البطاقة';
        if (str_contains($class, 'Cards\Events\CardSuspended')) return 'تم تعليق البطاقة';
        if (str_contains($class, 'Cards\Events\CardTransactionAuthorized')) return 'عملية بطاقة جديدة';
        if (str_contains($class, 'Cards\Events\CardTransactionDeclined')) return 'تم رفض عملية البطاقة';
        if (str_contains($class, 'Savings\Events\SavingsGoalCreated')) return 'تم إنشاء هدف ادخار';
        if (str_contains($class, 'Savings\Events\SavingsGoalCompleted')) return 'تم تحقيق هدف الادخار';
        if (str_contains($class, 'Savings\Events\SavingsContributionMade')) return 'إيداع في هدف الادخار';
        if (str_contains($class, 'Savings\Events\SavingsWithdrawn')) return 'سحب من هدف الادخار';
        if (str_contains($class, 'Savings\Events\SavingsProfitDistributed')) return 'توزيع أرباح الادخار';
        if (str_contains($class, 'Loyalty\Events\PointsAwarded')) return 'تم إضافة نقاط ولاء';
        if (str_contains($class, 'Loyalty\Events\PointsRedeemed')) return 'تم استبدال نقاط الولاء';
        if (str_contains($class, 'Loyalty\Events\CashbackApplied')) return 'استرداد نقدي';
        if (str_contains($class, 'Loyalty\Events\TierUpgraded')) return 'تم ترقية المستوى';
        if (str_contains($class, 'Merchant\Events\MerchantRegistered')) return 'تم تقديم طلب التاجر';
        if (str_contains($class, 'Merchant\Events\MerchantApproved')) return 'تم الموافقة على التاجر';
        if (str_contains($class, 'Merchant\Events\MerchantPaymentCompleted')) return 'عملية دفع للتاجر';
        if (str_contains($class, 'Bills\Events\BillPaid')) return 'تم دفع الفاتورة';
        if (str_contains($class, 'Bills\Events\BillPaymentFailed')) return 'فشل دفع الفاتورة';
        if (str_contains($class, 'Remittance\Events\RemittanceOrderCreated')) return 'تم إنشاء أمر حوالة';
        if (str_contains($class, 'Remittance\Events\RemittanceOrderCompleted')) return 'تم تنفيذ الحوالة';
        if (str_contains($class, 'Remittance\Events\RemittanceOrderFailed')) return 'فشل تنفيذ الحوالة';
        if (str_contains($class, 'Payroll\Events\PayrollDisbursementCompleted')) return 'تم صرف الراتب';
        if (str_contains($class, 'Payroll\Events\PayrollDisbursementFailed')) return 'فشل صرف الراتب';
        if (str_contains($class, 'Fraud\Events\FraudTransactionBlocked')) return 'تنبيه أمني: معاملة مشبوهة';
        if (str_contains($class, 'Education\Events\StudentRegistered')) return 'تم تسجيل الطالب';
        if (str_contains($class, 'Education\Events\FeePaid')) return 'تم دفع الرسوم الدراسية';
        if (str_contains($class, 'Financing\Events\LoanApplied')) return 'تم تقديم طلب تمويل';
        if (str_contains($class, 'Financing\Events\LoanApproved')) return 'تم الموافقة على التمويل';
        if (str_contains($class, 'Financing\Events\LoanDisbursed')) return 'تم صرف التمويل';
        if (str_contains($class, 'Financing\Events\LoanRepaid')) return 'تم تسديد قسط التمويل';
        if (str_contains($class, 'GovCollections\Events\GovPaymentCompleted')) return 'تم دفع الرسوم الحكومية';
        if (str_contains($class, 'Humanitarian\Events\AidDisbursed')) return 'تم صرف المساعدات';
        if (str_contains($class, 'OpenFinance\Events\AppRegistered')) return 'تم تسجيل تطبيق جديد';
        if (str_contains($class, 'OpenFinance\Events\ConsentCreated')) return 'تم منح الإذن للتطبيق';
        if (str_contains($class, 'OpenFinance\Events\ConsentRevoked')) return 'تم سحب الإذن من التطبيق';

        return null;
    }

    private function resolveBody(object $event): ?string
    {
        $class = $event::class;

        if (str_contains($class, 'Wallet\Events\WalletCredited')) {
            return "تم إيداع {$event->amount} {$event->currency} في محفظتك";
        }
        if (str_contains($class, 'Wallet\Events\WalletDebited')) {
            return "تم خصم {$event->amount} {$event->currency} من محفظتك";
        }
        if (str_contains($class, 'Wallet\Events\WalletTransferInitiated')) {
            return "تحويل {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Cards\Events\CardTransactionAuthorized')) {
            $merchant = $event->merchantName ?? 'متجر';
            return "عملية بقيمة {$event->amount} {$event->currency} في {$merchant}";
        }
        if (str_contains($class, 'Cards\Events\CardTransactionDeclined')) {
            return "تم رفض عملية بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Savings\Events\SavingsContributionMade')) {
            return "تم إضافة {$event->amount} {$event->currency} إلى هدف الادخار";
        }
        if (str_contains($class, 'Loyalty\Events\PointsAwarded')) {
            return "تمت إضافة {$event->points} نقطة ولاء";
        }
        if (str_contains($class, 'Loyalty\Events\PointsRedeemed')) {
            return "تم استبدال {$event->points} نقطة ولاء";
        }
        if (str_contains($class, 'Bills\Events\BillPaid')) {
            return "تم دفع فاتورة بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Bills\Events\BillPaymentFailed')) {
            return "فشل دفع الفاتورة. يرجى المحاولة مرة أخرى";
        }
        if (str_contains($class, 'Remittance\Events\RemittanceOrderCompleted')) {
            return "تم تحويل {$event->amount} {$event->currency} بنجاح";
        }
        if (str_contains($class, 'Remittance\Events\RemittanceOrderFailed')) {
            return "فشل تحويل {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Payroll\Events\PayrollDisbursementCompleted')) {
            return "تم صرف راتب بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Education\Events\FeePaid')) {
            return "تم دفع رسوم بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Financing\Events\LoanApplied')) {
            return "تقديم طلب تمويل بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Financing\Events\LoanApproved')) {
            return "تمت الموافقة على تمويل بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Financing\Events\LoanDisbursed')) {
            return "تم صرف تمويل بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'Financing\Events\LoanRepaid')) {
            return "تم تسديد قسط بقيمة {$event->amount} {$event->currency}";
        }
        if (str_contains($class, 'GovCollections\Events\GovPaymentCompleted')) {
            return "تم دفع {$event->amount} {$event->currency} مقابل {$event->serviceType}";
        }
        if (str_contains($class, 'Humanitarian\Events\AidDisbursed')) {
            return "تم صرف مساعدات بقيمة {$event->amount} {$event->currency}";
        }

        return null;
    }

    private function resolveData(object $event): ?array
    {
        $data = [];
        foreach (['amount', 'currency', 'status', 'reference'] as $prop) {
            if (property_exists($event, $prop)) {
                try {
                    $data[$prop] = $event->$prop;
                } catch (\Throwable) {
                }
            }
        }
        return empty($data) ? null : $data;
    }
}
