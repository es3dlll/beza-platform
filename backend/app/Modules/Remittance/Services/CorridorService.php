<?php

declare(strict_types=1);

namespace Modules\Remittance\Services;

use Modules\Remittance\Models\Corridor;
use Modules\Remittance\DTOs\CreateCorridorDto;
use Modules\Remittance\Repositories\CorridorRepository;
use Modules\Remittance\Exceptions\RemittanceCorridorUnavailableException;

class CorridorService
{
    public function __construct(
        private readonly CorridorRepository $corridorRepository,
    ) {}

    public function create(CreateCorridorDto $dto): Corridor
    {
        return $this->corridorRepository->create($dto);
    }

    public function getActive(string $sourceCountry): Corridor
    {
        $corridor = $this->corridorRepository->findActiveByCountry($sourceCountry);
        if (!$corridor) {
            throw new RemittanceCorridorUnavailableException($sourceCountry);
        }
        return $corridor;
    }

    public function allActive(): iterable
    {
        return $this->corridorRepository->findAllActive();
    }

    public function findById(string $id): ?Corridor
    {
        return $this->corridorRepository->findById($id);
    }
}
