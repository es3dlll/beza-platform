<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

final class SanctionMatchType
{
    const EXACT = 'EXACT';
    const PARTIAL = 'PARTIAL';
    const ALIAS = 'ALIAS';
    const GEOGRAPHIC = 'GEOGRAPHIC';
    const DEVICE_FINGERPRINT = 'DEVICE_FINGERPRINT';
}
