<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;

class PinService
{
    public function hash(string $pin): string
    {
        return Hash::make($pin);
    }

    public function verify(string $pin, string $hash): bool
    {
        return Hash::check($pin, $hash);
    }

    public function validatePinFormat(string $pin): bool
    {
        if (strlen($pin) !== 6) {
            return false;
        }

        return preg_match('/^\d{6}$/', $pin) === 1;
    }
}
