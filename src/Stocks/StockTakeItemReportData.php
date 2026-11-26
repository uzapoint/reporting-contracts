<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use Spatie\LaravelData\Data;

/** A counted line of StockTakeReportData. Nested only. Added in 1.7.0. */
final class StockTakeItemReportData extends Data
{
    public function __construct(
        public int $inventory_item_id,
        public float $expected_quantity,
        /** Null until counted. */
        public ?float $counted_quantity = null,
        /** counted - expected; null until counted. */
        public ?float $difference = null,
        public bool $is_reconciled = false,
        public ?string $sku = null,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        public ?float $unit_cost = null,
    ) {}
}
