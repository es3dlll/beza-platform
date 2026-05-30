<?php

declare(strict_types=1);

namespace Modules\Bills\Repositories;

use Modules\Bills\Models\BillProvider;
use Modules\Bills\DTOs\CreateBillProviderDto;

class BillProviderRepository
{
    public function create(CreateBillProviderDto $dto): BillProvider
    {
        return BillProvider::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'name_ar' => $dto->nameAr,
            'category' => $dto->category,
            'account_label' => $dto->accountLabel,
            'account_format_regex' => $dto->accountFormatRegex,
            'supported_account_types' => $dto->supportedAccountTypes,
            'fee_percentage' => $dto->feePercentage,
            'fee_min_syp' => $dto->feeMinSyp,
            'fee_max_syp' => $dto->feeMaxSyp,
            'is_active' => $dto->isActive,
            'integration_config' => $dto->integrationConfig,
        ]);
    }

    public function findById(string $id): ?BillProvider
    {
        return BillProvider::find($id);
    }

    public function findByCode(string $code): ?BillProvider
    {
        return BillProvider::where('code', $code)->first();
    }

    public function findActiveByCategory(string $category): iterable
    {
        return BillProvider::where('category', $category)->where('is_active', true)->get();
    }

    public function findAllActive(): iterable
    {
        return BillProvider::where('is_active', true)->get();
    }
}
