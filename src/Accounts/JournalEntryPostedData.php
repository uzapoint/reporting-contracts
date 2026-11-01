<?php
// uzapoint/reporting-contracts /src/Accounts/JournalEntryPostedData.php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A journal entry was posted (full state, with every line).
 *
 * Routing key: accounts.journal.posted  (accounts.events)
 * Consumer:    reports-ms HandleLedgerJournalPosted -> ledger_lines, upsert by (business_id, line_id);
 *              lines missing from a republished entry are removed, so replays never double-count.
 *
 * Published for entries in 'posted' or 'reversed' status — a reversed original still counts, because
 * its reversal entry is posted too and the two offset.
 *
 * No idempotencyKey(): the consumer is idempotent by line id, and a fixed key would make
 * `accounting:republish-ledger` a no-op for the duplicate-suppression window.
 */
final class JournalEntryPostedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $journal_entry_id,
        public int $business_id,
        public string $reference,
        /** Calendar date, YYYY-MM-DD. */
        public string $entry_date,
        /** posted | reversed */
        public string $posting_status,
        public float $total_debit,
        public float $total_credit,
        #[DataCollectionOf(JournalEntryLineData::class)]
        public array $lines,
        public ?string $entry_uuid = null,
        public ?string $entry_type = null,
        public ?string $source_module = null,
        public ?string $sourceable_type = null,
        public int|string|null $sourceable_id = null,
        /** 'primary' or e.g. 'reversal:{id}' — distinguishes several entries from one source. */
        public ?string $event_key = null,
        public ?string $fiscal_period_key = null,
        public int|string|null $reverses_entry_id = null,
        public ?string $currency = null,
        public ?string $description = null,
        public int|string|null $branch_id = null,
        public int|string|null $terminal_id = null,
        /** ISO-8601 */
        public ?string $posted_at = null,
        public int|string|null $user_id = null,
    ) {
    }

    public function eventName(): string
    {
        return 'accounts.journal.posted';
    }

    public function kind(): ReportKind
    {
        return ReportKind::Snapshot;
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
        return $this->timestamp($this->posted_at);
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
