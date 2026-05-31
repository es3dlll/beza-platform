<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Services;

use App\Modules\BillProvider\Models\BillProvider;

final class BillProviderCatalogService
{
    public function getAll(?string $category = null): array
    {
        $query = BillProvider::active()->orderBy('name');
        if ($category) {
            $query->byCategory($category);
        }
        return $query->get()->all();
    }

    public function findByCategory(string $category): array
    {
        return BillProvider::active()->byCategory($category)->orderBy('name')->get()->all();
    }

    public function toggleActive(string $id): ?BillProvider
    {
        $provider = BillProvider::find($id);
        if (!$provider) return null;
        $provider->update(['is_active' => !$provider->is_active]);
        return $provider->fresh();
    }

    public function register(array $data): BillProvider
    {
        return BillProvider::create($data);
    }

    public function updateProvider(string $id, array $data): ?BillProvider
    {
        $provider = BillProvider::find($id);
        if (!$provider) return null;
        $provider->update($data);
        return $provider->fresh();
    }
}
