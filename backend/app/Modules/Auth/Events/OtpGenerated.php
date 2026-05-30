<?php

declare(strict_types=1);

namespace Modules\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly string $purpose,
    ) {}
}
