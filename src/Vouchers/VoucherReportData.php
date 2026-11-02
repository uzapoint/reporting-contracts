<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Vouchers/VoucherReportData.php

namespace Uzapoint\ReportingContracts\Vouchers;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a value voucher: a prepaid balance the business owes the holder (liability).
 *
 * Routing key: voucher.snapshot.synced  (business.events)
 * Consumer:    reports-ms -> voucher_snapshots, upsert by voucher_id
 *
 * Republished on every state change (issued, redeemed, expired, voided), so it carries the
 * voucher's complete current state. The full code is never reported; code_hint is its last
 * characters, enough to recognise a voucher on a report and useless for redeeming one.
 *
 * Open liability is the sum of balance over active and partially_redeemed vouchers.
 */
final class VoucherReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $voucher_id,
        public int $business_id,
        /** active | partially_redeemed | redeemed | expired | voided */
        public string $status,
        /** Value the voucher was issued with. */
        public float $face_value,
        /** Remaining redeemable value, holds excluded. */
        public float $balance,
        /** ISO-8601 */
        public string $issued_at,
        public ?string $code_hint = null,
        public string $currency = 'KES',
        public float $redeemed_total = 0.0,
        /** Reserved by tills but not yet redeemed. Informational. */
        public float $held_amount = 0.0,
        /** Value lapsed through expiry, 0 until the voucher expires with a balance. */
        public float $expired_amount = 0.0,
        /** ISO-8601 */
        public ?string $expires_at = null,
        /** ISO-8601 of the latest transaction; issued_at when there is none. */
        public ?string $last_activity_at = null,
        /** sale | refund | bulk | manual | change | ecommerce */
        public ?string $channel = null,
        /** What the buyer paid, which can differ from face_value on a bonus voucher. */
        public ?float $amount_received = null,
        public ?string $payment_method = null,
        public ?int $branch_id = null,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public int|string|null $source_sale_id = null,
        public ?string $source_sale_number = null,
        public int|string|null $issued_by = null,
    ) {
    }

    public function eventName(): string
    {
        return 'voucher.snapshot.synced';
    }

    public function kind(): ReportKind
    {
        return ReportKind::Snapshot;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    public function aggregateType(): string
    {
        return 'voucher';
    }

    public function aggregateId(): string|int
    {
        return $this->voucher_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->last_activity_at ?? $this->issued_at);
    }

    public function actorId(): int|string|null
    {
        return $this->issued_by;
    }
}
