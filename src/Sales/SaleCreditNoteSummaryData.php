<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * A credit note as it appears on its parent SaleReportData. Summary only; the
 * full credit note travels separately as CreditNoteReportData.
 */
final class SaleCreditNoteSummaryData extends Data
{
    public function __construct(
        public int|string $id,
        public string $credit_note_number,
        /** pending | approved | rejected */
        public string $status,
        /** full | partial */
        public string $type,
        public float $total_amount,
        public ?string $reason = null,
        /** ISO-8601 */
        public ?string $created_at = null,
    ) {
    }
}
