<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

final class SchemaVersionManager
{
    private string $currentVersion = 'v1';
    private array $supportedVersions = ['v1', 'v2'];

    public function __construct()
    {
        $configuredVersion = config('event_bus.schema.current_version', 'v1');
        if ($configuredVersion !== null) {
            $this->currentVersion = $configuredVersion;
        }

        $configuredSupported = config('event_bus.schema.supported_versions');
        if ($configuredSupported !== null) {
            $this->supportedVersions = $configuredSupported;
        }
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function isVersionSupported(string $version): bool
    {
        return in_array($version, $this->supportedVersions, true);
    }

    public function getSupportedVersions(): array
    {
        return $this->supportedVersions;
    }

    public function isBackwardCompatible(string $producerVersion, string $consumerVersion): bool
    {
        $versions = $this->supportedVersions;
        $producerIndex = array_search($producerVersion, $versions, true);
        $consumerIndex = array_search($consumerVersion, $versions, true);

        if ($producerIndex === false || $consumerIndex === false) {
            return false;
        }

        return $consumerIndex >= $producerIndex;
    }
}
