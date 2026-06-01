<?php

namespace App\Core\Enums;

enum TransactionType: string
{
    case Transfer = 'transfer';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Payment = 'payment';
    case Refund = 'refund';
    case Fee = 'fee';
    case Exchange = 'exchange';
    case Reversal = 'reversal';
}
