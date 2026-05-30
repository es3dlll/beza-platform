<?php

declare(strict_types=1);

namespace Modules\Identity\Repositories;

use Modules\Identity\Models\Device;
use Modules\Identity\Exceptions\DeviceLimitExceededException;

final class DeviceRepository
{
    public const int MAX_DEVICES = 5;

    public function findByDeviceId(string $deviceId): ?Device
    {
        return Device::where('device_id', $deviceId)->first();
    }

    public function bindToUser(string $userId, string $deviceId, array $data): Device
    {
        $existing = $this->findByDeviceId($deviceId);

        if ($existing !== null) {
            $existing->update(array_merge($data, [
                'user_id' => $userId,
                'last_used_at' => now(),
            ]));

            return $existing->fresh();
        }

        if ($this->countUserDevices($userId) >= self::MAX_DEVICES) {
            throw new DeviceLimitExceededException(
                "User {$userId} has reached the maximum of " . self::MAX_DEVICES . " devices."
            );
        }

        return Device::create(array_merge($data, [
            'user_id' => $userId,
            'device_id' => $deviceId,
            'is_trusted' => false,
            'last_used_at' => now(),
        ]));
    }

    public function countUserDevices(string $userId): int
    {
        return Device::where('user_id', $userId)->count();
    }

    public function unbindDevice(string $deviceId): void
    {
        Device::where('device_id', $deviceId)->delete();
    }

    public function unbindAllUserDevices(string $userId): void
    {
        Device::where('user_id', $userId)->delete();
    }

    public function findUserDevices(string $userId): iterable
    {
        return Device::where('user_id', $userId)->get();
    }

    public function markTrusted(string $deviceId): void
    {
        Device::where('device_id', $deviceId)->update(['is_trusted' => true]);
    }

    public function updateFcmToken(string $deviceId, string $fcmToken): void
    {
        Device::where('device_id', $deviceId)->update(['fcm_token' => $fcmToken]);
    }
}
