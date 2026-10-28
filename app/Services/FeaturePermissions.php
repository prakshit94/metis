<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\HrPermissions;

/** Compatibility grants for actions formerly covered by a broader permission. */
final class FeaturePermissions
{
    public const LEGACY = [
        'office-view' => ['team-view'],
        'office-create' => ['team-create'],
        'office-edit' => ['team-edit'],
        'office-delete' => ['team-delete'],
        'product-export' => ['product-view'],
        'orders.view.unfulfillable' => ['orders.view.pending'],
        'orders.view.delivery_attempted' => ['orders.view.dispatched'],
        'orders.shipping_label' => ['orders.ship', 'orders.bulk_print'],
        'reports-export' => ['reports-view'],
        'target-import' => ['target-create'],
        'files-view' => ['settings-view'], 'files-create' => ['settings-view'],
        'files-edit' => ['settings-view'], 'files-delete' => ['settings-view'], 'files-download' => ['settings-view'],
        'calltag-view' => ['settings-view'], 'calltag-create' => ['settings-view'],
        'calltag-edit' => ['settings-view'], 'calltag-delete' => ['settings-view'],
        'calllog-create' => ['customer-view', 'orders.create'],
        'hrsetting-view' => ['department-view'], 'hrsetting-create' => ['department-create'],
        'hrsetting-edit' => ['department-edit'], 'hrsetting-delete' => ['department-delete'],
        'holiday-view' => ['leave-view'], 'holiday-create' => ['leave-create'],
        'holiday-edit' => ['leave-edit'], 'holiday-delete' => ['leave-delete'],
        'leavebalance-view' => ['leave-view'], 'leavebalance-create' => ['leave-create'],
        'leavebalance-edit' => ['leave-edit'], 'leavebalance-delete' => ['leave-delete'],
        'purchaseorder-invoice-upload' => ['purchaseorder-create'], 'purchaseorder-payment-create' => ['purchaseorder-create'],
        'inventoryadjustment-approve' => ['inventoryadjustment-edit'],
        'stocktransfer-send' => ['stocktransfer-edit'], 'stocktransfer-receive' => ['stocktransfer-edit'], 'stocktransfer-cancel' => ['stocktransfer-edit'],
        'invoices.view' => ['orders.view'], 'invoices.edit' => ['orders.receipt'],
        'invoices.export' => ['orders.view'], 'invoices.pdf' => ['orders.view'], 'invoices.record-payment' => ['orders.receipt'],
        'payments.view' => ['orders.view'], 'payments.create' => ['orders.receipt'],
        'payments.edit' => ['orders.receipt'], 'payments.delete' => ['orders.receipt'],
        'payments.import' => ['orders.receipt'], 'payments.export' => ['orders.view'],
        'refunds.view' => ['orders.view'], 'refunds.edit' => ['orders.edit'],
        'credit-notes.view' => ['orders.view'], 'credit-notes.create' => ['orders.receipt'],
        'credit-notes.edit' => ['orders.receipt'], 'credit-notes.delete' => ['orders.receipt'],
        'returns.view' => ['orders.view'], 'returns.create' => ['orders.return'],
        'returns.process' => ['orders.return'], 'returns.qc' => ['orders.return'], 'returns.finance' => ['orders.return'],
        'returns.approve' => ['orders.return'], 'returns.cancel' => ['orders.return'],
        'messages-view' => ['chat-view'], 'messages-create' => ['chat-create'],
        'messages-edit' => ['chat-edit'], 'messages-delete' => ['chat-delete'],
        'chat-group-manage' => ['chat-edit'],
        'user-security-requirement' => ['settings-edit'],
        'security-mail-view' => ['settings-edit'], 'security-mail-edit' => ['settings-edit'], 'security-mail-test' => ['settings-edit'],
    ];

    public const BULK_ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'cancelled', 'returned'];

    public const REPORTS = [
        'sales_overview', 'india_post', 'product_sales', 'sales_region', 'payment_reconciliation',
        'stock_valuation', 'stock_ledger', 'low_stock', 'po_fulfillment', 'grn_discrepancy',
        'call_performance', 'call_tagging', 'customer_retention', 'return_analysis', 'audit_trail',
        'suppliers_report', 'attendance_report',
    ];

    public static function legacy(): array
    {
        $legacy = self::LEGACY;
        foreach (self::BULK_ORDER_STATUSES as $status) $legacy['orders.bulk_status.'.$status] = ['orders.bulk_status'];
        foreach (self::REPORTS as $report) $legacy['reports.export.'.$report] = ['reports-view'];
        return $legacy;
    }

    public static function expand(array $permissions): array
    {
        $permissions = HrPermissions::expand($permissions);
        $original = $permissions;
        foreach (self::legacy() as $action => $legacy) {
            if (array_intersect($legacy, $original)) $permissions[] = $action;
        }
        return array_values(array_unique($permissions));
    }
}
