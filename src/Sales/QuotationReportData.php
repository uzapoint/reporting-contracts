<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a quotation.
 *
 * Routing key: quotation.snapshot.synced  (sales.events)
 * Consumer:    reports-ms HandleQuotationSnapshot -> quotation_snapshots, upsert by quotation_id
 */
final class QuotationReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<DocumentItemReportData> $items
     */
    public function __construct(
        public int|string $quotation_id,
        public int $business_id,
        public string $quote_number,
        public string $status,
        public float $grand_total,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public int|string|null $created_by = null,
        public int|string|null $approved_by = null,
        public string $currency = 'KES',
        public float $sub_total = 0.0,
        public float $discount_total = 0.0,
        public float $tax_total = 0.0,
        public float $total_cost = 0.0,
        #[DataCollectionOf(DocumentItemReportData::class)]
        public array $items = [],
    ) {}

    public function eventName(): string
    {
        return 'quotation.snapshot.synced';
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
        return 'quotation';
    }

    public function aggregateId(): string|int
    {
        return $this->quotation_id;
    }

    public function actorId(): int|string|null
    {
        return $this->approved_by ?? $this->created_by;
    }
}
