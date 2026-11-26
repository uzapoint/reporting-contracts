<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A customer's prepaid wallet: balance, amount on hold and what is free to spend,
 * republished whenever it changes.
 *
 * Routing key: accounts.customer_wallet.synced  (accounts.events)
 * Consumer:    reports-ms HandleCustomerWalletSnapshot -> customer_wallets
 *
 * Added in 1.7.0, schema 1.
 */
final class CustomerWalletReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $wallet_id,
        public int $business_id,
        public int|string $customer_id,
        public float $balance,
        public ?string $customer_name = null,
        public ?string $customer_phone = null,
        public ?string $currency = null,
        public float $held_amount = 0.0,
        public ?float $available_balance = null,
        public bool $is_active = true,
        public ?string $last_transaction_at = null,
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.customer_wallet.synced';
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
        return 'customer_wallet';
    }

    public function aggregateId(): string|int
    {
        return $this->wallet_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
