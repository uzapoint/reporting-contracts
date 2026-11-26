<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * The whole goods receipt note as it stands, republished after every change (received,
 * stock synced or failed, payable created, reverted).
 *
 * Routing key: procurement.goods_received.synced  (procurement.events)
 * Consumer:    reports-ms HandleProcurementGoodsReceived -> procurement_goods_received
 *              + procurement_snapshots (one document per entity_type + grn_id).
 *
 * Schema 2 (1.7.0): the shape of procurement-ms SnapshotFactory::goodsReceiptSnapshot,
 * published on one key. Not to be confused with procurement.goods_received.items_received,
 * the per-line stock-in message stocks-ms acts on.
 */
final class GoodsReceivedReportData extends ReportData
{
    public const int SCHEMA_VERSION = 2;

    /**
     * @param list<array<string, mixed>> $items goods_receipt_item_id, product_id, category_id,
     *        product_sku_id, sku, product_name, quantity_ordered, quantity_accepted,
     *        quantity_rejected, rejection_reason, unit_price, line_total, batches[], serials[]
     */
    public function __construct(
        /** The GRN's uid. */
        public string $grn_id,
        public int $business_id,
        public int $po_id,
        public string $action = 'state_changed',
        /** The stock sync state: not_synced | pending | synced | failed | ... */
        public string $status = 'not_synced',
        public ?int $supplier_id = null,
        public ?string $grn_number = null,
        public ?string $invoice_number = null,
        public ?string $po_ref_no = null,
        public ?int $location_id = null,
        public ?int $store_location_id = null,
        public ?string $payment_method = null,
        public int|string|null $received_by = null,
        public float $total_quantity_ordered = 0.0,
        public float $total_quantity_accepted = 0.0,
        public float $total_quantity_rejected = 0.0,
        public ?float $expected_total = null,
        public ?float $total_value_accepted = null,
        public array $items = [],
        /** ISO-8601, when the snapshot was taken. */
        public ?string $timestamp = null,
        public string $entity_type = 'goods_receipt',
    ) {}

    public function eventName(): string
    {
        return 'procurement.goods_received.synced';
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
        return 'goods_receipt';
    }

    public function aggregateId(): string|int
    {
        return $this->grn_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->timestamp);
    }

    public function actorId(): int|string|null
    {
        return $this->received_by;
    }
}
