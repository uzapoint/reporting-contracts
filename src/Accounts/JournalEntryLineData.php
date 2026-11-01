<?php
// uzapoint/reporting-contracts /src/Accounts/JournalEntryLineData.php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use Spatie\LaravelData\Data;

/**
 * One posted ledger line. Nested only (inside JournalEntryPostedData).
 *
 * Self-contained on purpose: account code/name/type, the tax snapshot and every reporting dimension
 * travel with the line so reports-ms never calls back to accounts-ms. Amounts are base-currency;
 * the original-currency figures are carried for the general ledger drill-down only.
 */
final class JournalEntryLineData extends Data
{
    public function __construct(
        public int $line_id,
        public int $coa_account_id,
        public string $account_code,
        public string $account_name,
        /** asset | liability | equity | revenue | expense */
        public string $account_type,
        /** debit | credit — the account's normal side (contra accounts differ from their type). */
        public string $normal_balance,
        public bool $is_contra = false,
        public float $debit = 0.0,
        public float $credit = 0.0,
        public ?string $currency = null,
        public float $exchange_rate = 1.0,
        public ?float $original_debit = null,
        public ?float $original_credit = null,
        public ?string $description = null,
        public int|string|null $tax_code_id = null,
        public ?string $tax_name = null,
        /** Fraction (0.16), frozen at posting time. */
        public ?float $tax_rate = null,
        /** Negative on a reversal line. */
        public float $tax_amount = 0.0,
        public int|string|null $banking_account_id = null,
        public int|string|null $category_id = null,
        public ?string $category_name = null,
        public int|string|null $brand_id = null,
        public ?string $brand_name = null,
        public int|string|null $product_id = null,
        public ?string $product_name = null,
        public int|string|null $branch_id = null,
        public int|string|null $terminal_id = null,
    ) {
    }
}
