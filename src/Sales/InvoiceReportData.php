<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Optional;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of an invoice through its lifecycle (draft, approved, paid, void).
 *
 * Routing key: invoice.snapshot.synced  (sales.events)
 * Consumer:    reports-ms HandleInvoiceSnapshot -> invoice_snapshots, upsert by invoice_id
 *
 * Items are Optional, not nullable: approval-time publishes omit them and the
 * consumer must not overwrite stored items with an empty list. Omit `items`
 * for status-only publishes; include the full list on create/edit.
 */
final class InvoiceReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<DocumentItemReportData>|Optional $items
     */
    public function __construct(
        public int|string $invoice_id,
        public int $business_id,
        public string $invoice_number,
        public string $status,
        /** ISO-8601 */
        public string $invoice_date,
        public float $grand_total,
        public ?string $due_date = null,
        public int|string|null $terminal_id = null,
        public ?int $location_id = null,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public int|string|null $created_by = null,
        public int|string|null $approved_by = null,
        public string $currency = 'KES',
        public float $sub_total = 0.0,
        public float $discount_total = 0.0,
        public float $tax_total = 0.0,
        public ?float $total_cost = null,
        #[DataCollectionOf(DocumentItemReportData::class)]
        public array|Optional $items = new Optional(),
    ) {}

    public function eventName(): string
    {
        return 'invoice.snapshot.synced';
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
        return 'invoice';
    }

    public function aggregateId(): string|int
    {
        return $this->invoice_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->invoice_date);
    }

    public function actorId(): int|string|null
    {
        return $this->approved_by ?? $this->created_by;
    }
}
