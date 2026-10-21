<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * An immutable stock movement.
 *
 * Routing key: stock.transaction.recorded  (stocks.events)
 * Consumer:    reports-ms ProcessStockTransaction -> stock_transactions (append),
 *              stock_daily_balances (opening/closing per sku/location/day),
 *              inventory_snapshots (latest quantity, cost_price, reorder_level).
 *
 * The consumer appends rather than upserts, so the deterministic idempotency
 * key is what prevents a redelivered message from double-counting a movement.
 * quantity_before/after are the running balance for the SKU at the location.
 */
final class StockTransactionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $transaction_id,
        public int $business_id,
        public int $location_id,
        public string $sku,
        /** in | out | transfer | take | adjustment ... as recorded by stocks-ms */
        public string $type,
        public float $quantity_change,
        public float $quantity_before,
        public float $quantity_after,
        /** ISO-8601; buckets the movement into a daily balance. */
        public string $created_at,
        public int $inventory_item_id = 0,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        public int|string|null $reference_id = null,
        public ?string $reference_type = null,
        public ?string $batch_number = null,
        public ?string $serial_number = null,
        public int|string|null $user_id = null,
        public ?float $reorder_level = null,
        public ?float $cost_price = null,
    ) {}

    public function eventName(): string
    {
        return 'stock.transaction.recorded';
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
        return 'stock_transaction';
    }

    public function aggregateId(): string|int
    {
        return $this->transaction_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->created_at);
    }

    public function idempotencyKey(): ?string
    {
        return "stock:transaction:{$this->transaction_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
