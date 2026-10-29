<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * Delivery details of a SaleReportData, as the till captured them. Nested only.
 *
 * fee is what was charged; list_fee is what the delivery zone normally costs, so a custom or
 * waived fee shows up as list_fee - fee. A free delivery has fee 0 and is_free true.
 */
final class SaleDeliveryData extends Data
{
    public function __construct(
        public float $fee = 0.0,
        public ?float $list_fee = null,
        public bool $is_free = false,
        public bool $is_custom_fee = false,
        public ?string $location = null,
        public ?string $zone_code = null,
        public int|string|null $delivery_cost_id = null,
        public ?string $currency = null,
        public ?int $estimated_days = null,
        public ?string $address = null,
        public ?string $phone = null,
    ) {
    }
}
