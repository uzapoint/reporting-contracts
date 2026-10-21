<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

final class CreditNoteItemReportData extends Data
{
    public function __construct(
        public string $sku,
        public float $quantity,
        public int|string|null $credit_note_item_id = null,
        public int|string|null $product_sku_id = null,
        /** none | batch | serial */
        public string $tracking_method = 'none',
        /** When null the consumer resolves it from inventory. */
        public ?float $unit_cost = null,
    ) {}
}
