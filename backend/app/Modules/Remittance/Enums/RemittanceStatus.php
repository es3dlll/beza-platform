<?php

declare(strict_types=1);

namespace Modules\Remittance\Enums;

enum RemittanceStatus: string
{
    case PENDING = 'pending';
    case SCREENING = 'screening';
    case SCREENING_FAILED = 'screening_failed';
    case QUOTED = 'quoted';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PAID_IN = 'paid_in';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SCREENING => 'Under Compliance Screening',
            self::SCREENING_FAILED => 'Screening Rejected',
            self::QUOTED => 'Quoted',
            self::AWAITING_PAYMENT => 'Awaiting Payment',
            self::PAID_IN => 'Paid In',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
        };
    }

    public static function terminalStates(): array
    {
        return [self::COMPLETED, self::FAILED, self::REFUNDED, self::EXPIRED, self::SCREENING_FAILED];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminalStates(), true);
    }
}
