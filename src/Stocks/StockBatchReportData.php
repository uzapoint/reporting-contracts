<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A stock batch (lot) as it stands: what is left of it, at what cost, and when it expires.
 * Republished whenever the batch changes (received, sold from, written off, released).
 *
 * Routing key: stock.batch.synced  (stocks.events)
 * Consumer:    reports-ms HandleStockBatchSnapshot -> stock_batches (one document per batch_id).
 *
 * Added in 1.7.0, schema 1.
 */
final class StockBatchReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $batch_id,
        public int $business_id,
        public int $inventory_item_id,
        public int $location_id,
        public string $batch_number,
        public float $current_quantity,
        public float $initial_quantity = 0.0,
        public ?string $sku = null,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        public ?float $unit_cost = null,
        public ?float $selling_price = null,
        /** Y-m-d */
        public ?string $expiry_date = null,
        /** ISO-8601 */
        public ?string $received_at = null,
        /** e.g. held | released */
        public ?string $status = null,
        public ?string $source = null,
        /** ISO-8601, when the batch last changed. */
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'stock.batch.synced';
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
        return 'stock_batch';
    }

    public function aggregateId(): string|int
    {
        return $this->batch_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
