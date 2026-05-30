<?php
declare(strict_types=1);

namespace Modules\Ledger\Tests;

use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\DTOs\JournalLineDto;
use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Exceptions\DoubleEntryViolationException;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journal;
    private AccountService $accounts;
    private string $assetId;
    private string $liabilityId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->app->make(JournalService::class);
        $this->accounts = $this->app->make(AccountService::class);

        $this->assetId = $this->accounts->create(
            new CreateAccountDto('1000-ASSET', 'Test Asset', 'asset')
        )->id;

        $this->liabilityId = $this->accounts->create(
            new CreateAccountDto('2000-LIAB', 'Test Liability', 'liability')
        )->id;
    }

    public function test_can_post_balanced_entry(): void
    {
        $dto = new PostEntryDto(
            referenceType: 'test',
            referenceId: 'ref-001',
            description: 'Test entry',
            lines: [
                new JournalLineDto($this->assetId, 1000, 'debit', 'Dr test'),
                new JournalLineDto($this->liabilityId, 1000, 'credit', 'Cr test'),
            ],
        );

        $entry = $this->journal->post($dto);

        $this->assertEquals(1000, $entry->total_amount);
        $this->assertCount(2, $entry->lines);
    }

    public function test_rejects_unbalanced_entry(): void
    {
        $dto = new PostEntryDto(
            referenceType: 'test',
            referenceId: 'ref-002',
            description: 'Unbalanced',
            lines: [
                new JournalLineDto($this->assetId, 1000, 'debit', 'Dr only'),
            ],
        );

        $this->expectException(DoubleEntryViolationException::class);
        $this->journal->post($dto);
    }
}
