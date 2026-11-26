<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Accounts;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A fixed asset with its cost, depreciation and book value, republished whenever it changes.
 *
 * Routing key: accounts.asset.synced  (accounts.events)
 * Consumer:    reports-ms HandleFixedAssetSnapshot -> fixed_assets
 *
 * Added in 1.7.0, schema 1.
 */
final class FixedAssetReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $asset_id,
        public int $business_id,
        public string $name,
        public float $purchase_price,
        public ?string $asset_code = null,
        public ?string $category_name = null,
        public ?string $serial_number = null,
        public ?string $location = null,
        public int|string|null $branch_id = null,
        public ?string $assigned_to = null,
        public ?string $currency = null,
        public float $accumulated_depreciation = 0.0,
        public ?float $net_book_value = null,
        public ?float $salvage_value = null,
        public ?string $depreciation_method = null,
        public ?float $depreciation_rate = null,
        /** Y-m-d */
        public ?string $purchase_date = null,
        public ?string $depreciation_start_date = null,
        public ?string $warranty_expiry = null,
        public ?string $disposal_date = null,
        public ?string $updated_at = null,
    ) {}

    public function eventName(): string
    {
        return 'accounts.asset.synced';
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
        return 'fixed_asset';
    }

    public function aggregateId(): string|int
    {
        return $this->asset_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->updated_at);
    }
}
