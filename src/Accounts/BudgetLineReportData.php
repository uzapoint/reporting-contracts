<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use Spatie\LaravelData\Data;

/** A line of BudgetReportData: one account's amount for one fiscal period. Nested only. Added in 1.7.0. */
final class BudgetLineReportData extends Data
{
    public function __construct(
        public int $coa_account_id,
        public float $budgeted_amount,
        public ?string $account_code = null,
        public ?string $account_name = null,
        public ?string $account_type = null,
        public int|string|null $fiscal_period_id = null,
        public ?string $period_name = null,
        /** Y-m-d */
        public ?string $period_start = null,
        public ?string $period_end = null,
    ) {}
}
