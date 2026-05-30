<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Enums;

enum ConsentStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
enum OpenFinanceScope: string
{
    case ACCOUNTS_READ = 'accounts:read';
    case ACCOUNTS_WRITE = 'accounts:write';
    case TRANSACTIONS_READ = 'transactions:read';
    case WALLET_READ = 'wallet:read';
    case WALLET_WRITE = 'wallet:write';
    case PROFILE_READ = 'profile:read';
}
