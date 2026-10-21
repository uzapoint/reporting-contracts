# Changelog

All notable changes to `uzapoint/reporting-contracts` are documented here. The format follows Keep a Changelog; versions follow SemVer. A contract's own `SCHEMA_VERSION` is independent of the package version and is bumped only on an incompatible payload change.

## [1.0.0] - 2026-10-13

### Added
- `ReportData` base class (spatie/laravel-data + `Reportable`).
- Sales: `SaleReportData` (+ item, payment, user), `SalePaymentRecordedData`, `VoidedSaleItemReportData`, `InvoiceReportData`, `QuotationReportData` (+ `DocumentItemReportData`), `CreditNoteReportData` (+ item). All schema 1.
- Products: `ProductReportData` (+ `ProductSkuReportData`), `ProductDeactivatedData`, `ProductSkuChangedData`. Schema 1.
- Stocks: `StockTransactionReportData`. Schema 1.
- Accounts: `ExpenseReportData`, `PayableReportData`, `PayablePaymentRecordedData`, `ReceivableReportData`, `ReceivablePaymentRecordedData`. Schema 1.
- Support: `SupportTicketReportData`, `TicketMessageCreatedData`. Schema 1.
- Production: `ProductionRequisitionReportData`. Schema 1.
- Procurement: `PurchaseOrderReportData` (+ item), `RequisitionReportData`, `GoodsReceivedReportData`, `SupplierReportData`. Schema 1.
- Catalogue, compatibility and behaviour test suites (104 tests).

### Not included
- Contracts for `procurement.purchase_return.*`, `procurement.rejected_item.recorded`, `procurement.goods_received.items_received`, `procurement.catalogue.generated`: no defined shape on the producer side yet.
