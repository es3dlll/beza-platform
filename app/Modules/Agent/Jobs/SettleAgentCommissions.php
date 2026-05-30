<?php

declare(strict_types=1);

namespace Modules\Agent\Jobs;

use Modules\Agent\Models\AgentCommission;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class SettleAgentCommissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PostingEngine $posting): void
    {
        $pending = AgentCommission::where('status', 'pending')->get();

        foreach ($pending as $commission) {
            try {
                $instruction = new PostingInstructionDto(
                    referenceType: 'commission_settlement',
                    referenceId: Str::ulid()->toBase32(),
                    description: "Agent commission settlement {$commission->id}",
                    lines: [
                        [
                            'account_id' => '4000-000',
                            'amount' => $commission->amount,
                            'type' => 'debit',
                            'description' => 'Commission expense',
                        ],
                        [
                            'account_id' => $commission->agent_id,
                            'amount' => $commission->amount,
                            'type' => 'credit',
                            'description' => "Commission to agent {$commission->agent_id}",
                        ],
                    ],
                    channel: 'system',
                );

                $result = $posting->execute($instruction);

                if ($result->success) {
                    $commission->status = 'settled';
                    $commission->settled_at = now();
                    $commission->save();
                }
            } catch (\Exception $e) {
                logger("Commission settlement failed for {$commission->id}: {$e->getMessage()}");
            }
        }
    }
}
