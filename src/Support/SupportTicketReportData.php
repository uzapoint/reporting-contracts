<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Support;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\MapName;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a support ticket, published on every lifecycle transition.
 *
 * Routing keys: support_ticket.created | support_ticket.updated | support_ticket.closed  (support.events)
 * Consumer:     reports-ms HandleSupportTicketSnapshot -> support_ticket_snapshots,
 *               partial upsert by ticket id (null fields are not written).
 */
final class SupportTicketReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        /** Wire name is "id". */
        #[MapName('id')]
        public int $ticket_id,
        public int $business_id,
        /** created | updated | closed */
        public string $action,
        public string $status,
        public ?string $subject = null,
        public ?string $priority = null,
        public int|string|null $user_id = null,
        public ?int $ticket_category_id = null,
        public int|string|null $first_account_manager_id = null,
        public int|string|null $second_account_manager_id = null,
        public int|string|null $third_account_manager_id = null,
        /** ISO-8601 */
        public ?string $opened_at = null,
        public ?string $resolved_at = null,
        public ?string $closed_at = null,
        public int|string|null $closed_by_id = null,
    ) {}

    public function eventName(): string
    {
        return "support_ticket.{$this->action}";
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
        return 'support_ticket';
    }

    public function aggregateId(): string|int
    {
        return $this->ticket_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp(match ($this->action) {
            'closed' => $this->closed_at,
            'created' => $this->opened_at,
            default => null,
        });
    }

    public function actorId(): int|string|null
    {
        return $this->action === 'closed' ? $this->closed_by_id : $this->user_id;
    }
}
