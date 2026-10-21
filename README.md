# uzapoint/reporting-contracts

The typed payloads UzaPoint microservices publish to the Reports service. This package owns **what** UzaPoint reports: one class per reporting message, with the wire names, types, required fields and routing key fixed in code and shared by producer and consumer. It contains no transport code (that is `sammy-boy/laravel-reporting`) and no projections (those stay in reports-ms).

Think of each contract as the PHP equivalent of a shared TypeScript interface between the service that owns the data and the service that reports on it.

## Purpose

Today every producer hand-builds an array and every reports-ms handler reads it defensively (`quantity ?? qty`, `product_code ?? sku`, `po_id ?? id`, `total_value ?? total_amount`). Each alias is a shape drift that happened once and was patched on the consumer. A contract makes the shape explicit at the producer, where it can fail at build time, and gives the consumer one class to hydrate instead of a dozen `??` chains.

## Philosophy

- **Derived from the consumer, not invented.** Every field exists because a reports-ms handler or report reader uses it. Fields nobody reads are not here.
- **Wire compatibility first.** Property names are wire names. Where the consumer's established wire name is unfortunate (`id` for a sale), the property gets a sensible name and `#[MapName]` carries the wire name, so neither producer output nor consumer input changes. Routing keys are preserved exactly, including `sales.credit_note_issued` and `sale.item.synced`.
- **Snapshot or event is declared, not implied.** Each contract states its `ReportKind`. Classification follows what the consumer does with it (upsert = snapshot, append/delta = event), not what the routing key sounds like.
- **Required means required.** Missing a required field throws `CannotCreateData` at the producer. Nullable means "present, may be null". `Optional` means "may be absent on the wire" and is used only where the consumer distinguishes absent from empty (invoice items).
- **Scalars only on the wire.** Dates and timestamps are ISO-8601 strings. Money and quantities are floats. eventbus-core encodes whole floats as JSON integers; PHP widens them back, and decimal strings from PostgreSQL coerce into floats on hydration.
- **Lifecycle contracts carry their action.** One class covers `accounts.expense.{created,approved,voided}`, deriving the routing key from `$action`, mirroring how the producers already publish.

## Installation

```bash
composer require uzapoint/reporting-contracts
```

This pulls in `sammy-boy/laravel-reporting` and `spatie/laravel-data`. Configure `reporting.exchange` in the producing service; see the laravel-reporting README.

## Available contracts

Routing keys and consumers are those of reports-ms `config/eventbus.php` at the time of writing.

### Sales (`sales.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Sales\SaleReportData` (+ `SaleItemReportData`, `SalePaymentReportData`, `SaleUserReportData`) | snapshot | `sale.item.synced` | `ProcessSaleSnapshot` |
| `Sales\SalePaymentRecordedData` | event | `sale.payment.recorded` | `HandleSalePaymentRecorded` |
| `Sales\VoidedSaleItemReportData` | event | `sales.item.voided` | `StoreVoidedItemSnapshot` |
| `Sales\InvoiceReportData` (+ `DocumentItemReportData`) | snapshot | `invoice.snapshot.synced` | `HandleInvoiceSnapshot` |
| `Sales\QuotationReportData` (+ `DocumentItemReportData`) | snapshot | `quotation.snapshot.synced` | `HandleQuotationSnapshot` |
| `Sales\CreditNoteReportData` (+ `CreditNoteItemReportData`) | snapshot | `sales.credit_note_issued` | `HandleCreditNoteSnapshot` |

### Products (`products.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Products\ProductReportData` (+ `ProductSkuReportData`) | snapshot | `product.item.synced` | `SyncProductToLake` |
| `Products\ProductDeactivatedData` | event | `product.item.deactivated` | `HandleProductDeactivation` |
| `Products\ProductSkuChangedData` | event | `products.skus.{created,updated,deleted}` | `SyncProductToLake` / `HandleProductDeactivation` |

### Stocks (`stocks.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Stocks\StockTransactionReportData` | event | `stock.transaction.recorded` | `ProcessStockTransaction` |

### Accounts (`accounts.events`, published in the `wrapped` envelope format)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Accounts\ExpenseReportData` | event | `accounts.expense.{created,approved,voided}` | `HandleExpenseCreated` |
| `Accounts\PayableReportData` | snapshot | `accounts.payable.snapshot` | `HandlePayableSnapshot` |
| `Accounts\PayablePaymentRecordedData` | event | `payable.payment.recorded` / `payable.fully_paid` | `HandlePayableSnapshot` |
| `Accounts\ReceivableReportData` | snapshot | `accounts.receivable.snapshot` | `HandleReceivableSnapshot` |
| `Accounts\ReceivablePaymentRecordedData` | event | `receivable.payment.recorded` / `receivable.fully_paid` | `HandleReceivableSnapshot` |

### Support (`support.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Support\SupportTicketReportData` | snapshot | `support_ticket.{created,updated,closed}` | `HandleSupportTicketSnapshot` |
| `Support\TicketMessageCreatedData` | event | `ticket_message.created` | `HandleTicketMessageSnapshot` |

### Production (`production.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Production\ProductionRequisitionReportData` | snapshot | `production.requisition.synced` / `production.finished_goods.produced` / `production.raw_materials.consumed` | `HandleProductionRequisitionSnapshot` |

### Procurement (`procurement.events`)

| Contract | Kind | Routing key | Consumer |
| --- | --- | --- | --- |
| `Procurement\PurchaseOrderReportData` (+ `PurchaseOrderItemReportData`) | snapshot | `procurement.purchase_order.{created,sent,accepted,rejected,cancelled}` | `HandleProcurementPurchaseOrder` |
| `Procurement\RequisitionReportData` | snapshot | `procurement.requisition.{created,approved,rejected}` | `HandleProcurementRequisition` |
| `Procurement\GoodsReceivedReportData` | snapshot | `procurement.goods_received.grn_created` | `HandleProcurementGoodsReceived` |
| `Procurement\SupplierReportData` | snapshot | `procurement.supplier.{created,updated}` | `HandleProcurementSupplier` |

### Deliberately not contracted

`procurement.goods_received.items_received`, `procurement.purchase_return.*`, `procurement.rejected_item.recorded`, `procurement.catalogue.generated` and `business.deleted` / `business.data.purged`. The first group is consumed by raw-dump listeners (`StoreProcurementSnapshot`, `AlsoWritesRawProcurementSnapshot`) with no defined shape, and the reports-ms comments record that `.submitted`/`.approved` payloads are hand-rolled duplicates without reliable status. Defining a DTO would be inventing a schema; decide the shape in procurement-ms first. The business purge events are platform commands, not reports.

## How producers use the contracts

Construct the contract from the owning model in the producing service (the contracts package knows nothing about Eloquent models), then publish it.

```php
// sales-ms  app/Reporting/SaleReportMapper.php
final class SaleReportMapper
{
    public function snapshot(Sale $sale): SaleReportData
    {
        return new SaleReportData(
            sale_id: $sale->id,
            business_id: $sale->business_id,
            sale_number: $sale->sale_number,
            sale_date: $sale->sale_date->toIso8601String(),
            status: $sale->status,
            grand_total: (float) $sale->grand_total,
            location_id: $sale->location_id,
            terminal_id: $sale->terminal_id,
            payment_status: $sale->payment_status,
            customer_id: $sale->customer_id,
            customer_name: $sale->customer_name,
            user_id: $sale->user_id,
            user_name: $sale->user_name,
            sub_total: (float) $sale->sub_total,
            discount_total: (float) $sale->discount_total,
            tax_total: (float) $sale->tax_total,
            items: $sale->items->map(fn (SaleItem $i) => new SaleItemReportData(
                product_code: $i->product_code,
                product_name: $i->product_name,
                quantity: (float) $i->quantity,
                unit_price: (float) $i->unit_price,
                line_total: (float) $i->line_total,
                product_sku_id: $i->product_sku_id,
                unit_cost: $i->unit_cost === null ? null : (float) $i->unit_cost,
                tracking_method: $i->tracking_method ?? 'none',
                batch_number: $i->batch_number,
            ))->all(),
            payments: $sale->payments->map(fn (SalePayment $p) => new SalePaymentReportData(
                payment_method: $p->method,
                amount: (float) $p->amount,
                payment_date: $p->paid_at?->toIso8601String(),
            ))->all(),
        );
    }
}
```

```php
// sales-ms  app/Services/SaleService.php
DB::transaction(function () use ($sale) {
    // ...business writes...
    $this->reporting->snapshot($this->mapper->snapshot($sale->fresh(['items', 'payments'])));
});
```

`Reporting::snapshot()` defers the publish to the commit and drops it on rollback. The `rabbitmq:hydrate-lake` style commands become a loop over models calling the same mapper.

For lifecycle contracts, set the action from the transition:

```php
$this->reporting->event(new ExpenseReportData(
    expense_id: $expense->id,
    business_id: $expense->business_id,
    action: 'approved',
    amount: (float) $expense->amount,
    tax_amount: (float) $expense->tax_amount,
    net_amount: (float) $expense->net_amount,
    expense_date: $expense->expense_date->toDateString(),
    occurred_at: now()->toIso8601String(),
    is_approved: true,
    created_by_user_id: $expense->created_by_user_id,
    created_by_user_name: $expense->created_by_user_name,
    actor_user_id: $expense->approved_by_user_id,
));
```

## How consumers use the contracts

```php
$report = IncomingReport::from($data);

if ($report->isContracted()) {
    $sale = SaleReportData::from($report->payload);   // throws on a malformed message
    // typed access: $sale->items[0]->unit_cost
} else {
    // legacy array path, until every producer of this key is migrated
}
```

Hydration ignores unknown keys, so a consumer on contract version N accepts a producer that added optional fields in N+1.

## Schema evolution

- Each contract carries `SCHEMA_VERSION`, published as `schema_version` in the envelope.
- **Adding an optional field**: no version bump. Old consumers ignore it, new consumers default it.
- **Removing, renaming or re-typing a field**: bump `SCHEMA_VERSION`, keep the old class available for consumers that still branch on the previous version, and update the consumer to `match` on `IncomingReport::schemaVersion()`.
- **New routing key**: new class. Never reuse a class for a key with different semantics.
- **Legacy messages**: `IncomingReport::schemaVersion()` returns `0`. Treat `0` as "pre-contract shape" until the producer is migrated, then delete that branch.

## Backward compatibility

Contracted messages are byte-compatible with what existing handlers read: the same routing key, the same field names at the top level of `data` (flat) or under `payload` (wrapped), plus one extra `_reporting` block the legacy handlers do not look at. Handlers that spread the whole payload into Mongo (`StoreVoidedItemSnapshot`, `StoreProcurementSnapshot`, `AlsoWritesRawProcurementSnapshot`, the raw insert in `HandleProductionRequisitionSnapshot`) will persist that block verbatim until they are migrated to `IncomingReport`, which strips it.

Field aliases the consumer still accepts (`qty`, `sku` on sale items, `id`/`reference_no`/`total_amount` on procurement) are not part of any contract. Keep the consumer fallbacks until the corresponding producer publishes the contract, then remove them.

## Testing

`composer test` runs 104 tests: a catalogue test that discovers every contract in `src/`, demands a fixture for each, and asserts that it hydrates, declares a routing key the Reports MS actually subscribes to, round-trips through JSON as eventbus-core encodes it, ignores unknown fields, and publishes through laravel-reporting with the right kind, version, aggregate id, idempotency key and `occurred_at`; a compatibility test pinning `sale.item.synced` to every key `ProcessSaleSnapshot` reads; and behaviour tests for `Optional` items, lifecycle routing keys, settlement flags, deterministic idempotency keys and the wrapped accounts framing.

## License

MIT.
