<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * The whole purchase requisition as it stands, republished after every change (created,
 * approved, rejected, approval reverted).
 *
 * Routing key: procurement.requisition.synced  (procurement.events)
 * Consumer:    reports-ms HandleProcurementRequisition -> procurement_requisitions
 *              + procurement_snapshots (one document per entity_type + requisition_id).
 *
 * Schema 2 (1.7.0): the shape of procurement-ms SnapshotFactory::requisitionSnapshot,
 * published on one key. Values are in the business's base currency.
 */
final class RequisitionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 2;

    /**
     * @param list<array<string, mixed>> $items purchase_requisition_item_id, product_id,
     *        category_id, product_sku_id, sku, product_name, quantity, estimated_unit_price,
     *        estimated_total
     */
    public function __construct(
        public int $requisition_id,
        public int $business_id,
        public string $status,
        public string $ref_no,
        public float $total_value = 0.0,
        public string $action = 'state_changed',
        public ?int $supplier_id = null,
        public ?string $supplier_name = null,
        public int $items_count = 0,
        public int|string|null $created_by = null,
        public int|string|null $approved_by = null,
        public ?string $rejection_reason = null,
        public array $items = [],
        /** ISO-8601, when the snapshot was taken. */
        public ?string $timestamp = null,
        public string $entity_type = 'purchase_requisition',
    ) {}

    public function eventName(): string
    {
        return 'procurement.requisition.synced';
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
        return 'purchase_requisition';
    }

    public function aggregateId(): string|int
    {
        return $this->requisition_id;
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
