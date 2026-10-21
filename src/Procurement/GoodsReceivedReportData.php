<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a goods received note.
 *
 * Routing key: procurement.goods_received.grn_created  (procurement.events)
 * Consumer:    reports-ms HandleProcurementGoodsReceived -> procurement_goods_received
 *              (upsert by grn_id) + raw procurement_snapshots.
 *
 * grn_id is a ULID string in procurement-ms, not an integer. Items are passed
 * through as arrays: the consumer stores them verbatim and only counts them.
 */
final class GoodsReceivedReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        public string $grn_id,
        public int $business_id,
        public int $po_id,
        public int $supplier_id,
        public float $total_quantity_accepted,
        public float $total_value_accepted,
        /** ISO-8601 */
        public string $created_at,
        public string $status = 'received',
        public float $total_quantity_rejected = 0.0,
        public ?string $grn_number = null,
        public ?string $invoice_number = null,
        public ?string $po_ref_no = null,
        public ?int $location_id = null,
        public ?int $store_location_id = null,
        public ?string $payment_method = null,
        public string $currency = 'KES',
        public int|string|null $received_by = null,
        public ?string $notes = null,
        public array $items = [],
        public string $entity_type = 'goods_received',
    ) {}

    public function eventName(): string
    {
        return 'procurement.goods_received.grn_created';
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
        return 'goods_received_note';
    }

    public function aggregateId(): string|int
    {
        return $this->grn_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->created_at);
    }

    public function actorId(): int|string|null
    {
        return $this->received_by;
    }
}
