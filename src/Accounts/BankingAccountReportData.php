<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A bank, mobile-money or cash account with its balances, republished whenever it changes
 * (every transaction moves current_balance).
 *
 * Routing key: accounts.banking_account.synced  (accounts.events)
 * Consumer:    reports-ms HandleBankingAccountSnapshot -> banking_accounts
 *
 * Added in 1.7.0, schema 1.
 */
final class BankingAccountReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $banking_account_id,
        public int $business_id,
        public string $account_name,
        public float $current_balance,
        public ?string $code = null,
        public int|string|null $coa_account_id = null,
        /** bank | mobile_money | cash | ... */
        public ?string $account_type = null,
        public ?string $account_number = null,
        public ?string $bank_name = null,
        public ?string $currency = null,
        public ?float $available_balance = null,
        public ?float $opening_balance = null,
        public ?float $minimum_balance = null,
        public ?float $overdraft_limit = null,
        public bool $is_active = true,
        public bool $is_primary = false,
        public int|string|null $branch_id = null,
        public ?string $last_transaction_at = null,
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.banking_account.synced';
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
        return 'banking_account';
    }

    public function aggregateId(): string|int
    {
        return $this->banking_account_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
