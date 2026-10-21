<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a purchase requisition on each transition.
 *
 * Routing keys: procurement.requisition.{created|approved|rejected}  (procurement.events)
 * Consumer:     reports-ms HandleProcurementRequisition -> procurement_requisitions
 *               (partial upsert by requisition_id) + raw procurement_snapshots.
 */
final class RequisitionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $requisition_id,
        public int $business_id,
        /** created | approved | rejected */
        public string $action,
        public string $status,
        public string $ref_no,
        public float $total_value,
        /** ISO-8601 */
        public string $created_at,
        public string $title = 'Requisition',
        public int|string|null $requested_by = null,
        public int|string|null $approved_by = null,
        /** low | medium | high */
        public string $priority = 'medium',
        public ?string $expected_delivery_date = null,
        public ?string $notes = null,
        public ?string $rejection_reason = null,
        public string $entity_type = 'requisition',
    ) {}

    public function eventName(): string
    {
        return "procurement.requisition.{$this->action}";
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
        return 'requisition';
    }

    public function aggregateId(): string|int
    {
        return $this->requisition_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->created_at);
    }

    public function actorId(): int|string|null
    {
        return $this->action === 'created' ? $this->requested_by : $this->approved_by;
    }
}
