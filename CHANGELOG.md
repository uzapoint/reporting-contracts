# Changelog

Versions follow SemVer. A contract's `SCHEMA_VERSION` is independent of the package version and only bumps on an incompatible payload change.

## [1.6.1] - 2026-11-02

### Changed

- `Vouchers\VoucherTransactionReportData`: `type` gains `refunded` (value returned to an existing voucher; liability up), and `adjusted` is now defined as always a liability decrease (excess left after a sale, settled as change, a new voucher or forfeited). The type list and direction of each are documented on the class. No field added or removed. Schema stays 1.
- `Vouchers\VoucherReportData`: `channel` documents its actual values (`sale | refund | bulk | manual | change | ecommerce`). No code change.

## [1.6.0] - 2026-11-02

### Added

- `Vouchers\VoucherReportData` on `voucher.snapshot.synced`: a value voucher's current state (status, face value, balance, redeemed and expired amounts, expiry, channel, source sale). Carries `code_hint` only, never the code. Schema 1.
- `Vouchers\VoucherTransactionReportData` on `voucher.transaction.recorded`: one liability-changing movement (issued, redeemed, expired, voided, adjusted), idempotent per transaction. Schema 1.
- `Vouchers\PromoRedemptionReportData` on `promo.redemption.recorded`: a promo code redeemed or reversed, joined to its sale by `sale_ref`, idempotent per transition. Schema 1.
- `Sales\SalePromoData` and, on `SaleReportData`: `promo`, `promo_discount_total`, `voucher_paid_total`. On `SalePaymentReportData`: `is_voucher`, `reference`. All optional with zero/false/null defaults. Schema stays 1.

## [1.5.0] - 2026-11-01

### Added

- `Accounts\JournalEntryPostedData` (+ `JournalEntryLineData`) on `accounts.journal.posted`: a posted or reversed ledger entry with all its lines. Schema 1.
- `Accounts\JournalEntryReversedData` on `accounts.journal.reversed`. Schema 1.
- `Accounts\ChartAccountChangedData` on `accounts.coa.upserted` / `accounts.coa.deleted`, switched by `is_deleted`; carries `account_subtype`. Schema 1.

## [1.4.0] - 2026-10-29

### Added

- `Sales\SaleDeliveryData` and, on `SaleReportData`: `delivery`, `delivery_fee`, `item_discount_total`, `order_discount_total`, `free_goods_value`, `free_expense_total`. On `SaleItemReportData`: `is_free_good`, `is_free_with_expense`, `free_value`, `expense_amount`, `order_discount_total`. All optional with zero/false defaults. Schema stays 1.

## [1.3.0] - 2026-10-27

### Added

- `Commissions\CommissionLedgerReportData` (+ `CommissionSummaryReportData`) on `commission.ledger.recorded`. Schema 1.

## [1.2.0] - 2026-10-25

### Added

- `ReceivablePaymentReversedData` (`receivable.payment.reversed`): lets sales-ms take a reversed accounts payment off its sale/invoice. Schema 1.
- `ReceivablePaymentRecordedData`: optional `sale_id`, `invoice_id`, `invoice_number`, `payment_method`, `reference`, `applied_amount`. Schema stays 1.

## [1.0.0] - 2026-10-13

### Added

- `ReportData` base class (spatie/laravel-data + `Reportable`).
- Sales: `SaleReportData` (+ item, payment, user), `SalePaymentRecordedData`, `VoidedSaleItemReportData`, `InvoiceReportData`, `QuotationReportData` (+ `DocumentItemReportData`), `CreditNoteReportData` (+ item).
- Products: `ProductReportData` (+ `ProductSkuReportData`), `ProductDeactivatedData`, `ProductSkuChangedData`.
- Stocks: `StockTransactionReportData`.
- Accounts: `ExpenseReportData`, `PayableReportData`, `PayablePaymentRecordedData`, `ReceivableReportData`, `ReceivablePaymentRecordedData`.
- Support: `SupportTicketReportData`, `TicketMessageCreatedData`.
- Production: `ProductionRequisitionReportData`.
- Procurement: `PurchaseOrderReportData` (+ item), `RequisitionReportData`, `GoodsReceivedReportData`, `SupplierReportData`.
- Catalogue, compatibility and behaviour test suites.
