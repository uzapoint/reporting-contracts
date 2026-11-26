<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use DateTimeInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A stock take (physical count) with every counted line, republished as it moves
 * (started, counting, completed, reconciled).
 *
 * Routing key: stock.take.synced  (stocks.events)
 * Consumer:    reports-ms HandleStockTakeSnapshot -> stock_takes (one document per stock_take_id).
 *
 * Added in 1.7.0, schema 1.
 */
final class StockTakeReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<StockTakeItemReportData> $items
     */
    public function __construct(
        public int $stock_take_id,
        public int $business_id,
        public int $location_id,
        public string $status,
        public ?string $started_at = null,
        public ?string $completed_at = null,
        public int|string|null $started_by_user_id = null,
        public int|string|null $reconciled_by_user_id = null,
        public ?string $notes = null,
        #[DataCollectionOf(StockTakeItemReportData::class)]
        public array $items = [],
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'stock.take.synced';
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
        return 'stock_take';
    }

    public function aggregateId(): string|int
    {
        return $this->stock_take_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at ?? $this->completed_at ?? $this->started_at);
    }

    public function actorId(): int|string|null
    {
        return $this->reconciled_by_user_id ?? $this->started_by_user_id;
    }
}
