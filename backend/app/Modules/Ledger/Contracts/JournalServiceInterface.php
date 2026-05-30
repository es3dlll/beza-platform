<?php

declare(strict_types=1);

namespace Modules\Ledger\Contracts;

use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Models\JournalEntry;

interface JournalServiceInterface
{
    public function post(PostEntryDto $dto): JournalEntry;
}
