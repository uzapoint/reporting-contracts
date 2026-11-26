<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use DateTimeInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A transfer between two stores as it stands, republished at each step (created,
 * approved, shipped, received).
 *
 * Routing key: stock.transfer.synced  (stocks.events)
 * Consumer:    reports-ms HandleStockTransferSnapshot -> stock_transfers (one document per transfer_id).
 *
 * Added in 1.7.0, schema 1.
 */
final class StockTransferReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<StockTransferItemReportData> $items
     */
    public function __construct(
        public int $transfer_id,
        public int $business_id,
        public int $from_location_id,
        public int $to_location_id,
        /** created | approved | shipped | received | ... */
        public string $status,
        /** ISO-8601 */
        public string $created_at,
        public ?string $approved_at = null,
        public ?string $shipped_at = null,
        public ?string $received_at = null,
        public ?string $notes = null,
        #[DataCollectionOf(StockTransferItemReportData::class)]
        public array $items = [],
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'stock.transfer.synced';
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
        return 'stock_transfer';
    }

    public function aggregateId(): string|int
    {
        return $this->transfer_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at ?? $this->created_at);
    }
}
