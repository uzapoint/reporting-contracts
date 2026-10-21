<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a completed sale (POS or approved invoice converted to a sale).
 *
 * Routing key: sale.item.synced  (sales.events)
 * Consumer:    reports-ms ProcessSaleSnapshot -> sale_snapshots, orders_snapshot, product_sales_log
 *
 * The consumer upserts by sale id, so re-publishing the same sale (void, edit,
 * hydrate) is the intended way to correct the lake. Voids republish with
 * status "voided".
 */
final class SaleReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<SaleItemReportData> $items
     * @param list<SalePaymentReportData> $payments
     * @param list<SaleUserReportData> $sale_users
     */
    public function __construct(
        /** Wire name is "id": the consumer keys the lake on $data['id']. */
        #[MapName('id')]
        public int|string $sale_id,
        public int $business_id,
        public string $sale_number,
        /** ISO-8601. Drives sale_at, product_sales_log.date and daily aggregations. */
        public string $sale_date,
        /** completed | voided | active ... — "voided" removes the sale from revenue. */
        public string $status,
        public float $grand_total,
        public ?int $location_id = null,
        public int|string|null $terminal_id = null,
        public ?int $shift_id = null,
        public string $type = 'pos',
        public ?string $payment_status = null,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public ?string $customer_phone = null,
        public ?string $customer_email = null,
        public int|string|null $user_id = null,
        public ?string $user_name = null,
        public string $currency = 'KES',
        public float $sub_total = 0.0,
        public float $discount_total = 0.0,
        public float $tax_total = 0.0,
        /** Sum of unit_cost * qty. When null the consumer derives it from item costs. */
        public ?float $total_cost = null,
        public ?string $notes = null,
        #[DataCollectionOf(SaleItemReportData::class)]
        public array $items = [],
        #[DataCollectionOf(SalePaymentReportData::class)]
        public array $payments = [],
        #[DataCollectionOf(SaleUserReportData::class)]
        public array $sale_users = [],
    ) {}

    public function eventName(): string
    {
        return 'sale.item.synced';
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
        return 'sale';
    }

    public function aggregateId(): string|int
    {
        return $this->sale_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->sale_date);
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
