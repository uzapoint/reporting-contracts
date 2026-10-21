<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A payment was recorded against an existing sale (credit sale paid later).
 *
 * Routing key: sale.payment.recorded  (sales.events)
 * Consumer:    reports-ms HandleSalePaymentRecorded -> updates payment_status,
 *              amount_paid, amount_due on the sale/order snapshots.
 */
final class SalePaymentRecordedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $sale_id,
        public int $business_id,
        /** Cumulative amount paid after this payment. */
        public float $amount_paid,
        public float $remaining_amount,
        public bool $is_fully_paid,
        public int|string|null $payment_id = null,
        public ?float $amount = null,
        public ?string $payment_method = null,
        /** ISO-8601 */
        public ?string $paid_at = null,
        public int|string|null $user_id = null,
    ) {}

    public function eventName(): string
    {
        return 'sale.payment.recorded';
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
        return 'sale';
    }

    public function aggregateId(): string|int
    {
        return $this->sale_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->paid_at);
    }

    /**
     * One payment, one event. Without a payment id each publication is unique.
     */
    public function idempotencyKey(): ?string
    {
        return $this->payment_id === null ? null : "sale:payment:{$this->sale_id}:{$this->payment_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
