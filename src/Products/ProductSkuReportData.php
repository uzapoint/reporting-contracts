<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Products;

use Spatie\LaravelData\Data;

/**
 * A SKU inside ProductReportData. The product lake joins sales to products on `sku`;
 * the rest is what the catalogue, valuation and dashboard reports show per SKU.
 *
 * Since 1.7.0 it also carries the SKU's own name, cost, image, markup and map tag,
 * all optional, which products-ms already sends.
 */
final class ProductSkuReportData extends Data
{
    public function __construct(
        public string $sku,
        public float $price,
        public ?string $barcode = null,
        public ?string $name = null,
        public ?float $cost_price = null,
        public ?string $image_url = null,
        /** percentage | amount */
        public ?string $markup_type = null,
        public ?float $markup_value = null,
        public int|string|null $map_location_tag_id = null,
    ) {}
}
