<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Enums\VendorStatus;
use Modules\Marketplace\Models\Vendor;

final class VendorService
{
    public function register(array $data): Vendor
    {
        return Vendor::create($data);
    }

    public function approve(string $id): Vendor
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->update(['status' => VendorStatus::Approved]);

        return $vendor->fresh();
    }

    public function suspend(string $id): Vendor
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->update(['status' => VendorStatus::Suspended]);

        return $vendor->fresh();
    }

    public function listByStatus(string $status, int $perPage = 15): iterable
    {
        return Vendor::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findOrFail(string $id): Vendor
    {
        return Vendor::with('products')->findOrFail($id);
    }
}
