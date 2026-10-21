<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a customer receivable at creation.
 *
 * Routing key: accounts.receivable.snapshot  (accounts.events)
 * Consumer:    reports-ms HandleReceivableSnapshot -> receivable_snapshots, partial upsert by receivable_id.
 */
final class ReceivableReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $receivable_id,
        public int $business_id,
        public float $total_amount,
        public float $amount_paid = 0.0,
        public ?float $amount_due = null,
        public string $currency = 'KES',
        public ?string $status = null,
        public bool $is_void = false,
        public ?string $date_due = null,
        public int|string|null $sale_id = null,
        public ?string $sale_number = null,
        public ?string $sale_type = null,
        public ?string $sale_date = null,
        public int|string|null $invoice_id = null,
        public ?string $invoice_number = null,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public float $sub_total = 0.0,
        public float $discount_total = 0.0,
        public float $tax_total = 0.0,
        public int|string|null $user_id = null,
        public ?string $user_name = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.receivable.snapshot';
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
        return 'receivable';
    }

    public function aggregateId(): string|int
    {
        return $this->receivable_id;
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
