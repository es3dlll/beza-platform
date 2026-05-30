<?php

declare(strict_types=1);

namespace Modules\Fraud\Enums;

enum FraudRuleType: string
{
    case VELOCITY = 'velocity';
    case GEOLOCATION_ANOMALY = 'geolocation_anomaly';
    case DEVICE_FINGERPRINT = 'device_fingerprint';
    case SANCTIONS_SCREENING = 'sanctions_screening';
    case IP_BLACKLIST = 'ip_blacklist';
    case DEVICE_BLACKLIST = 'device_blacklist';
    case AMOUNT_THRESHOLD = 'amount_threshold';
    case BEHAVIORAL = 'behavioral';
}
