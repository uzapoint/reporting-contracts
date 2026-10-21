<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Support;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A message was posted on a ticket.
 *
 * Routing key: ticket_message.created  (support.events)
 * Consumer:    reports-ms HandleTicketMessageSnapshot — increments message_count and
 *              stamps first_response_at on the first admin message.
 *
 * Append semantics on the consumer, so the message id is the idempotency key.
 */
final class TicketMessageCreatedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int|string $message_id,
        public int $ticket_id,
        public int $business_id,
        public bool $is_admin,
        /** ISO-8601 */
        public string $created_at,
        public int|string|null $user_id = null,
    ) {}

    public function eventName(): string
    {
        return 'ticket_message.created';
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
        return 'support_ticket';
    }

    public function aggregateId(): string|int
    {
        return $this->ticket_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->created_at);
    }

    public function idempotencyKey(): ?string
    {
        return "ticket_message:created:{$this->message_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
