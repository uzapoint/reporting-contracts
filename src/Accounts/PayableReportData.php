<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a supplier payable at creation.
 *
 * Routing key: accounts.payable.snapshot  (accounts.events)
 * Consumer:    reports-ms HandlePayableSnapshot -> payable_snapshots, partial upsert by payable_id
 *              (null fields are skipped, never written).
 */
final class PayableReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $payable_id,
        public int $business_id,
        public float $total_amount,
        public float $amount_paid = 0.0,
        public string $currency = 'KES',
        public ?string $status = null,
        public bool $is_void = false,
        public ?string $date_due = null,
        public int|string|null $grn_id = null,
        public int|string|null $po_id = null,
        public ?string $po_number = null,
        public int|string|null $vendor_id = null,
        public ?string $vendor_name = null,
        public int|string|null $created_by_user_id = null,
        public ?string $created_by_user_name = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.payable.snapshot';
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
        return 'payable';
    }

    public function aggregateId(): string|int
    {
        return $this->payable_id;
    }

    public function actorId(): int|string|null
    {
        return $this->created_by_user_id;
    }
}
