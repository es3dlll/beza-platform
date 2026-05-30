<?php

declare(strict_types=1);

namespace Modules\Bills\Enums;

enum BillPaymentStatus: string
{
    case PENDING_INQUIRY = 'pending_inquiry';
    case INQUIRED = 'inquired';
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_INQUIRY => 'Pending Inquiry',
            self::INQUIRED => 'Inquired',
            self::PENDING_PAYMENT => 'Pending Payment',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    public static function terminalStates(): array
    {
        return [self::PAID, self::FAILED, self::REFUNDED];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminalStates(), true);
    }
}
