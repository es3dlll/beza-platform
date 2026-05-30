<?php

declare(strict_types=1);

namespace Modules\Wallet\Contracts;

use Modules\Wallet\DTOs\CreateWalletDto;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\DTOs\TransferDto;
use Modules\Wallet\Models\Wallet;

interface WalletServiceInterface
{
    public function create(CreateWalletDto $dto): Wallet;
    public function deposit(DepositDto $dto): Wallet;
    public function withdraw(WithdrawDto $dto): Wallet;
    public function transfer(TransferDto $dto): array;
    public function getBalance(string $walletId): array;
    public function getTransactions(string $walletId, int $limit = 20): array;
}
