<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

final class SuspenseNotBalancedException extends FxException
{
    public function __construct(string $message = 'Suspense account not balanced after FX settlement', int $code = 5005)
    {
        parent::__construct($message, $code);
    }
}
