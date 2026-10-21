<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Products;

use Spatie\LaravelData\Data;

/**
 * A SKU inside ProductReportData. The product lake stores exactly
 * {sku, price, barcode} per SKU and joins sales to products on `sku`.
 */
final class ProductSkuReportData extends Data
{
    public function __construct(
        public string $sku,
        public float $price,
        public ?string $barcode = null,
    ) {}
}
