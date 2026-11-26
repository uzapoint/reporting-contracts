<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A budget and every line of it (account, fiscal period, amount), republished whenever it or
 * a line changes. Amounts are positive on the account's natural side (income to earn, cost
 * to spend).
 *
 * Routing key: accounts.budget.synced  (accounts.events)
 * Consumer:    reports-ms HandleBudgetSnapshot -> budgets
 *
 * Added in 1.7.0, schema 1.
 */
final class BudgetReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $budget_id,
        public int $business_id,
        public string $name,
        /** draft | approved | ... */
        public string $status,
        public int|string|null $fiscal_year_id = null,
        public ?string $fiscal_year_name = null,
        /** Y-m-d */
        public ?string $fiscal_year_start = null,
        public ?string $fiscal_year_end = null,
        public ?string $approved_at = null,
        public int|string|null $approved_by_user_id = null,
        public ?string $notes = null,
        /** @var list<BudgetLineReportData> */
        #[DataCollectionOf(BudgetLineReportData::class)]
        public array $lines = [],
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.budget.synced';
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
        return 'budget';
    }

    public function aggregateId(): string|int
    {
        return $this->budget_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
