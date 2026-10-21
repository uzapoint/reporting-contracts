<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * An expense lifecycle transition, carrying the expense's current state.
 *
 * Routing keys: accounts.expense.created | accounts.expense.approved | accounts.expense.voided  (accounts.events)
 * Consumer:     reports-ms HandleExpenseCreated -> expense_snapshots, upsert by expense_id.
 *               Defers the upsert while requires_approval && !is_approved.
 *
 * Published with the `wrapped` envelope format: accounts-ms consumers
 * destructure (?int $auth_user_id, array $payload). Configure
 * reporting.envelope.format = wrapped in accounts-ms.
 *
 * The idempotency key reproduces the existing producer's
 * "expense:{action}:{id}", so one transition is processed once even if the
 * publish is retried.
 */
final class ExpenseReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $expense_id,
        public int $business_id,
        /** created | approved | voided */
        public string $action,
        public float $amount,
        public float $tax_amount,
        public float $net_amount,
        /** ISO-8601 date */
        public string $expense_date,
        /** ISO-8601 timestamp of the transition */
        public string $occurred_at,
        public ?string $expense_ref = null,
        public ?int $branch_id = null,
        public int|string|null $terminal_id = null,
        public string $currency = 'KES',
        public ?string $category = null,
        public ?string $payment_method = null,
        public bool $requires_approval = false,
        public bool $is_approved = false,
        public bool $is_void = false,
        public int|string|null $created_by_user_id = null,
        public ?string $created_by_user_name = null,
        public int|string|null $actor_user_id = null,
    ) {}

    public function eventName(): string
    {
        return "accounts.expense.{$this->action}";
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
        return 'expense';
    }

    public function aggregateId(): string|int
    {
        return $this->expense_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->occurred_at);
    }

    public function idempotencyKey(): ?string
    {
        return "expense:{$this->action}:{$this->expense_id}";
    }

    public function actorId(): int|string|null
    {
        return $this->actor_user_id ?? $this->created_by_user_id;
    }
}
