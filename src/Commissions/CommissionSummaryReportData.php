<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Commissions;

use Spatie\LaravelData\Data;

/**
 * An earner's running commission totals within one program. Nested inside
 * CommissionLedgerReportData; never published on its own.
 *
 * Consumer: reports-ms HandleCommissionLedgerRecorded -> commission_summary_snapshots,
 * upserted by summary_id.
 */
final class CommissionSummaryReportData extends Data
{
    public function __construct(
        public int $summary_id,
        public int $business_id,
        public string $earner_type,
        public int|string $earner_id,
        public float $total_earned = 0.0,
        public float $total_approved = 0.0,
        public float $total_paid = 0.0,
        public float $total_rejected = 0.0,
        public float $total_clawed_back = 0.0,
        public float $pending_balance = 0.0,
        public int $transactions_count = 0,
        public ?int $commission_program_id = null,
        public ?string $program_name = null,
        public ?string $earner_name = null,
        /** ISO-8601; reports-ms ignores a summary older than the one it holds. */
        public ?string $updated_at = null,
    ) {
    }
}
