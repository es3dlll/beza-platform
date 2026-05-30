<?php

declare(strict_types=1);

namespace Modules\Agent\Contracts;

use Modules\Agent\DTOs\RegisterAgentDto;
use Modules\Agent\DTOs\CashInDto;
use Modules\Agent\DTOs\CashOutDto;
use Modules\Agent\Models\Agent;

interface AgentServiceInterface
{
    public function register(RegisterAgentDto $dto): Agent;
    public function approve(string $agentId, string $approvedBy): Agent;
    public function cashIn(CashInDto $dto): array;
    public function cashOut(CashOutDto $dto): array;
    public function getNearby(string $governorate, ?float $lat = null, ?float $lng = null, ?int $radius = null): array;
    public function getTodaySummary(string $agentId): array;
    public function getLiquidityScore(string $agentId): array;
    public function updateCoverageRadius(string $agentId, int $meters): Agent;
}
