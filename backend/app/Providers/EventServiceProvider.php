<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\LogAllEvents;
use App\Listeners\CreateDatabaseNotification;
use App\Listeners\SendSmsNotification;
use App\Listeners\SendEmailNotification;
use Modules\Auth\Events\OtpGenerated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OtpGenerated::class => [
            LogAllEvents::class,
        ],
        \Modules\Auth\Events\UserLoggedIn::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Auth\Events\UserLoggedOut::class => [LogAllEvents::class],
        \Modules\Auth\Events\PinCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Auth\Events\PhoneVerified::class => [LogAllEvents::class],
        \Modules\Identity\Events\UserRegistered::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Identity\Events\DeviceBound::class => [LogAllEvents::class],
        \Modules\Wallet\Events\WalletCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Wallet\Events\WalletCredited::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Wallet\Events\WalletDebited::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Wallet\Events\WalletTransferInitiated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Ledger\Events\JournalEntryPosted::class => [LogAllEvents::class],
        \Modules\Ledger\Events\AccountBalanceChanged::class => [LogAllEvents::class],
        \Modules\Ledger\Events\HoldPlaced::class => [LogAllEvents::class],
        \Modules\Ledger\Events\HoldReleased::class => [LogAllEvents::class],
        \Modules\CoreFinancialEngine\Events\TransactionPosted::class => [LogAllEvents::class],
        \Modules\CoreFinancialEngine\Events\TransactionReversed::class => [LogAllEvents::class],
        \Modules\CoreFinancialEngine\Events\FeeApplied::class => [LogAllEvents::class],
        \Modules\CoreFinancialEngine\Events\SettlementCompleted::class => [LogAllEvents::class],
        \Modules\FX\Events\FxRateUpdated::class => [LogAllEvents::class],
        \Modules\FX\Events\FxQuoteCreated::class => [LogAllEvents::class],
        \Modules\FX\Events\FxQuoteAccepted::class => [LogAllEvents::class],
        \Modules\FX\Events\FxQuoteExpired::class => [LogAllEvents::class],
        \Modules\FX\Events\FxConversionCompleted::class => [LogAllEvents::class],
        \Modules\FX\Events\FxConversionFailed::class => [LogAllEvents::class],
        \Modules\Float\Events\FloatCredited::class => [LogAllEvents::class],
        \Modules\Float\Events\FloatDebited::class => [LogAllEvents::class],
        \Modules\Settlement\Events\SettlementCompleted::class => [LogAllEvents::class],
        \Modules\Agent\Events\AgentRegistered::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Agent\Events\AgentApproved::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Agent\Events\AgentCashInCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Agent\Events\AgentCashOutCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Cards\Events\CardCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Cards\Events\CardActivated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Cards\Events\CardSuspended::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Cards\Events\CardTransactionAuthorized::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Cards\Events\CardTransactionDeclined::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Savings\Events\SavingsGoalCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Savings\Events\SavingsGoalCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Savings\Events\SavingsContributionMade::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Savings\Events\SavingsWithdrawn::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Savings\Events\SavingsProfitDistributed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Loyalty\Events\PointsAwarded::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Loyalty\Events\PointsRedeemed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Loyalty\Events\CashbackApplied::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Loyalty\Events\TierUpgraded::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Merchant\Events\MerchantRegistered::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Merchant\Events\MerchantApproved::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Merchant\Events\MerchantPaymentCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Bills\Events\BillPaid::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Bills\Events\BillPaymentFailed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Bills\Events\BillInquiryCompleted::class => [LogAllEvents::class],
        \Modules\Bills\Events\BillRefunded::class => [LogAllEvents::class],
        \Modules\Remittance\Events\RemittanceOrderCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Remittance\Events\RemittanceOrderCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Remittance\Events\RemittanceOrderFailed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Remittance\Events\RemittanceOrderScreened::class => [LogAllEvents::class],
        \Modules\Remittance\Events\RemittanceOrderPaidIn::class => [LogAllEvents::class],
        \Modules\Remittance\Events\RemittanceOrderRefunded::class => [LogAllEvents::class],
        \Modules\Payroll\Events\PayrollDisbursementCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Payroll\Events\PayrollDisbursementFailed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Payroll\Events\PayrollBatchCreated::class => [LogAllEvents::class],
        \Modules\Payroll\Events\PayrollBatchApproved::class => [LogAllEvents::class],
        \Modules\Fraud\Events\FraudCaseCreated::class => [LogAllEvents::class],
        \Modules\Fraud\Events\FraudDeviceBlacklisted::class => [LogAllEvents::class],
        \Modules\Fraud\Events\FraudTransactionBlocked::class => [
            LogAllEvents::class,
            SendSmsNotification::class,
        ],
        \Modules\Payroll\Events\EmployerRegistered::class => [
            LogAllEvents::class,
            SendEmailNotification::class,
        ],
        \Modules\Education\Events\StudentRegistered::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Education\Events\FeePaid::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Financing\Events\LoanApplied::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Financing\Events\LoanApproved::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Financing\Events\LoanDisbursed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Financing\Events\LoanRepaid::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\GovCollections\Events\GovPaymentCompleted::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Humanitarian\Events\AidDisbursed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Humanitarian\Events\AidProgramCreated::class => [LogAllEvents::class],
        \Modules\IAM\Events\RoleCreated::class => [LogAllEvents::class],
        \Modules\IAM\Events\PermissionAssigned::class => [LogAllEvents::class],
        \Modules\OpenFinance\Events\AppRegistered::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\OpenFinance\Events\ConsentCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\OpenFinance\Events\ConsentRevoked::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],

        // Marketplace
        \Modules\Marketplace\Events\OrderPlaced::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Marketplace\Events\OrderFulfilled::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Marketplace\Events\OrderRefunded::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],

        // Takaful
        \Modules\Takaful\Events\PolicySubscribed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Takaful\Events\ClaimFiled::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Takaful\Events\ClaimApproved::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],

        // Investments
        \Modules\Investments\Events\Subscribed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Investments\Events\Redeemed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],

        // Escrow
        \Modules\Escrow\Events\EscrowCreated::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Escrow\Events\EscrowReleased::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Escrow\Events\EscrowDisputed::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
        \Modules\Escrow\Events\EscrowResolved::class => [
            LogAllEvents::class,
            CreateDatabaseNotification::class,
        ],
    ];
}
