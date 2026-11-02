# uzapoint/reporting-contracts

Typed payloads the UzaPoint microservices publish to reports-ms: one class per reporting message, with wire names, types and routing key fixed in code and shared by producer and consumer. No transport (that is `sammy-boy/laravel-reporting`) and no projections (those stay in reports-ms).

## Install

```bash
composer require uzapoint/reporting-contracts
```

## Rules

- Property names are wire names; `#[MapName]` carries the wire name where it differs. Routing keys never change.
- Each contract declares its `ReportKind` (snapshot = upsert, event = append/delta).
- Required fields throw `CannotCreateData` at the producer. Nullable = present, may be null. `Optional` = may be absent.
- Dates are ISO-8601 strings, money and quantities are floats.
- Adding an optional field needs no bump. Removing, renaming or re-typing a field bumps the contract's `SCHEMA_VERSION`. A new routing key is a new class.
- Consumers hydrate with `Dto::from(IncomingReport::from($data)->payload)`; unknown keys are ignored.

## Contracts

### Sales (`sales.events`)

| Contract                                                                                                                            | Kind     | Routing key                 | Consumer                    |
| ----------------------------------------------------------------------------------------------------------------------------------- | -------- | --------------------------- | --------------------------- |
| `Sales\SaleReportData` (+ `SaleItemReportData`, `SalePaymentReportData`, `SaleUserReportData`, `SalePromoData`, `SaleDeliveryData`) | snapshot | `sale.item.synced`          | `ProcessSaleSnapshot`       |
| `Sales\SalePaymentRecordedData`                                                                                                     | event    | `sale.payment.recorded`     | `HandleSalePaymentRecorded` |
| `Sales\VoidedSaleItemReportData`                                                                                                    | event    | `sales.item.voided`         | `StoreVoidedItemSnapshot`   |
| `Sales\InvoiceReportData` (+ `DocumentItemReportData`)                                                                              | snapshot | `invoice.snapshot.synced`   | `HandleInvoiceSnapshot`     |
| `Sales\QuotationReportData` (+ `DocumentItemReportData`)                                                                            | snapshot | `quotation.snapshot.synced` | `HandleQuotationSnapshot`   |
| `Sales\CreditNoteReportData` (+ `CreditNoteItemReportData`)                                                                         | snapshot | `sales.credit_note_issued`  | `HandleCreditNoteSnapshot`  |

### Products (`products.events`)

| Contract                                                | Kind     | Routing key                               | Consumer                                          |
| ------------------------------------------------------- | -------- | ----------------------------------------- | ------------------------------------------------- |
| `Products\ProductReportData` (+ `ProductSkuReportData`) | snapshot | `product.item.synced`                     | `SyncProductToLake`                               |
| `Products\ProductDeactivatedData`                       | event    | `product.item.deactivated`                | `HandleProductDeactivation`                       |
| `Products\ProductSkuChangedData`                        | event    | `products.skus.{created,updated,deleted}` | `SyncProductToLake` / `HandleProductDeactivation` |

### Stocks (`stocks.events`)

| Contract                            | Kind  | Routing key                  | Consumer                  |
| ----------------------------------- | ----- | ---------------------------- | ------------------------- |
| `Stocks\StockTransactionReportData` | event | `stock.transaction.recorded` | `ProcessStockTransaction` |

### Accounts (`accounts.events`, `wrapped` envelope)

| Contract                                 | Kind     | Routing key                                             | Consumer                   |
| ---------------------------------------- | -------- | ------------------------------------------------------- | -------------------------- |
| `Accounts\ExpenseReportData`             | event    | `accounts.expense.{created,approved,voided}`            | `HandleExpenseCreated`     |
| `Accounts\PayableReportData`             | snapshot | `accounts.payable.snapshot`                             | `HandlePayableSnapshot`    |
| `Accounts\PayablePaymentRecordedData`    | event    | `payable.payment.recorded` / `payable.fully_paid`       | `HandlePayableSnapshot`    |
| `Accounts\ReceivableReportData`          | snapshot | `accounts.receivable.snapshot`                          | `HandleReceivableSnapshot` |
| `Accounts\ReceivablePaymentRecordedData` | event    | `receivable.payment.recorded` / `receivable.fully_paid` | `HandleReceivableSnapshot` |

### Ledger (`accounts.events`, flat envelope)

| Contract                                                     | Kind             | Routing key                                      | Consumer                      |
| ------------------------------------------------------------ | ---------------- | ------------------------------------------------ | ----------------------------- |
| `Accounts\JournalEntryPostedData` (+ `JournalEntryLineData`) | snapshot         | `accounts.journal.posted`                        | `HandleLedgerJournalPosted`   |
| `Accounts\JournalEntryReversedData`                          | event            | `accounts.journal.reversed`                      | `HandleLedgerJournalReversed` |
| `Accounts\ChartAccountChangedData`                           | snapshot / event | `accounts.coa.upserted` / `accounts.coa.deleted` | `HandleLedgerAccountChanged`  |

Published by accounts-ms for every posted or reversed entry and every chart-of-accounts change. reports-ms builds all financial statements from them.

### Support (`support.events`)

| Contract                           | Kind     | Routing key                               | Consumer                      |
| ---------------------------------- | -------- | ----------------------------------------- | ----------------------------- |
| `Support\SupportTicketReportData`  | snapshot | `support_ticket.{created,updated,closed}` | `HandleSupportTicketSnapshot` |
| `Support\TicketMessageCreatedData` | event    | `ticket_message.created`                  | `HandleTicketMessageSnapshot` |

### Production (`production.events`)

| Contract                                     | Kind     | Routing key                                                                                                  | Consumer                              |
| -------------------------------------------- | -------- | ------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| `Production\ProductionRequisitionReportData` | snapshot | `production.requisition.synced` / `production.finished_goods.produced` / `production.raw_materials.consumed` | `HandleProductionRequisitionSnapshot` |

### Procurement (`procurement.events`)

| Contract                                                                | Kind     | Routing key                                                             | Consumer                         |
| ----------------------------------------------------------------------- | -------- | ----------------------------------------------------------------------- | -------------------------------- |
| `Procurement\PurchaseOrderReportData` (+ `PurchaseOrderItemReportData`) | snapshot | `procurement.purchase_order.{created,sent,accepted,rejected,cancelled}` | `HandleProcurementPurchaseOrder` |
| `Procurement\RequisitionReportData`                                     | snapshot | `procurement.requisition.{created,approved,rejected}`                   | `HandleProcurementRequisition`   |
| `Procurement\GoodsReceivedReportData`                                   | snapshot | `procurement.goods_received.grn_created`                                | `HandleProcurementGoodsReceived` |
| `Procurement\SupplierReportData`                                        | snapshot | `procurement.supplier.{created,updated}`                                | `HandleProcurementSupplier`      |

### Commissions (`business.events`)

| Contract                                                                   | Kind     | Routing key                  | Consumer                         |
| -------------------------------------------------------------------------- | -------- | ---------------------------- | -------------------------------- |
| `Commissions\CommissionLedgerReportData` (+ `CommissionSummaryReportData`) | snapshot | `commission.ledger.recorded` | `HandleCommissionLedgerRecorded` |

Published by business-ms after every ledger change (earn, approve, reject, payout, clawback, adjust). The commission _commands_ that feed business-ms (`*.commission.completed|refunded`) are operational events, not reports, and are deliberately not contracted here.

### Vouchers (`business.events`)

| Contract                                | Kind     | Routing key                    | Consumer                    |
| --------------------------------------- | -------- | ------------------------------ | --------------------------- |
| `Vouchers\VoucherReportData`            | snapshot | `voucher.snapshot.synced`      | `ProcessVoucherSnapshot`    |
| `Vouchers\VoucherTransactionReportData` | event    | `voucher.transaction.recorded` | `ProcessVoucherTransaction` |
| `Vouchers\PromoRedemptionReportData`    | event    | `promo.redemption.recorded`    | `ProcessPromoRedemption`    |

Published by business-ms on every value-voucher change and promo redemption or reversal. Voucher reports carry `code_hint` only, never the code. A voucher tender on a sale is not a separate event: it is a payment flagged `is_voucher` on `SaleReportData`, and a promo code is `SaleReportData.promo`, joined to its redemption by `sale_ref`.

### Not contracted

`procurement.goods_received.items_received`, `procurement.purchase_return.*`, `procurement.rejected_item.recorded`, `procurement.catalogue.generated` (no defined shape in procurement-ms yet), and `business.deleted` / `business.data.purged` (platform commands).

## Testing

`composer test`. The catalogue test requires a fixture in `tests/Fixtures/Payloads.php` for every contract in `src/`, and every routing key must be listed in `CONSUMED_ROUTING_KEYS`.
