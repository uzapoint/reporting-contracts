<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SammyBoy\Reporting\Envelope\IncomingReport;
use SammyBoy\Reporting\Envelope\ReportKind;
use SammyBoy\Reporting\Facades\Reporting;
use Spatie\LaravelData\Exceptions\CannotCreateData;
use Uzapoint\ReportingContracts\Sales\SaleItemReportData;
use Uzapoint\ReportingContracts\Sales\SalePaymentRecordedData;
use Uzapoint\ReportingContracts\Sales\SaleReportData;
use Uzapoint\ReportingContracts\Tests\Fixtures\Payloads;
use Uzapoint\ReportingContracts\Tests\TestCase;

/**
 * sale.item.synced is the first migrated flow. These tests pin the wire shape
 * to exactly what reports-ms ProcessSaleSnapshot reads today, so a producer
 * switching to the contract changes nothing the consumer sees.
 */
final class SaleContractCompatibilityTest extends TestCase
{
    /** Top-level keys ProcessSaleSnapshot reads from $input. */
    private const array HANDLER_SALE_READS = [
        'id', 'business_id', 'sale_number', 'sale_date', 'status', 'payment_status', 'type',
        'location_id', 'terminal_id', 'shift_id', 'customer_id', 'customer_name', 'customer_phone', 'customer_email',
        'user_id', 'user_name', 'currency', 'sub_total', 'discount_total', 'tax_total', 'grand_total', 'total_cost',
        'notes', 'items', 'payments', 'sale_users',
    ];

    /** Keys ProcessSaleSnapshot reads from each item (primary names, not legacy aliases). */
    private const array HANDLER_ITEM_READS = [
        'product_sku_id', 'product_code', 'product_name', 'category_id', 'category_name', 'unit_cost', 'quantity',
        'unit_price', 'line_subtotal', 'line_total', 'line_tax_total', 'line_discount_total', 'batch_number',
        'serial_number', 'tracking_method', 'expiry_date',
    ];

    #[Test]
    public function wire_payload_carries_every_key_the_consumer_reads(): void
    {
        $wire = SaleReportData::from(Payloads::sale())->toReportPayload();

        foreach (self::HANDLER_SALE_READS as $key) {
            $this->assertArrayHasKey($key, $wire, "consumer reads \$input['{$key}']");
        }

        foreach (self::HANDLER_ITEM_READS as $key) {
            $this->assertArrayHasKey($key, $wire['items'][0], "consumer reads \$row['{$key}']");
        }

        $this->assertSame(['payment_method', 'amount', 'payment_date'], array_keys($wire['payments'][0]));
        $this->assertSame(['user_id', 'user_name'], array_keys($wire['sale_users'][0]));
    }

    #[Test]
    public function sale_id_is_published_under_the_legacy_wire_name_id(): void
    {
        $sale = SaleReportData::from(Payloads::sale());

        $this->assertSame(9001, $sale->sale_id);
        $this->assertSame(9001, $sale->toReportPayload()['id']);
        $this->assertArrayNotHasKey('sale_id', $sale->toReportPayload());
        $this->assertSame('9001', (string) $sale->aggregateId());
    }

    #[Test]
    public function never_looks_like_an_invoice_to_the_consumer(): void
    {
        // ProcessSaleSnapshot refuses payloads with invoice_number but no sale_number.
        $wire = SaleReportData::from(Payloads::sale())->toReportPayload();

        $this->assertNotEmpty($wire['sale_number']);
        $this->assertArrayNotHasKey('invoice_number', $wire);
    }

    #[Test]
    public function sale_number_is_required(): void
    {
        $this->expectException(CannotCreateData::class);

        SaleReportData::from(array_diff_key(Payloads::sale(), ['sale_number' => true]));
    }

    #[Test]
    public function hydrates_decimal_strings_from_postgres_into_floats(): void
    {
        $sale = SaleReportData::from([
            ...Payloads::sale(),
            'grand_total' => '2000.50',
            'sub_total' => '1724.57',
            'business_id' => '12',
            'items' => [['product_code' => 'A', 'product_name' => 'A', 'quantity' => '3', 'unit_price' => '10.50', 'line_total' => '31.50']],
        ]);

        $this->assertSame(2000.5, $sale->grand_total);
        $this->assertSame(1724.57, $sale->sub_total);
        $this->assertSame(12, $sale->business_id);
        $this->assertSame(3.0, $sale->items[0]->quantity);
        $this->assertSame(31.5, $sale->items[0]->line_total);
    }

    #[Test]
    public function nested_collections_are_typed(): void
    {
        $sale = SaleReportData::from(Payloads::sale());

        $this->assertCount(2, $sale->items);
        $this->assertContainsOnlyInstancesOf(SaleItemReportData::class, $sale->items);
        $this->assertSame('batch', $sale->items[1]->tracking_method);
        $this->assertNull($sale->items[1]->unit_cost, 'absent cost is null so the consumer resolves it from inventory');
        $this->assertSame(55.25, $sale->items[0]->unit_cost);
    }

    #[Test]
    public function a_void_is_the_same_snapshot_with_status_voided(): void
    {
        $voided = SaleReportData::from([...Payloads::sale(), 'status' => 'voided']);

        $this->assertSame('sale.item.synced', $voided->eventName());
        $this->assertSame('voided', $voided->toReportPayload()['status']);
        $this->assertNull($voided->idempotencyKey(), 'every re-sync must be processed, so no deterministic key');
    }

    #[Test]
    public function metadata_rides_alongside_the_legacy_fields_on_the_wire(): void
    {
        $fake = Reporting::fake();
        $sale = SaleReportData::from(Payloads::sale());

        $envelope = Reporting::snapshot($sale);

        $data = $fake->published('sale.item.synced')[0]->data;

        // Legacy consumer path: untouched.
        $this->assertSame(9001, $data['id']);
        $this->assertSame(2000.5, $data['grand_total']);
        $this->assertCount(2, $data['items']);

        // Migrated consumer path: hydrate the contract from the same bytes.
        $incoming = IncomingReport::from($data);
        $this->assertTrue($incoming->isContracted());
        $this->assertSame(SaleReportData::SCHEMA_VERSION, $incoming->schemaVersion());
        $this->assertSame(ReportKind::Snapshot, $incoming->envelope->kind);
        $this->assertSame('sale', $incoming->envelope->aggregateType);
        $this->assertSame('2026-10-13T13:45:00+03:00', $incoming->envelope->occurredAt->format(DATE_ATOM));
        $this->assertSame(3, $incoming->envelope->actorId);

        $rehydrated = SaleReportData::from($incoming->payload);
        $this->assertEquals($sale->toReportPayload(), $rehydrated->toReportPayload());
        $this->assertSame($envelope->eventId, $incoming->envelope->eventId);
    }

    #[Test]
    public function payment_recorded_is_an_event_keyed_per_payment(): void
    {
        $fake = Reporting::fake();
        $payment = SalePaymentRecordedData::from(Payloads::all()[SalePaymentRecordedData::class]);

        Reporting::event($payment);

        $published = $fake->published('sale.payment.recorded')[0];
        $this->assertSame('sale:payment:9001:77', $published->envelope->eventId);
        $this->assertSame(9001, $published->data['sale_id']);
        $this->assertSame(1500.25, $published->data['amount_paid']);
        $this->assertFalse($published->data['is_fully_paid']);
        $this->assertSame(499.75, $published->data['remaining_amount']);
    }
}
