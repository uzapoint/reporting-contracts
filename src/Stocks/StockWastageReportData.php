<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Stock written off (damaged, expired, theft, ...) with its reason and the cost lost.
 *
 * Routing key: stock.wastage.recorded  (stocks.events)
 * Consumer:    reports-ms HandleStockWastageRecorded -> stock_wastages (one document per wastage_id).
 *
 * Added in 1.7.0, schema 1.
 */
final class StockWastageReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $wastage_id,
        public int $business_id,
        public int $location_id,
        public int $inventory_item_id,
        public float $quantity,
        /** ISO-8601 */
        public string $recorded_at,
        public ?string $sku = null,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        public ?string $location_name = null,
        /** Cost per unit written off. */
        public ?float $unit_cost = null,
        public ?string $reason = null,
        public int|string|null $reason_id = null,
        /** manual | expiry | qc | ... */
        public ?string $source = null,
        public ?string $batch_number = null,
        public ?string $serial_number = null,
        public int|string|null $recorded_by_user_id = null,
        public ?string $recorded_by_user_name = null,
        public ?string $notes = null,
    ) {}

    public function eventName(): string
    {
        return 'stock.wastage.recorded';
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
        return 'stock_wastage';
    }

    public function aggregateId(): string|int
    {
        return $this->wastage_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->recorded_at);
    }

    public function idempotencyKey(): ?string
    {
        return "stock:wastage:{$this->wastage_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->recorded_by_user_id;
    }
}
