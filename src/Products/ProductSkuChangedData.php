<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Products;

use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\MapName;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A single SKU row was created, updated or deleted.
 *
 * Routing keys: products.skus.created | products.skus.updated | products.skus.deleted  (products.events)
 * Consumers:    reports-ms SyncProductToLake (created/updated) and
 *               HandleProductDeactivation (deleted).
 *
 * This is the "thin" product event: the consumer recognises it by the presence
 * of product_id alongside a SKU-level id and deliberately never lets it blank
 * fields written by the richer ProductReportData.
 */
final class ProductSkuChangedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        /** Wire name is "id": the SKU row id, not the product id. */
        #[MapName('id')]
        public int $sku_id,
        public int $product_id,
        public int $business_id,
        public string $sku,
        /** created | updated | deleted */
        public string $action,
        public ?float $price = null,
        public ?string $barcode = null,
        public ?string $name = null,
        public int|string|null $user_id = null,
        /** ISO-8601, deletions only */
        public ?string $deleted_at = null,
    ) {}

    public function eventName(): string
    {
        return "products.skus.{$this->action}";
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
        return 'product_sku';
    }

    public function aggregateId(): string|int
    {
        return $this->sku_id;
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
