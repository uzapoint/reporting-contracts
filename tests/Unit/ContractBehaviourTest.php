<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SammyBoy\Reporting\Envelope\IncomingReport;
use SammyBoy\Reporting\Facades\Reporting;
use SammyBoy\Reporting\Reporting as ReportingService;
use Uzapoint\ReportingContracts\Accounts\ExpenseReportData;
use Uzapoint\ReportingContracts\Accounts\PayablePaymentRecordedData;
use Uzapoint\ReportingContracts\Accounts\ReceivablePaymentRecordedData;
use Uzapoint\ReportingContracts\Procurement\PurchaseOrderReportData;
use Uzapoint\ReportingContracts\Production\ProductionRequisitionReportData;
use Uzapoint\ReportingContracts\Products\ProductSkuChangedData;
use Uzapoint\ReportingContracts\Sales\InvoiceReportData;
use Uzapoint\ReportingContracts\Stocks\StockTransactionReportData;
use Uzapoint\ReportingContracts\Support\SupportTicketReportData;
use Uzapoint\ReportingContracts\Tests\Fixtures\Payloads;
use Uzapoint\ReportingContracts\Tests\TestCase;

final class ContractBehaviourTest extends TestCase
{
    #[Test]
    public function invoice_items_are_omitted_when_not_supplied_and_present_when_they_are(): void
    {
        $base = Payloads::all()[InvoiceReportData::class];

        $statusOnly = InvoiceReportData::from(array_diff_key($base, ['items' => true]));
        $this->assertArrayNotHasKey('items', $statusOnly->toReportPayload(), 'consumer must not blank stored items');

        $full = InvoiceReportData::from($base);
        $this->assertCount(1, $full->toReportPayload()['items']);
        $this->assertSame('Soda 500ml', $full->toReportPayload()['items'][0]['product_name']);

        $empty = InvoiceReportData::from([...$base, 'items' => []]);
        $this->assertSame([], $empty->toReportPayload()['items'], 'an explicit empty list is a real statement');
    }

    #[Test]
    public function lifecycle_contracts_derive_the_routing_key_from_their_action(): void
    {
        $expense = Payloads::all()[ExpenseReportData::class];

        $this->assertSame('accounts.expense.created', ExpenseReportData::from($expense)->eventName());
        $this->assertSame('accounts.expense.approved', ExpenseReportData::from([...$expense, 'action' => 'approved'])->eventName());
        $this->assertSame('accounts.expense.voided', ExpenseReportData::from([...$expense, 'action' => 'voided'])->eventName());

        $this->assertSame('expense:approved:1201', ExpenseReportData::from([...$expense, 'action' => 'approved'])->idempotencyKey());

        $po = Payloads::all()[PurchaseOrderReportData::class];
        $this->assertSame('procurement.purchase_order.cancelled', PurchaseOrderReportData::from([...$po, 'action' => 'cancelled'])->eventName());

        $ticket = Payloads::all()[SupportTicketReportData::class];
        $this->assertSame('support_ticket.closed', SupportTicketReportData::from([...$ticket, 'action' => 'closed'])->eventName());

        $sku = Payloads::all()[ProductSkuChangedData::class];
        $this->assertSame('products.skus.deleted', ProductSkuChangedData::from([...$sku, 'action' => 'deleted'])->eventName());
        $this->assertSame(9100, ProductSkuChangedData::from($sku)->toReportPayload()['id']);
    }

    #[Test]
    public function settlement_flag_selects_between_payment_recorded_and_fully_paid(): void
    {
        $payable = Payloads::all()[PayablePaymentRecordedData::class];
        $this->assertSame('payable.fully_paid', PayablePaymentRecordedData::from($payable)->eventName());
        $this->assertSame('payable.payment.recorded', PayablePaymentRecordedData::from([...$payable, 'is_fully_paid' => false])->eventName());

        $receivable = Payloads::all()[ReceivablePaymentRecordedData::class];
        $this->assertSame('receivable.payment.recorded', ReceivablePaymentRecordedData::from($receivable)->eventName());
        $this->assertSame('receivable.fully_paid', ReceivablePaymentRecordedData::from([...$receivable, 'is_fully_paid' => true])->eventName());

        // The consumer reads new_paid_amount from these, not amount_paid.
        $this->assertArrayHasKey('new_paid_amount', PayablePaymentRecordedData::from($payable)->toReportPayload());
        $this->assertArrayNotHasKey('amount_paid', PayablePaymentRecordedData::from($payable)->toReportPayload());
    }

    #[Test]
    public function production_stage_maps_to_the_three_existing_routing_keys(): void
    {
        $base = Payloads::all()[ProductionRequisitionReportData::class];

        $this->assertSame('production.requisition.synced', ProductionRequisitionReportData::from($base)->eventName());
        $this->assertSame('production.finished_goods.produced', ProductionRequisitionReportData::from([...$base, 'stage' => 'finished_goods.produced'])->eventName());
        $this->assertSame('production.raw_materials.consumed', ProductionRequisitionReportData::from([...$base, 'stage' => 'raw_materials.consumed'])->eventName());
    }

    #[Test]
    public function append_only_events_carry_deterministic_idempotency_keys(): void
    {
        $stock = StockTransactionReportData::from(Payloads::all()[StockTransactionReportData::class]);

        $this->assertSame('stock:transaction:40001', $stock->idempotencyKey());
        $this->assertSame(-2.0, $stock->quantity_change);
        $this->assertSame('2026-10-13T14:00:00+03:00', $stock->occurredAt()?->format(DATE_ATOM));
    }

    #[Test]
    public function accounts_contracts_publish_in_the_wrapped_format_their_consumers_destructure(): void
    {
        config()->set('reporting.envelope.format', 'wrapped');
        $this->app->forgetInstance(\SammyBoy\Reporting\Routing\EnvelopeFormatResolver::class);
        $this->app->forgetInstance(ReportingService::class);
        config()->set('reporting.exchange', 'accounts.events');
        $this->app->forgetInstance(\SammyBoy\Reporting\Routing\ExchangeResolver::class);

        $fake = Reporting::fake();
        $expense = ExpenseReportData::from(Payloads::all()[ExpenseReportData::class]);

        Reporting::event($expense);

        $published = $fake->published('accounts.expense.created')[0];
        $this->assertSame('accounts.events', $published->exchange);
        $this->assertSame('expense:created:1201', $published->envelope->eventId);

        // What HandleExpenseCreated::__construct(?int $auth_user_id, array $payload) receives.
        $this->assertSame(['auth_user_id', 'payload'], array_keys($published->data));
        $this->assertSame(3, $published->data['auth_user_id']);
        $this->assertSame(1201, $published->data['payload']['expense_id']);
        $this->assertSame(2500.5, $published->data['payload']['amount']);
        $this->assertSame('2026-10-13', $published->data['payload']['expense_date']);
        $this->assertSame('2026-10-13T11:30:00+03:00', $published->data['payload']['occurred_at']);

        // The handler's `$payload['payload'] ?? $payload` plus IncomingReport both resolve to the contract.
        $incoming = IncomingReport::from($published->data['payload']);
        $this->assertEquals($expense->toReportPayload(), ExpenseReportData::from($incoming->payload)->toReportPayload());
        $this->assertSame($published->envelope->correlationId, $incoming->correlationId());
    }
}
