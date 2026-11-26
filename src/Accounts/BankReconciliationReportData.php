<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A bank reconciliation session as it stands (statement against books, matched and unmatched
 * lines, difference).
 *
 * Routing key: accounts.reconciliation.synced  (accounts.events)
 * Consumer:    reports-ms HandleBankReconciliationSnapshot -> bank_reconciliations
 *
 * Added in 1.7.0, schema 1.
 */
final class BankReconciliationReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $reconciliation_id,
        public int $business_id,
        public int $banking_account_id,
        /** in_progress | completed | abandoned */
        public string $status,
        /** Y-m-d */
        public ?string $statement_date = null,
        public ?string $statement_start_date = null,
        public ?float $statement_opening_balance = null,
        public ?float $statement_closing_balance = null,
        public ?float $system_opening_balance = null,
        public ?float $system_closing_balance = null,
        public ?float $difference = null,
        public int $total_statement_lines = 0,
        public int $matched_lines = 0,
        public int $unmatched_lines = 0,
        public ?string $completed_at = null,
        public int|string|null $completed_by_user_id = null,
        public ?string $created_by_user_name = null,
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.reconciliation.synced';
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
        return 'bank_reconciliation';
    }

    public function aggregateId(): string|int
    {
        return $this->reconciliation_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
