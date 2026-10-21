<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a purchase order, republished on every status transition.
 *
 * Routing keys: procurement.purchase_order.{created|sent|accepted|rejected|cancelled}  (procurement.events)
 * Consumer:     reports-ms HandleProcurementPurchaseOrder -> procurement_purchase_orders
 *               (partial upsert by po_id) + raw procurement_snapshots (upsert by entity_type + po_id).
 *
 * Field names follow procurement-ms SnapshotFactory (po_id, ref_no, total_value),
 * which is the shape the consumer reads first. Always carry items and
 * total_value: the consumer never zeroes stored money when they are absent,
 * but a thin publish leaves the lake stale.
 */
final class PurchaseOrderReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<PurchaseOrderItemReportData> $items
     */
    public function __construct(
        public int $po_id,
        public int $business_id,
        /** created | sent | accepted | rejected | cancelled */
        public string $action,
        public string $status,
        public string $ref_no,
        public int $supplier_id,
        public float $total_value,
        /** ISO-8601 */
        public string $order_date,
        public string $supplier_name = '',
        public ?int $location_id = null,
        public ?int $store_location_id = null,
        public ?float $total_tax = null,
        public ?float $total_discount = null,
        public string $currency = 'KES',
        public int|string|null $created_by = null,
        public int|string|null $accepted_by = null,
        public int|string|null $sent_by = null,
        public int|string|null $cancelled_by = null,
        public ?string $sent_at = null,
        public ?string $accepted_at = null,
        public ?string $cancelled_at = null,
        public ?string $expected_delivery_date = null,
        public ?string $notes = null,
        #[DataCollectionOf(PurchaseOrderItemReportData::class)]
        public array $items = [],
        /** Fixed discriminator for the raw procurement_snapshots collection. */
        public string $entity_type = 'purchase_order',
    ) {}

    public function eventName(): string
    {
        return "procurement.purchase_order.{$this->action}";
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
        return $this->timestamp(match ($this->action) {
            'sent' => $this->sent_at,
            'accepted' => $this->accepted_at,
            'cancelled' => $this->cancelled_at,
            default => $this->order_date,
        });
    }

    public function actorId(): int|string|null
    {
        return match ($this->action) {
            'sent' => $this->sent_by,
            'accepted' => $this->accepted_by,
            'cancelled' => $this->cancelled_by,
            default => $this->created_by,
        };
    }
}
