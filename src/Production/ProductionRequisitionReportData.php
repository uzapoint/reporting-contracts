<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Production;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a production requisition, republished at each stage.
 *
 * Routing keys: production.requisition.synced | production.finished_goods.produced
 *               | production.raw_materials.consumed  (production.events)
 * Consumer:     reports-ms HandleProductionRequisitionSnapshot -> production_requisition_snapshots,
 *               partial upsert by requisition_id; also appends the raw payload to production_snapshots.
 *
 * finished_goods, raw_materials and runs are passed through as arrays: the
 * consumer stores them verbatim and no reader depends on their inner shape yet.
 * Type them in a later schema version once a report does.
 */
final class ProductionRequisitionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<array<string, mixed>>|null $finished_goods
     * @param list<array<string, mixed>>|null $raw_materials
     * @param list<array<string, mixed>>|null $runs
     */
    public function __construct(
        public int $requisition_id,
        public int $business_id,
        /** synced | finished_goods.produced | raw_materials.consumed — selects the routing key */
        public string $stage,
        public string $status,
        /** ISO-8601 */
        public string $created_at,
        public ?string $ref_no = null,
        public ?int $store_from_id = null,
        public ?string $store_from_name = null,
        public ?int $store_to_id = null,
        public ?string $store_to_name = null,
        public ?string $note = null,
        public ?string $source = null,
        public string $production_mode = 'standard',
        /** amount | percentage */
        public string $overhead_type = 'amount',
        public float $overhead_value = 0.0,
        public float $overhead_amount = 0.0,
        public float $total_raw_material_cost = 0.0,
        public float $total_labor_cost = 0.0,
        public float $total_production_cost = 0.0,
        public ?int $created_by_user_id = null,
        public ?string $created_by_name = null,
        public ?int $approved_by_user_id = null,
        public ?string $approved_by_name = null,
        public ?string $approved_at = null,
        public ?string $production_at = null,
        public ?string $dispatched_at = null,
        public ?array $finished_goods = null,
        public ?array $raw_materials = null,
        public ?array $runs = null,
    ) {}

    public function eventName(): string
    {
        return match ($this->stage) {
            'synced' => 'production.requisition.synced',
            'finished_goods.produced' => 'production.finished_goods.produced',
            'raw_materials.consumed' => 'production.raw_materials.consumed',
            default => "production.requisition.{$this->stage}",
        };
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
        return 'production_requisition';
    }

    public function aggregateId(): string|int
    {
        return $this->requisition_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp(match ($this->stage) {
            'finished_goods.produced' => $this->production_at,
            default => null,
        });
    }

    public function actorId(): int|string|null
    {
        return $this->approved_by_user_id ?? $this->created_by_user_id;
    }
}
