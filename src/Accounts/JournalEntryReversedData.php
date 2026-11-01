<?php
// uzapoint/reporting-contracts  /src/Accounts/JournalEntryReversedData.php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A posted journal entry was reversed.
 *
 * Routing key: accounts.journal.reversed  (accounts.events)
 * Consumer:    reports-ms HandleLedgerJournalReversed -> marks the original's ledger lines 'reversed'.
 *              The reversal itself arrives as its own accounts.journal.posted, so sums need no change.
 */
final class JournalEntryReversedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        /** The original entry that was reversed. */
        public int $journal_entry_id,
        public int $business_id,
        public int $reversed_by_entry_id,
        public ?string $reference = null,
        public ?string $reason = null,
        /** ISO-8601 */
        public ?string $reversed_at = null,
        public int|string|null $user_id = null,
    ) {
    }

    public function eventName(): string
    {
        return 'accounts.journal.reversed';
    }

    public function kind(): ReportKind
    {
        return ReportKind::Event;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    public function aggregateType(): string
    {
        return 'journal_entry';
    }

    public function aggregateId(): string|int
    {
        return $this->journal_entry_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->reversed_at);
    }

    public function idempotencyKey(): ?string
    {
        return "journal:{$this->journal_entry_id}:reversed:{$this->reversed_by_entry_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
