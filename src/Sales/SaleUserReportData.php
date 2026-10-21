<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * A staff member attributed to a SaleReportData (commissions, rep reports).
 * The first entry is the fallback actor when sale.user_id is absent.
 */
final class SaleUserReportData extends Data
{
    public function __construct(
        public int|string $user_id,
        public ?string $user_name = null,
    ) {}
}
