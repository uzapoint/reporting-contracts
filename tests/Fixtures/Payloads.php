<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests\Fixtures;

use Uzapoint\ReportingContracts\Accounts\ExpenseReportData;
use Uzapoint\ReportingContracts\Accounts\PayablePaymentRecordedData;
use Uzapoint\ReportingContracts\Accounts\PayableReportData;
use Uzapoint\ReportingContracts\Accounts\ReceivablePaymentRecordedData;
use Uzapoint\ReportingContracts\Accounts\ReceivableReportData;
use Uzapoint\ReportingContracts\Procurement\GoodsReceivedReportData;
use Uzapoint\ReportingContracts\Procurement\PurchaseOrderReportData;
use Uzapoint\ReportingContracts\Procurement\RequisitionReportData;
use Uzapoint\ReportingContracts\Procurement\SupplierReportData;
use Uzapoint\ReportingContracts\Production\ProductionRequisitionReportData;
use Uzapoint\ReportingContracts\Products\ProductDeactivatedData;
use Uzapoint\ReportingContracts\Products\ProductReportData;
use Uzapoint\ReportingContracts\Products\ProductSkuChangedData;
use Uzapoint\ReportingContracts\Sales\CreditNoteReportData;
use Uzapoint\ReportingContracts\Sales\InvoiceReportData;
use Uzapoint\ReportingContracts\Sales\QuotationReportData;
use Uzapoint\ReportingContracts\Sales\SalePaymentRecordedData;
use Uzapoint\ReportingContracts\Sales\SaleReportData;
use Uzapoint\ReportingContracts\Sales\VoidedSaleItemReportData;
use Uzapoint\ReportingContracts\Stocks\StockTransactionReportData;
use Uzapoint\ReportingContracts\Support\SupportTicketReportData;
use Uzapoint\ReportingContracts\Support\TicketMessageCreatedData;

/**
 * One valid wire payload per publishable contract. These are the inputs a
 * producer would hand to ::from() (or build via the constructor), written in
 * the shapes the Reports MS handlers read.
 */
final class Payloads
{
    /**
     * @return array<class-string<\Uzapoint\ReportingContracts\ReportData>, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            SaleReportData::class => self::sale(),
            SalePaymentRecordedData::class => [
                'sale_id' => 9001, 'business_id' => 12, 'amount_paid' => 1500.25, 'remaining_amount' => 499.75,
                'is_fully_paid' => false, 'payment_id' => 77, 'amount' => 500.25, 'payment_method' => 'Mpesa',
                'paid_at' => '2026-10-13T14:02:00+03:00', 'user_id' => 3,
            ],
            VoidedSaleItemReportData::class => [
                'sale_item_id' => 5551, 'sale_id' => 9001, 'business_id' => 12, 'voided_at' => '2026-10-13T15:00:00+03:00',
                'product_code' => 'KE-SODA-500', 'product_name' => 'Soda 500ml', 'qty_voided' => 1, 'unit_price' => 80.5,
                'line_total' => 80.5, 'void_reason' => 'Customer changed mind', 'voided_by' => 3, 'terminal_id' => 2,
            ],
            InvoiceReportData::class => [
                'invoice_id' => 301, 'business_id' => 12, 'invoice_number' => 'INV-0301', 'status' => 'approved',
                'invoice_date' => '2026-10-10', 'due_date' => '2026-11-09', 'grand_total' => 11600.5, 'sub_total' => 10000.5,
                'tax_total' => 1600.0, 'customer_id' => 44, 'customer_name' => 'Acme Ltd', 'created_by' => 3, 'approved_by' => 5,
                'items' => [['sku' => 'KE-SODA-500', 'product_name' => 'Soda 500ml', 'quantity' => 100, 'unit_price' => 100.005, 'line_total' => 10000.5, 'tax_amount' => 1600.0]],
            ],
            QuotationReportData::class => [
                'quotation_id' => 88, 'business_id' => 12, 'quote_number' => 'QT-0088', 'status' => 'sent', 'grand_total' => 2320.5,
                'items' => [['sku' => 'KE-SODA-500', 'product_name' => 'Soda 500ml', 'quantity' => 20, 'unit_price' => 100.0, 'line_total' => 2000.5]],
            ],
            CreditNoteReportData::class => [
                'credit_note_id' => 17, 'business_id' => 12, 'credit_note_number' => 'CN-0017', 'total_amount' => 161.5,
                'sale_id' => 9001, 'issued_by' => 3, 'issued_at' => '2026-10-13T16:00:00+03:00',
                'items' => [['sku' => 'KE-SODA-500', 'quantity' => 2, 'unit_cost' => 55.25]],
            ],
            ProductReportData::class => [
                'product_id' => 700, 'business_id' => 12, 'name' => 'Soda 500ml', 'category_name' => 'Beverages',
                'tracking_method' => 'none', 'is_active' => true, 'created_by' => 3,
                'skus' => [['sku' => 'KE-SODA-500', 'price' => 80.5, 'barcode' => '6001234567890']],
            ],
            ProductDeactivatedData::class => [
                'product_id' => 700, 'business_id' => 12, 'deactivated_at' => '2026-10-13T09:00:00+03:00', 'name' => 'Soda 500ml', 'user_id' => 3,
            ],
            ProductSkuChangedData::class => [
                'id' => 9100, 'product_id' => 700, 'business_id' => 12, 'sku' => 'KE-SODA-500', 'action' => 'updated', 'price' => 85.5,
            ],
            StockTransactionReportData::class => [
                'transaction_id' => 40001, 'business_id' => 12, 'location_id' => 1, 'sku' => 'KE-SODA-500', 'type' => 'out',
                'quantity_change' => -2, 'quantity_before' => 50, 'quantity_after' => 48, 'created_at' => '2026-10-13T14:00:00+03:00',
                'inventory_item_id' => 310, 'sku_name' => 'Soda 500ml', 'category_name' => 'Beverages', 'reference_id' => 9001,
                'reference_type' => 'sale', 'user_id' => 3, 'reorder_level' => 10, 'cost_price' => 55.25,
            ],
            ExpenseReportData::class => [
                'expense_id' => 1201, 'business_id' => 12, 'action' => 'created', 'amount' => 2500.5, 'tax_amount' => 0.0,
                'net_amount' => 2500.5, 'expense_date' => '2026-10-13', 'occurred_at' => '2026-10-13T11:30:00+03:00',
                'expense_ref' => 'EXP-1201', 'category' => 'Transport', 'payment_method' => 'cash', 'requires_approval' => false,
                'is_approved' => false, 'is_void' => false, 'created_by_user_id' => 3, 'created_by_user_name' => 'Wanjiku',
            ],
            PayableReportData::class => [
                'payable_id' => 501, 'business_id' => 12, 'total_amount' => 58000.5, 'amount_paid' => 0, 'status' => 'open',
                'date_due' => '2026-11-12', 'po_id' => 61, 'po_number' => 'PO-0061', 'vendor_id' => 9, 'vendor_name' => 'Coastal Suppliers',
                'created_by_user_id' => 3, 'created_by_user_name' => 'Wanjiku',
            ],
            PayablePaymentRecordedData::class => [
                'payable_id' => 501, 'business_id' => 12, 'new_paid_amount' => 58000.5, 'is_fully_paid' => true, 'status' => 'paid', 'payment_id' => 12,
            ],
            ReceivableReportData::class => [
                'receivable_id' => 801, 'business_id' => 12, 'total_amount' => 11600.5, 'amount_paid' => 0, 'amount_due' => 11600.5,
                'status' => 'open', 'invoice_id' => 301, 'invoice_number' => 'INV-0301', 'customer_id' => 44, 'customer_name' => 'Acme Ltd', 'user_id' => 3,
            ],
            ReceivablePaymentRecordedData::class => [
                'receivable_id' => 801, 'business_id' => 12, 'new_paid_amount' => 5000.25, 'remaining_amount' => 6600.25, 'is_fully_paid' => false, 'payment_id' => 31,
            ],
            SupportTicketReportData::class => [
                'id' => 2201, 'business_id' => 12, 'action' => 'created', 'status' => 'open', 'subject' => 'Printer offline',
                'priority' => 'high', 'user_id' => 3, 'ticket_category_id' => 4, 'opened_at' => '2026-10-13T08:00:00+03:00',
            ],
            TicketMessageCreatedData::class => [
                'message_id' => 31001, 'ticket_id' => 2201, 'business_id' => 12, 'is_admin' => true, 'created_at' => '2026-10-13T08:15:00+03:00', 'user_id' => 10,
            ],
            ProductionRequisitionReportData::class => [
                'requisition_id' => 15, 'business_id' => 12, 'stage' => 'synced', 'status' => 'Approved', 'created_at' => '2026-10-12T10:00:00+03:00',
                'ref_no' => 'REQ-15', 'store_from_id' => 1, 'store_to_id' => 2, 'total_raw_material_cost' => 1200.5, 'total_production_cost' => 1500.75,
                'created_by_user_id' => 3, 'finished_goods' => [['sku' => 'BREAD-400', 'quantity' => 100]], 'raw_materials' => [['sku' => 'FLOUR-2KG', 'quantity' => 25]],
            ],
            PurchaseOrderReportData::class => [
                'po_id' => 61, 'business_id' => 12, 'action' => 'created', 'status' => 'draft', 'ref_no' => 'PO-0061', 'supplier_id' => 9,
                'supplier_name' => 'Coastal Suppliers', 'total_value' => 58000.5, 'order_date' => '2026-10-11T09:00:00+03:00', 'created_by' => 3,
                'items' => [['product_id' => 700, 'sku' => 'KE-SODA-500', 'product_name' => 'Soda 500ml', 'quantity_ordered' => 1000, 'unit_price' => 58.0005, 'total_line_value' => 58000.5]],
            ],
            RequisitionReportData::class => [
                'requisition_id' => 71, 'business_id' => 12, 'action' => 'approved', 'status' => 'approved', 'ref_no' => 'REQ-0071',
                'total_value' => 58000.5, 'created_at' => '2026-10-10T09:00:00+03:00', 'requested_by' => 3, 'approved_by' => 5,
            ],
            GoodsReceivedReportData::class => [
                'grn_id' => '01JA0ZC9R6F3Q7Y8K2M4N5P6Q7', 'business_id' => 12, 'po_id' => 61, 'supplier_id' => 9, 'total_quantity_accepted' => 990,
                'total_value_accepted' => 57420.5, 'created_at' => '2026-10-12T15:00:00+03:00', 'grn_number' => 'GRN-0012', 'po_ref_no' => 'PO-0061',
                'total_quantity_rejected' => 10, 'received_by' => 3, 'items' => [['sku' => 'KE-SODA-500', 'quantity_accepted' => 990, 'quantity_rejected' => 10]],
            ],
            SupplierReportData::class => [
                'id' => 9, 'business_id' => 12, 'action' => 'created', 'name' => 'Coastal Suppliers', 'created_at' => '2026-10-01T09:00:00+03:00',
                'code' => 'CS-01', 'phone' => '+254700000000', 'status' => 'active', 'user_id' => 3,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function sale(): array
    {
        return [
            'id' => 9001,
            'business_id' => 12,
            'sale_number' => 'S-2026-9001',
            'sale_date' => '2026-10-13T13:45:00+03:00',
            'status' => 'completed',
            'payment_status' => 'fully_paid',
            'grand_total' => 2000.5,
            'sub_total' => 1724.57,
            'tax_total' => 275.93,
            'discount_total' => 0.0,
            'location_id' => 1,
            'terminal_id' => 2,
            'shift_id' => 14,
            'customer_id' => 44,
            'customer_name' => 'Acme Ltd',
            'customer_phone' => '+254711000000',
            'user_id' => 3,
            'user_name' => 'Wanjiku',
            'currency' => 'KES',
            'items' => [
                [
                    'product_sku_id' => 9100, 'product_code' => 'KE-SODA-500', 'product_name' => 'Soda 500ml',
                    'category_id' => 6, 'category_name' => 'Beverages', 'quantity' => 20, 'unit_price' => 80.5,
                    'unit_cost' => 55.25, 'line_subtotal' => 1610.0, 'line_total' => 1610.0, 'line_tax_total' => 222.07,
                ],
                [
                    'product_code' => 'KE-BREAD-400', 'product_name' => 'Bread 400g', 'quantity' => 5, 'unit_price' => 78.1,
                    'line_total' => 390.5, 'tracking_method' => 'batch', 'batch_number' => 'B-2026-10-12', 'expiry_date' => '2026-10-16',
                ],
            ],
            'payments' => [
                ['payment_method' => 'Mpesa', 'amount' => 2000.5, 'payment_date' => '2026-10-13T13:45:30+03:00'],
            ],
            'sale_users' => [
                ['user_id' => 3, 'user_name' => 'Wanjiku'],
            ],
        ];
    }
}
