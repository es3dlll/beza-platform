<?php

declare(strict_types=1);

namespace Modules\Bills\Services;

use Modules\Bills\Models\BillProvider;
use Modules\Bills\DTOs\CreateBillProviderDto;
use Modules\Bills\Repositories\BillProviderRepository;

class BillProviderService
{
    public function __construct(
        private readonly BillProviderRepository $providerRepository,
    ) {}

    public function create(CreateBillProviderDto $dto): BillProvider
    {
        return $this->providerRepository->create($dto);
    }

    public function findById(string $id): ?BillProvider
    {
        return $this->providerRepository->findById($id);
    }

    public function findByCode(string $code): ?BillProvider
    {
        return $this->providerRepository->findByCode($code);
    }

    public function allActive(): iterable
    {
        return $this->providerRepository->findAllActive();
    }

    public function activeByCategory(string $category): iterable
    {
        return $this->providerRepository->findActiveByCategory($category);
    }
}
