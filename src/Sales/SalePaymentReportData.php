<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Sales/SalePaymentReportData.php

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * One payment line of a SaleReportData. The last payment_method in the list
 * becomes the order's headline payment method in the dashboard.
 */
final class SalePaymentReportData extends Data
{
    public function __construct(
        public string $payment_method,
        public float $amount,
        /** ISO-8601 */
        public ?string $payment_date = null,
        /** Tendered with a value voucher: settles a liability instead of bringing in money. */
        public bool $is_voucher = false,
        /** The voucher hold reference or other tender reference. */
        public ?string $reference = null,
    ) {
    }
}
