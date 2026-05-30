<?php

declare(strict_types=1);

namespace Modules\Fraud\Services;

final class GeolocationAnomalyService
{
    private const MAX_TRAVEL_KM_PER_HOUR = 900;
    private const EARTH_RADIUS_KM = 6371;

    public function check(?float $currentLat, ?float $currentLon, ?float $lastLat, ?float $lastLon, ?int $lastTimestamp): int
    {
        if ($currentLat === null || $currentLon === null || $lastLat === null || $lastLon === null || $lastTimestamp === null) {
            return 0;
        }

        $distance = $this->haversine($lastLat, $lastLon, $currentLat, $currentLon);
        $hoursElapsed = (time() - $lastTimestamp) / 3600;

        if ($hoursElapsed <= 0.001) {
            return $distance > 100 ? 300 : 0;
        }

        $speed = $distance / $hoursElapsed;

        if ($speed > self::MAX_TRAVEL_KM_PER_HOUR * 3) {
            return 300;
        }

        if ($speed > self::MAX_TRAVEL_KM_PER_HOUR) {
            return 100;
        }

        return 0;
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
