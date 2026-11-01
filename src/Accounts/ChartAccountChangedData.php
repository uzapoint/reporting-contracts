<?php
// uzapoint/reporting-contracts (shared package) · /src/Accounts/ChartAccountChangedData.php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A chart-of-accounts account was created, changed or deleted.
 *
 * Routing keys: accounts.coa.upserted, or accounts.coa.deleted when is_deleted  (accounts.events)
 * Consumer:     reports-ms HandleLedgerAccountChanged -> ledger_accounts. A rename/recode is copied onto
 *               the account's existing ledger lines so old postings read correctly. The account type is
 *               immutable in accounts-ms, so statements never reclassify history.
 *
 * A deleted account only needs its identifiers; the descriptive fields may be absent.
 */
final class ChartAccountChangedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $coa_account_id,
        public int $business_id,
        public string $code,
        public bool $is_deleted = false,
        public ?string $name = null,
        /** asset | liability | equity | revenue | expense */
        public ?string $account_type = null,
        /** cash_bank | receivable | inventory | ... ; null when unclassified. Cash flow groups by it. */
        public ?string $account_subtype = null,
        /** debit | credit */
        public ?string $normal_balance = null,
        public int|string|null $parent_id = null,
        public bool $is_header = false,
        public bool $is_contra = false,
        public bool $is_active = true,
        public int|string|null $banking_account_id = null,
        public ?string $currency = null,
    ) {
    }

    public function eventName(): string
    {
        return $this->is_deleted ? 'accounts.coa.deleted' : 'accounts.coa.upserted';
    }

    public function kind(): ReportKind
    {
        return $this->is_deleted ? ReportKind::Event : ReportKind::Snapshot;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    public function aggregateType(): string
    {
        return 'chart_account';
    }

    public function aggregateId(): string|int
    {
        return $this->coa_account_id;
    }
}
