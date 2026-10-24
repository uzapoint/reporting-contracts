<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Sales;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a sale: POS checkout, approved invoice, or any other sale type.
 *
 * Routing key: sale.item.synced  (sales.events)
 * Consumer:    reports-ms ProcessSaleSnapshot -> sale_snapshots, orders_snapshot, product_sales_log
 *
 * The consumer upserts by sale id, so the snapshot is republished on every
 * state change (payment recorded, partial/full void, edit, hydrate). It must
 * therefore carry the sale's complete current state: status, payment amounts,
 * void details and credit-note summaries are all part of it.
 *
 * sale_date is the moment the sale happened, as a full ISO-8601 timestamp
 * (POS close time, invoice approval time), never a bare date: the lake buckets
 * sales by hour and day from it.
 */
final class SaleReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<SaleItemReportData> $items
     * @param list<SalePaymentReportData> $payments
     * @param list<SaleUserReportData> $sale_users
     * @param list<SaleCreditNoteSummaryData> $credit_notes
     */
    public function __construct(
            /** Wire name is "id": the consumer keys the lake on $data['id']. */
        #[MapName('id')]
        public int|string $sale_id,
        public int $business_id,
        public string $sale_number,
        /** ISO-8601 timestamp of the sale, with time of day. */
        public string $sale_date,
        /** active | partially_voided | voided | cancelled | inactive */
        public string $status,
        public float $grand_total,
        /** pos | invoice | ecommerce ... */
        public string $type = 'pos',
        /** debit | credit | complete */
        public ?string $sale_type = null,
        /** not_paid | partially_paid | fully_paid */
        public ?string $payment_status = null,
        public float $amount_paid = 0.0,
        public float $amount_due = 0.0,
        public ?int $location_id = null,
        public int|string|null $terminal_id = null,
        public int|string|null $shift_id = null,
        public int|string|null $invoice_id = null,
        /** ISO-8601 date, credit sales only */
        public ?string $due_date = null,
        public int|string|null $customer_id = null,
        public ?string $customer_name = null,
        public ?string $customer_phone = null,
        public ?string $customer_email = null,
        /** Primary actor (seller / approver). */
        public int|string|null $user_id = null,
        public ?string $user_name = null,
        public string $currency = 'KES',
        public float $sub_total = 0.0,
        public float $discount_total = 0.0,
        public float $tax_total = 0.0,
        /** Sum of unit_cost * qty. When null the consumer derives it from item costs. */
        public ?float $total_cost = null,
        public ?string $void_reason = null,
        /** ISO-8601 */
        public ?string $voided_at = null,
        public ?string $notes = null,
        #[DataCollectionOf(SaleItemReportData::class)]
        public array $items = [],
        #[DataCollectionOf(SalePaymentReportData::class)]
        public array $payments = [],
        #[DataCollectionOf(SaleUserReportData::class)]
        public array $sale_users = [],
        #[DataCollectionOf(SaleCreditNoteSummaryData::class)]
        public array $credit_notes = [],
    ) {
    }

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
