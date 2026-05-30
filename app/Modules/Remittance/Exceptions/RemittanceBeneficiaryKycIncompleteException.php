<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceBeneficiaryKycIncompleteException extends Exception
{
    public function __construct(string $beneficiaryId)
    {
        parent::__construct("Beneficiary KYC incomplete: {$beneficiaryId}");
    }
}
