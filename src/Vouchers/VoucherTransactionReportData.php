<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — src/Vouchers/VoucherTransactionReportData.php

namespace Uzapoint\ReportingContracts\Vouchers;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * One movement on a value voucher's balance. Only movements that change the liability are
 * reported; holds and releases never are.
 *
 * Routing key: voucher.transaction.recorded  (business.events)
 * Consumer:    reports-ms -> voucher_transactions (append, deduplicated by the idempotency key)
 *
 * amount is always positive; type gives the direction:
 *   issued    liability up    (voucher sold, granted or re-issued as change)
 *   refunded  liability up    (value returned to an existing voucher)
 *   redeemed  liability down  (spent at a till; sale_id / sale_number identify the sale)
 *   expired   liability down  (balance lapsed, recognised as breakage)
 *   voided    liability down  (cancelled before use)
 *   adjusted  liability down  (excess left after a sale, settled as change, a new voucher or forfeited)
 */
final class VoucherTransactionReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $transaction_id,
        public int|string $voucher_id,
        public int $business_id,
        /** issued | refunded | redeemed | expired | voided | adjusted */
        public string $type,
        public float $amount,
        public float $balance_after,
        /** ISO-8601 */
        public string $occurred_at,
        public int|string|null $sale_id = null,
        public ?string $sale_number = null,
        /** The hold reference the till redeemed under. */
        public ?string $sale_ref = null,
        public ?int $branch_id = null,
        public int|string|null $terminal_id = null,
        public int|string|null $customer_id = null,
        public int|string|null $user_id = null,
        public ?string $reason = null,
    ) {
    }

    public function eventName(): string
    {
        return 'voucher.transaction.recorded';
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
        return 'voucher';
    }

    public function aggregateId(): string|int
    {
        return $this->voucher_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->occurred_at);
    }

    public function idempotencyKey(): ?string
    {
        return "voucher:txn:{$this->voucher_id}:{$this->transaction_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
