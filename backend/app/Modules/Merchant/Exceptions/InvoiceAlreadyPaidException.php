<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

final class InvoiceAlreadyPaidException extends MerchantException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice {$invoiceId} is already paid");
    }
}
