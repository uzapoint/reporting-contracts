<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Sales/SalePromoData.php

namespace Uzapoint\ReportingContracts\Sales;

use Spatie\LaravelData\Data;

/**
 * The promo code applied to a SaleReportData. Nested only.
 *
 * ref is the sale_ref the till redeemed the code under, so it joins this sale to the
 * PromoRedemptionReportData event even though the redemption happens before the sale exists.
 * discount is part of the sale's order_discount_total.
 */
final class SalePromoData extends Data
{
    public function __construct(
        public string $ref,
        public string $code,
        public float $discount,
        public ?string $name = null,
        public int|string|null $promo_id = null,
        public bool $stackable = false,
    ) {
    }
}
