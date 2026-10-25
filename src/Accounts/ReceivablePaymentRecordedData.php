<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A payment was applied to a receivable.
 *
 * Routing keys: receivable.payment.recorded, or receivable.fully_paid when settled  (accounts.events)
 * Consumer:     reports-ms HandleReceivableSnapshot reads new_paid_amount / remaining_amount.
 */
final class ReceivablePaymentRecordedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $receivable_id,
        public int $business_id,
        public float $new_paid_amount,
        public float $remaining_amount,
        public bool $is_fully_paid,
        public ?string $status = null,
        public int|string|null $payment_id = null,
        public ?float $amount = null,
        /** ISO-8601 */
        public ?string $paid_at = null,
        public int|string|null $user_id = null,
        // Operational fields for sales-ms, which mirrors the payment onto its sale/invoice.
        // Optional additions are not a schema bump: consumers ignore unknown keys.
        public int|string|null $sale_id = null,
        public int|string|null $invoice_id = null,
        public ?string $invoice_number = null,
        public ?string $payment_method = null,
        public ?string $reference = null,
        public ?float $applied_amount = null,
    ) {
    }

    public function eventName(): string
    {
        return $this->is_fully_paid ? 'receivable.fully_paid' : 'receivable.payment.recorded';
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
        return 'receivable';
    }

    public function aggregateId(): string|int
    {
        return $this->receivable_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->paid_at);
    }

    public function idempotencyKey(): ?string
    {
        return $this->payment_id === null ? null : "receivable:payment:{$this->receivable_id}:{$this->payment_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
