<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A credit note was issued against a sale or invoice.
 *
 * Routing key: sales.credit_note_issued  (sales.events)
 * Consumer:    reports-ms HandleCreditNoteSnapshot -> credit_note_snapshots,
 *              upsert by credit_note_id.
 *
 * Classified as a snapshot: the routing key reads like an event, but the
 * payload is the credit note's full state and the consumer upserts it. The
 * routing key is preserved as-is.
 */
final class CreditNoteReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<CreditNoteItemReportData> $items
     */
    public function __construct(
        public int|string $credit_note_id,
        public int $business_id,
        public string $credit_note_number,
        public float $total_amount,
        public int|string|null $sale_id = null,
        public int|string|null $invoice_id = null,
        public ?int $location_id = null,
        public int|string|null $issued_by = null,
        /** ISO-8601 */
        public ?string $issued_at = null,
        public float $total_cost = 0.0,
        #[DataCollectionOf(CreditNoteItemReportData::class)]
        public array $items = [],
    ) {}

    public function eventName(): string
    {
        return 'sales.credit_note_issued';
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
        return 'credit_note';
    }

    public function aggregateId(): string|int
    {
        return $this->credit_note_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->issued_at);
    }

    public function actorId(): int|string|null
    {
        return $this->issued_by;
    }
}
