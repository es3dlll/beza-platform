<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Exceptions;

use Exception;
use Throwable;

final class ReconciliationFailedException extends Exception
{
    public function __construct(string $message = 'Ledger reconciliation failed', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function balanceMismatch(string $accountCode, string $expected, string $actual, string $difference): self
    {
        return new self(sprintf(
            'Balance mismatch for account %s: expected %s, actual %s, difference %s',
            $accountCode, $expected, $actual, $difference
        ));
    }

    public static function cbsReportGenerationFailed(string $reportType, string $reason): self
    {
        return new self(sprintf('Failed to generate CBS %s report: %s', $reportType, $reason));
    }
}
