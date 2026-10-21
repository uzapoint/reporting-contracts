<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use Spatie\LaravelData\Data;

final class PurchaseOrderItemReportData extends Data
{
    public function __construct(
        public float $quantity_ordered,
        public float $unit_price,
        public float $total_line_value,
        public ?int $product_id = null,
        public int|string|null $product_sku_id = null,
        public ?string $sku = null,
        public string $product_name = '',
        public float $tax_rate = 0.0,
    ) {}
}
