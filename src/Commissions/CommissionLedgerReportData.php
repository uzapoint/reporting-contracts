<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Commissions;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of one commission ledger entry (earn, approve, reject, payout, clawback, adjust)
 * together with its earner's running totals.
 *
 * Routing key: commission.ledger.recorded  (business.events)
 * Consumer:    reports-ms HandleCommissionLedgerRecorded -> commission_ledger_snapshots (by ledger_id)
 *              and commission_summary_snapshots (by summary_id)
 *
 * Republished on EVERY change to the entry (status moves pending -> approved -> paid, a
 * clawback also rejects its pending original), so it carries the entry's complete current
 * state. updated_at is the version: the consumer ignores a snapshot older than the one held.
 *
 * Amount sign: a clawback is stored negative by business-ms; reports treat clawbacks as
 * negative whatever the sign. sale_receipt_number is the sales-ms sale_number.
 */
final class CommissionLedgerReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $ledger_id,
        public int $business_id,
        /** earn | approve | reject | payout | clawback | adjust */
        public string $type,
        /** pending | approved | rejected | paid */
        public string $status,
        public float $amount,
        /** ISO-8601 time the entry was created: the date reports filter on. */
        public string $created_at,
        /** ISO-8601 version of this snapshot. */
        public string $updated_at,
        public string $earner_type,
        public int|string $earner_id,
        public ?string $earner_name = null,
        public ?int $commission_program_id = null,
        public ?string $program_name = null,
        public ?int $commission_rule_id = null,
        public ?int $commission_summary_id = null,
        public float $balance_after = 0.0,
        public int|string|null $sale_id = null,
        public ?string $sale_receipt_number = null,
        public float $sale_total = 0.0,
        public ?string $rule_basis = null,
        public ?string $rule_rate_type = null,
        public float $rule_rate_value = 0.0,
        public ?string $matched_product_name = null,
        public ?string $matched_category_name = null,
        public ?string $description = null,
        public ?string $performed_by_name = null,
        public ?string $approved_by_name = null,
        public ?CommissionSummaryReportData $summary = null,
    ) {
    }

    public function eventName(): string
    {
        return 'commission.ledger.recorded';
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
        return 'commission_ledger';
    }

    public function aggregateId(): string|int
    {
        return $this->ledger_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
