<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Products;

use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a product and its SKUs.
 *
 * Routing key: product.item.synced  (products.events)
 * Consumer:    reports-ms SyncProductToLake -> product_snapshots (upsert by product_id)
 *              + product_activity_logs
 *
 * The consumer keys on product_id. created_by "System" is treated as unknown
 * by the consumer; send null instead of a sentinel.
 */
final class ProductReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<ProductSkuReportData> $skus
     */
    public function __construct(
        public int $product_id,
        public int $business_id,
        public string $name,
        public bool $is_active = true,
        public ?string $sku_name = null,
        public ?string $category_name = null,
        /** none | batch | serial */
        public ?string $tracking_method = null,
        public ?string $image_url = null,
        public int|string|null $created_by = null,
        public ?string $creator_name = null,
        public int|string|null $user_id = null,
        #[DataCollectionOf(ProductSkuReportData::class)]
        public array $skus = [],
    ) {}

    public function eventName(): string
    {
        return 'product.item.synced';
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
        return 'product';
    }

    public function aggregateId(): string|int
    {
        return $this->product_id;
    }

    public function actorId(): int|string|null
    {
        return $this->user_id ?? $this->created_by;
    }
}
