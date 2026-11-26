<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A line rejected at receiving, as it stands, republished whenever it changes
 * (pending_return, returned, disposed, accepted_after_review).
 *
 * Routing key: procurement.rejected_item.synced  (procurement.events)
 * Consumer:    reports-ms HandleProcurementRejectedItem -> procurement_snapshots
 *              (one document per entity_type + rejected_item_id).
 *
 * Added in 1.7.0, schema 1: the shape of procurement-ms SnapshotFactory::rejectedItemSnapshot.
 */
final class RejectedItemReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $rejected_item_id,
        public int $business_id,
        public float $quantity_rejected,
        public string $action = 'item_rejected',
        public ?string $status = null,
        public ?int $supplier_id = null,
        /** The GRN's uid. */
        public ?string $grn_id = null,
        public ?string $grn_number = null,
        public ?int $po_id = null,
        public ?int $product_id = null,
        public int|string|null $category_id = null,
        public int|string|null $product_sku_id = null,
        public ?string $sku = null,
        public ?string $supplier_sku = null,
        public ?string $product_name = null,
        public string $tracking_method = 'none',
        public ?string $rejection_reason = null,
        public ?string $notes = null,
        public int|string|null $recorded_by = null,
        /** ISO-8601, when the snapshot was taken. */
        public ?string $timestamp = null,
        public string $entity_type = 'rejected_item',
    ) {}

    public function eventName(): string
    {
        return 'procurement.rejected_item.synced';
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
        return 'rejected_item';
    }

    public function aggregateId(): string|int
    {
        return $this->rejected_item_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->timestamp);
    }

    public function actorId(): int|string|null
    {
        return $this->recorded_by;
    }
}
