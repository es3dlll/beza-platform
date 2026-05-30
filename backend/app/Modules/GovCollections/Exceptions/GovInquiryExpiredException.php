<?php

declare(strict_types=1);

namespace Modules\GovCollections\Exceptions;

use Exception;

final class GovInquiryExpiredException extends Exception
{
    public function __construct() { parent::__construct('Payment inquiry has expired'); }
}
