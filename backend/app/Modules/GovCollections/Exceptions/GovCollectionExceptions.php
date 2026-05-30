<?php

declare(strict_types=1);

namespace Modules\GovCollections\Exceptions;

use Exception;

final class GovServiceProviderNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Provider not found: {$id}"); }
}
final class GovInquiryExpiredException extends Exception
{
    public function __construct() { parent::__construct('Payment inquiry has expired'); }
}
final class GovPaymentFailedException extends Exception
{
    public function __construct(string $reason) { parent::__construct("Payment failed: {$reason}"); }
}
