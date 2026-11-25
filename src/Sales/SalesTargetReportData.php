<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Sales/SalesTargetReportData.php

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a sales target: who it is for, what it measures, the goal and how much of it
 * has been reached.
 *
 * Routing key: sales_target.snapshot.synced  (sales.events)
 * Consumer:    reports-ms HandleSalesTargetSnapshot -> sales_target_snapshots, upsert by target_id
 *
 * Republished whenever the target changes (created, edited, credited by a sale, closed as
 * achieved or missed, deleted), so it always carries the complete current state. A deleted
 * target is sent once more with is_deleted true and leaves the reports.
 */
final class SalesTargetReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public string $target_id,
        public int $business_id,
        /** The staff member the target is set for. */
        public int|string $user_id,
        /** overall | category | product */
        public string $type,
        /** amount | quantity */
        public string $metric,
        public float $target_value,
        public float $achieved_value,
        /** active | achieved | missed */
        public string $status,
        /** ISO-8601 date */
        public string $start_date,
        /** ISO-8601 date */
        public string $end_date,
        /** daily | weekly | monthly | quarterly | yearly | custom */
        public ?string $period_type = null,
        public int|string|null $category_id = null,
        public int|string|null $product_id = null,
        public ?string $terminal_code = null,
        /** percentage | fixed */
        public ?string $bonus_type = null,
        public float $bonus_value = 0.0,
        public string $currency = 'KES',
        public int|string|null $created_by = null,
        /** ISO-8601 of the change this snapshot reports. */
        public ?string $updated_at = null,
        public bool $is_deleted = false,
    ) {
    }

    public function eventName(): string
    {
        return 'sales_target.snapshot.synced';
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
        return 'sales_target';
    }

    public function aggregateId(): string|int
    {
        return $this->target_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }

    public function actorId(): int|string|null
    {
        return $this->created_by;
    }
}
