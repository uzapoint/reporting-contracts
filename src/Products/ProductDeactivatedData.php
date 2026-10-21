<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Products;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Uzapoint\ReportingContracts\ReportData;

/**
 * A product was deactivated.
 *
 * Routing key: product.item.deactivated  (products.events)
 * Consumer:    reports-ms HandleProductDeactivation -> flags product_snapshots.is_active=false,
 *              appends a "deactivated" product_activity_logs row.
 */
final class ProductDeactivatedData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public int $product_id,
        public int $business_id,
        /** ISO-8601 */
        public string $deactivated_at,
        public ?string $name = null,
        public int|string|null $user_id = null,
    ) {}

    public function eventName(): string
    {
        return 'product.item.deactivated';
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
        return 'product';
    }

    public function aggregateId(): string|int
    {
        return $this->product_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->deactivated_at);
    }

    public function idempotencyKey(): ?string
    {
        return "product:deactivated:{$this->product_id}:{$this->deactivated_at}";
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
