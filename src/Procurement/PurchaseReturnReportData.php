<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * The whole purchase return as it stands, republished after every change (created,
 * submitted, approved, rejected, stock or payable outcome).
 *
 * Routing key: procurement.purchase_return.synced  (procurement.events)
 * Consumer:    reports-ms HandleProcurementPurchaseReturn -> procurement_snapshots
 *              (one document per entity_type + return_id).
 *
 * Added in 1.7.0, schema 1: the shape of procurement-ms SnapshotFactory::purchaseReturnSnapshot.
 */
final class PurchaseReturnReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<array<string, mixed>> $items product_id, category_id, sku, quantity_returned,
     *        unit_price, reason, line_total
     */
    public function __construct(
        public int $return_id,
        public int $business_id,
        public string $status,
        public string $action = 'state_changed',
        public ?string $return_number = null,
        public ?int $supplier_id = null,
        public ?int $po_id = null,
        /** The GRN's uid. */
        public ?string $grn_id = null,
        public int|string|null $created_by = null,
        public int|string|null $approved_by = null,
        public float $total_value = 0.0,
        public array $items = [],
        /** ISO-8601, when the snapshot was taken. */
        public ?string $timestamp = null,
        public string $entity_type = 'purchase_return',
    ) {}

    public function eventName(): string
    {
        return 'procurement.purchase_return.synced';
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
        return 'purchase_return';
    }

    public function aggregateId(): string|int
    {
        return $this->return_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->timestamp);
    }

    public function actorId(): int|string|null
    {
        return $this->approved_by ?? $this->created_by;
    }
}
