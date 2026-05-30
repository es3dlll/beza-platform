<?php

declare(strict_types=1);

return [
    'otp_expiry_minutes' => (int) env('OTP_EXPIRY', 5),
    'max_otp_attempts' => 5,
    'pin_attempts_before_lock' => 5,
    'pin_lock_minutes' => 30,
    'max_devices_per_user' => 2,
    'session_ttl_minutes' => 10,
    'jwt_ttl' => (int) env('JWT_TTL', 15),
    'jwt_refresh_ttl' => (int) env('JWT_REFRESH_TTL', 10080),
];
