<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Uzapoint\ReportingContracts\ReportData;

/**
 * The whole purchase order as it stands, republished after every change (created, sent,
 * accepted, rejected, cancelled, paid, received against).
 *
 * Routing key: procurement.purchase_order.synced  (procurement.events)
 * Consumer:    reports-ms HandleProcurementPurchaseOrder -> procurement_purchase_orders
 *              + procurement_snapshots (one document per entity_type + po_id).
 *
 * Schema 2 (1.7.0): the shape of procurement-ms SnapshotFactory::purchaseOrderSnapshot,
 * published on one key; the step that caused it is in `action`. Money is in the order's
 * currency; total_value_base is the same total in the business's base currency.
 */
final class PurchaseOrderReportData extends ReportData
{
    public const int SCHEMA_VERSION = 2;

    /**
     * @param list<PurchaseOrderItemReportData> $items
     */
    public function __construct(
        public int $po_id,
        public int $business_id,
        public string $status,
        public string $ref_no,
        public float $total_value,
        /** The step that triggered this snapshot (created, sent, accepted, state_changed, ...). */
        public string $action = 'state_changed',
        public ?int $supplier_id = null,
        public ?string $supplier_name = null,
        public ?int $location_id = null,
        public ?int $store_location_id = null,
        public int $items_count = 0,
        public ?string $currency = null,
        public ?float $exchange_rate = null,
        public ?float $subtotal_exclusive_tax = null,
        public ?float $total_tax = null,
        public ?float $total_discount = null,
        public ?float $total_value_base = null,
        /** ISO-8601 */
        public ?string $order_date = null,
        public ?string $expected_delivery_date = null,
        public int|string|null $created_by = null,
        public int|string|null $sent_by = null,
        public int|string|null $accepted_by = null,
        public int|string|null $rejected_by = null,
        public int|string|null $cancelled_by = null,
        public ?string $notes = null,
        #[DataCollectionOf(PurchaseOrderItemReportData::class)]
        public array $items = [],
        /** ISO-8601, when the snapshot was taken. */
        public ?string $timestamp = null,
        /** Fixed discriminator for the raw procurement_snapshots collection. */
        public string $entity_type = 'purchase_order',
    ) {}

    public function eventName(): string
    {
        return 'procurement.purchase_order.synced';
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
        return 'purchase_order';
    }

    public function aggregateId(): string|int
    {
        return $this->po_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->timestamp ?? $this->order_date);
    }
}
