<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * One tax charged on a SaleItemReportData line (VAT, excise, ...). Nested only.
 *
 * Added in 1.7.0. tax_amount is the line's share of line_tax_total for this tax, so the
 * amounts of a line's taxes add up to its line_tax_total.
 */
final class SaleItemTaxReportData extends Data
{
    public function __construct(
        public string $tax_code,
        /** Percent, e.g. 16.0 */
        public float $tax_rate,
        public float $tax_amount = 0.0,
    ) {
    }
}
