<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * One line of a SaleReportData. Nested only; never published on its own.
 *
 * Consumer reads: product_sku_id, product_code, product_name, category_id,
 * category_name, unit_cost, quantity, unit_price, line_subtotal, line_total,
 * line_tax_total, line_discount_total, batch_number, serial_number,
 * tracking_method, expiry_date, is_free_good, is_free_with_expense, free_value,
 * expense_amount, order_discount_total. Legacy aliases it still accepts (sku, qty)
 * are not part of the contract.
 */
final class SaleItemReportData extends Data
{
    public function __construct(
        public string $product_code,
        public string $product_name,
        public float $quantity,
        public float $unit_price,
        public float $line_total,
        public int|string|null $product_sku_id = null,
        public ?int $category_id = null,
        /** When null the consumer resolves it from the product lake. */
        public ?string $category_name = null,
        /** Cost per unit at sale time. When null the consumer resolves it from inventory. */
        public ?float $unit_cost = null,
        public ?float $line_subtotal = null,
        public float $line_tax_total = 0.0,
        public float $line_discount_total = 0.0,
        public ?string $batch_number = null,
        public ?string $serial_number = null,
        /** none | batch | serial */
        public string $tracking_method = 'none',
        public ?string $expiry_date = null,
        /** Given away free (line_total 0). */
        public bool $is_free_good = false,
        /** Free good consumed by the business: its cost is booked as an expense, not cost of sales. */
        public bool $is_free_with_expense = false,
        /** What the line would have sold for. */
        public float $free_value = 0.0,
        /** Cost booked as an expense for a free-with-expense line. */
        public ?float $expense_amount = null,
        /** This line's share of the whole-order discount (tax-exclusive); part of line_discount_total. */
        public float $order_discount_total = 0.0,
    ) {
    }
}
