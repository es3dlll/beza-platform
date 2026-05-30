<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;

final class PinService
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

        if (preg_match('/^\d{6}$/', $pin) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{5}$/', $pin)) {
            return false;
        }

        $sequential = ['123456', '234567', '345678', '456789', '567890',
                       '654321', '543210', '987654', '876543', '765432'];
        if (in_array($pin, $sequential, true)) {
            return false;
        }

        return true;
    }
}
