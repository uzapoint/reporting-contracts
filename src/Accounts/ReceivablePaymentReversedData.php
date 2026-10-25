<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A receivable payment was reversed in accounts-ms. Reports-ms already follows the
 * receivable through its snapshot; sales-ms uses this event to take the mirrored
 * payment back off its sale and invoice.
 */
final class ReceivablePaymentReversedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $receivable_id,
        public int $business_id,
        public int|string $payment_id,
        public float $new_paid_amount,
        public float $remaining_amount,
        public ?string $status = null,
        public ?float $reversed_amount = null,
        public ?string $reason = null,
        public ?string $reversed_at = null,
        public int|string|null $user_id = null,
        public int|string|null $sale_id = null,
        public int|string|null $invoice_id = null,
        public ?string $invoice_number = null,
    ) {
    }

    public function eventName(): string
    {
        return 'receivable.payment.reversed';
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
        return $this->timestamp($this->reversed_at);
    }

    public function idempotencyKey(): ?string
    {
        return "receivable:payment_reversed:{$this->receivable_id}:{$this->payment_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
