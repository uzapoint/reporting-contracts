<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Stocks;

use Spatie\LaravelData\Data;

/** A line of StockTransferReportData. Nested only. Added in 1.7.0. */
final class StockTransferItemReportData extends Data
{
    public function __construct(
        public int $inventory_item_id,
        public float $quantity_requested,
        public float $quantity_shipped = 0.0,
        public float $quantity_received = 0.0,
        public ?string $sku = null,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        public ?float $unit_cost = null,
    ) {}
}
