<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A payment was applied to a payable.
 *
 * Routing keys: payable.payment.recorded, or payable.fully_paid when the payment settles it  (accounts.events)
 * Consumer:     reports-ms HandlePayableSnapshot reads new_paid_amount (not amount_paid) from these.
 */
final class PayablePaymentRecordedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $payable_id,
        public int $business_id,
        /** Cumulative paid amount after this payment. */
        public float $new_paid_amount,
        public bool $is_fully_paid,
        public ?string $status = null,
        public int|string|null $payment_id = null,
        public ?float $amount = null,
        /** ISO-8601 */
        public ?string $paid_at = null,
        public int|string|null $user_id = null,
    ) {}

    public function eventName(): string
    {
        return $this->is_fully_paid ? 'payable.fully_paid' : 'payable.payment.recorded';
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
        return 'payable';
    }

    public function aggregateId(): string|int
    {
        return $this->payable_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->paid_at);
    }

    public function idempotencyKey(): ?string
    {
        return $this->payment_id === null ? null : "payable:payment:{$this->payable_id}:{$this->payment_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
