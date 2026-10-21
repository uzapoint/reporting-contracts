<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A single sale line was voided.
 *
 * Routing key: sales.item.voided  (sales.events)
 * Consumer:    reports-ms StoreVoidedItemSnapshot -> voided_item_snapshots,
 *              upsert keyed by (sale_item_id, voided_at). Field names mirror
 *              that collection's fillable list exactly.
 */
final class VoidedSaleItemReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $sale_item_id,
        public int|string $sale_id,
        public int $business_id,
        /** ISO-8601; part of the consumer's upsert key. */
        public string $voided_at,
        public string $product_code,
        public string $product_name,
        public float $qty_voided,
        public float $unit_price,
        public float $line_total,
        public ?string $product_sku = null,
        public int|string|null $product_sku_id = null,
        public ?float $unit_cost = null,
        public ?float $line_subtotal = null,
        public float $line_discount_total = 0.0,
        public float $line_tax_total = 0.0,
        public ?string $tax_code = null,
        public ?float $tax_rate = null,
        public ?string $void_reason = null,
        public int|string|null $voided_by = null,
        public int|string|null $terminal_id = null,
        public ?int $shift_id = null,
    ) {}

    public function eventName(): string
    {
        return 'sales.item.voided';
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
        return 'sale_item';
    }

    public function aggregateId(): string|int
    {
        return $this->sale_item_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->voided_at);
    }

    public function idempotencyKey(): ?string
    {
        return "sale_item:voided:{$this->sale_item_id}:{$this->voided_at}";
    }

    public function actorId(): int|string|null
    {
        return $this->voided_by;
    }
}
