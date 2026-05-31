<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Enums\SanctionMatchType;
use App\Modules\Compliance\Events\SanctionHit;
use App\Modules\Compliance\Models\SanctionList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class SanctionService
{
    const CACHE_KEY = 'sanction_list';
    const CACHE_TTL = 21600;

    public function check(string $name, ?string $phone = null, ?string $deviceFingerprint = null): array
    {
        $hits = [];

        $sanctions = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SanctionList::where('active', true)->get();
        });

        foreach ($sanctions as $entry) {
            $matchType = $this->match($entry, $name, $phone, $deviceFingerprint);
            if ($matchType !== null) {
                $hits[] = [
                    'sanction_id' => $entry->id,
                    'name' => $entry->name,
                    'source' => $entry->source,
                    'match_type' => $matchType,
                    'sanction_ref' => $entry->sanction_ref,
                ];

                Event::dispatch(new SanctionHit(
                    name: $name,
                    matchType: $matchType,
                    source: $entry->source,
                    timestamp: now()->getTimestamp(),
                ));
            }
        }

        return $hits;
    }

    private function match(SanctionList $entry, string $name, ?string $phone, ?string $deviceFingerprint): ?string
    {
        if (strtolower($entry->name) === strtolower($name)) {
            return SanctionMatchType::EXACT;
        }

        if ($entry->alias && stripos($entry->alias, $name) !== false) {
            return SanctionMatchType::ALIAS;
        }

        if ($phone && $entry->phone && $entry->phone === $phone) {
            return SanctionMatchType::PARTIAL;
        }

        if ($deviceFingerprint && $entry->device_fingerprint && $entry->device_fingerprint === $deviceFingerprint) {
            return SanctionMatchType::DEVICE_FINGERPRINT;
        }

        return null;
    }

    public function importEntry(string $name, string $source, string $matchType, ?string $alias = null, ?string $phone = null, ?string $deviceFingerprint = null, ?string $country = null, ?string $sanctionRef = null): SanctionList
    {
        return SanctionList::create([
            'name' => $name,
            'alias' => $alias,
            'phone' => $phone,
            'device_fingerprint' => $deviceFingerprint,
            'source' => $source,
            'match_type' => $matchType,
            'country' => $country,
            'sanction_ref' => $sanctionRef,
            'active' => true,
        ]);
    }
}
