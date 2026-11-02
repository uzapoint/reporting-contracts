<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Vouchers/PromoRedemptionReportData.php

namespace Uzapoint\ReportingContracts\Vouchers;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A promo code was redeemed against a sale, or that redemption was reversed.
 *
 * Routing key: promo.redemption.recorded  (business.events)
 * Consumer:    reports-ms -> promo_redemptions, upsert by redemption_id; a reversal flips the
 *              row to reversed rather than adding one, so net discount is never double-counted.
 *
 * The till redeems before the sale exists, so sale_ref (the uuid it generated) is the join to
 * SaleReportData.promo.ref; sale_id and sale_number are filled when business-ms learns them
 * and stay null until then.
 */
final class PromoRedemptionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $redemption_id,
        public int|string $promo_id,
        public int $business_id,
        /** redeemed | reversed */
        public string $action,
        public string $code,
        public float $discount,
        public string $sale_ref,
        /** ISO-8601 of the transition */
        public string $occurred_at,
        public ?string $name = null,
        /** order | category | product */
        public ?string $applies_to = null,
        public bool $stackable = false,
        /** Cart subtotal the discount was computed on. */
        public ?float $cart_subtotal = null,
        public int|string|null $sale_id = null,
        public ?string $sale_number = null,
        public int|string|null $customer_id = null,
        public ?int $branch_id = null,
        public int|string|null $terminal_id = null,
        public int|string|null $user_id = null,
    ) {
    }

    public function eventName(): string
    {
        return 'promo.redemption.recorded';
    }

    public function kind(): ReportKind
    {
        return ReportKind::Event;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    public function aggregateType(): string
    {
        return 'promo_redemption';
    }

    public function aggregateId(): string|int
    {
        return $this->redemption_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->occurred_at);
    }

    public function idempotencyKey(): ?string
    {
        return "promo:{$this->action}:{$this->redemption_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
