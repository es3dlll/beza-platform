<?php

declare(strict_types=1);

namespace Modules\Wallet\Jobs;

use Modules\Wallet\DTOs\TransferDto;
use Modules\Wallet\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessOfflineTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly TransferDto $dto,
    ) {}

    public function handle(WalletService $wallets): void
    {
        try {
            $wallets->transfer($this->dto);
            logger('Offline transfer processed', [
                'from' => $this->dto->fromWalletId,
                'to' => $this->dto->toWalletId,
                'amount' => $this->dto->amount,
            ]);
        } catch (\Exception $e) {
            logger('Offline transfer failed', [
                'error' => $e->getMessage(),
                'dto' => $this->dto,
            ]);
            $this->fail($e);
        }
    }
}
