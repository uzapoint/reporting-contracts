<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * A line on an invoice or quotation. Nested only.
 *
 * Derived from what the reports-ms financial/sales readers actually consume
 * from invoice/quotation items: sku, product_name, quantity, unit_price,
 * unit_cost, line_total, tax_amount, total_discount, category_name.
 */
final class DocumentItemReportData extends Data
{
    public function __construct(
        public string $sku,
        public string $product_name,
        public float $quantity,
        public float $unit_price,
        public float $line_total,
        public int|string|null $product_sku_id = null,
        /** When null the consumer resolves it from inventory at ingestion time. */
        public ?float $unit_cost = null,
        public float $tax_amount = 0.0,
        public float $total_discount = 0.0,
        /** When null the consumer resolves it from the product lake. */
        public ?string $category_name = null,
    ) {}
}
