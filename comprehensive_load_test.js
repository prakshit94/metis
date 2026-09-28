import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 255,
    duration: '30s',
};

const BASE_URL = 'http://localhost:8000';
const TOKEN = __ENV.TOKEN || '';

export default function () {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
    if (TOKEN) {
        headers['Authorization'] = `Bearer ${TOKEN}`;
    }
    const params = { headers: headers };

    // Route: dashboard
    {
        let res = http.get(`${BASE_URL}//`, params);
        check(res, { 'status is 200 or 4xx for dashboard': (r) => r.status < 500 });
    }

    // Route: scramble.dev-tools.asset
    {
        let res = http.get(`${BASE_URL}/_scramble/dev-tools/devtools.js`, params);
        check(res, { 'status is 200 or 4xx for scramble.dev-tools.asset': (r) => r.status < 500 });
    }

    // Route: admin.audit-logs.index
    {
        let res = http.get(`${BASE_URL}/admin/audit-logs`, params);
        check(res, { 'status is 200 or 4xx for admin.audit-logs.index': (r) => r.status < 500 });
    }

    // Route: analytics
    {
        let res = http.get(`${BASE_URL}/analytics`, params);
        check(res, { 'status is 200 or 4xx for analytics': (r) => r.status < 500 });
    }

    // Route: analytics.data
    {
        let res = http.get(`${BASE_URL}/analytics/data`, params);
        check(res, { 'status is 200 or 4xx for analytics.data': (r) => r.status < 500 });
    }

    // Route: api.activities.recent
    {
        let res = http.get(`${BASE_URL}/api/activities/recent`, params);
        check(res, { 'status is 200 or 4xx for api.activities.recent': (r) => r.status < 500 });
    }

    // Route: api.audit-logs.index
    {
        let res = http.get(`${BASE_URL}/api/admin/audit-logs`, params);
        check(res, { 'status is 200 or 4xx for api.audit-logs.index': (r) => r.status < 500 });
    }

    // Route: attendances.index
    {
        let res = http.get(`${BASE_URL}/api/attendances`, params);
        check(res, { 'status is 200 or 4xx for attendances.index': (r) => r.status < 500 });
    }

    // Route: api/attendances/export/detailed
    {
        let res = http.get(`${BASE_URL}/api/attendances/export/detailed`, params);
        check(res, { 'status is 200 or 4xx for api/attendances/export/detailed': (r) => r.status < 500 });
    }

    // Route: api/attendances/export/summary
    {
        let res = http.get(`${BASE_URL}/api/attendances/export/summary`, params);
        check(res, { 'status is 200 or 4xx for api/attendances/export/summary': (r) => r.status < 500 });
    }

    // Route: attendances.show
    {
        let res = http.get(`${BASE_URL}/api/attendances/1`, params);
        check(res, { 'status is 200 or 4xx for attendances.show': (r) => r.status < 500 });
    }

    // Route: attributes.index
    {
        let res = http.get(`${BASE_URL}/api/attributes`, params);
        check(res, { 'status is 200 or 4xx for attributes.index': (r) => r.status < 500 });
    }

    // Route: attributes.show
    {
        let res = http.get(`${BASE_URL}/api/attributes/1`, params);
        check(res, { 'status is 200 or 4xx for attributes.show': (r) => r.status < 500 });
    }

    // Route: brands.index
    {
        let res = http.get(`${BASE_URL}/api/brands`, params);
        check(res, { 'status is 200 or 4xx for brands.index': (r) => r.status < 500 });
    }

    // Route: brands.show
    {
        let res = http.get(`${BASE_URL}/api/brands/1`, params);
        check(res, { 'status is 200 or 4xx for brands.show': (r) => r.status < 500 });
    }

    // Route: api/call-tags
    {
        let res = http.get(`${BASE_URL}/api/call-tags`, params);
        check(res, { 'status is 200 or 4xx for api/call-tags': (r) => r.status < 500 });
    }

    // Route: api.call-tags.index
    {
        let res = http.get(`${BASE_URL}/api/call-tags-admin`, params);
        check(res, { 'status is 200 or 4xx for api.call-tags.index': (r) => r.status < 500 });
    }

    // Route: api/call-tags/1/form
    {
        let res = http.get(`${BASE_URL}/api/call-tags/1/form`, params);
        check(res, { 'status is 200 or 4xx for api/call-tags/1/form': (r) => r.status < 500 });
    }

    // Route: categories.index
    {
        let res = http.get(`${BASE_URL}/api/categories`, params);
        check(res, { 'status is 200 or 4xx for categories.index': (r) => r.status < 500 });
    }

    // Route: categories.show
    {
        let res = http.get(`${BASE_URL}/api/categories/1`, params);
        check(res, { 'status is 200 or 4xx for categories.show': (r) => r.status < 500 });
    }

    // Route: api/chat/active-status
    {
        let res = http.get(`${BASE_URL}/api/chat/active-status`, params);
        check(res, { 'status is 200 or 4xx for api/chat/active-status': (r) => r.status < 500 });
    }

    // Route: api/chat/conversations
    {
        let res = http.get(`${BASE_URL}/api/chat/conversations`, params);
        check(res, { 'status is 200 or 4xx for api/chat/conversations': (r) => r.status < 500 });
    }

    // Route: api/chat/conversations/1
    {
        let res = http.get(`${BASE_URL}/api/chat/conversations/1`, params);
        check(res, { 'status is 200 or 4xx for api/chat/conversations/1': (r) => r.status < 500 });
    }

    // Route: api/chat/conversations/1/messages
    {
        let res = http.get(`${BASE_URL}/api/chat/conversations/1/messages`, params);
        check(res, { 'status is 200 or 4xx for api/chat/conversations/1/messages': (r) => r.status < 500 });
    }

    // Route: api/chat/search
    {
        let res = http.get(`${BASE_URL}/api/chat/search`, params);
        check(res, { 'status is 200 or 4xx for api/chat/search': (r) => r.status < 500 });
    }

    // Route: api/chat/unread-count
    {
        let res = http.get(`${BASE_URL}/api/chat/unread-count`, params);
        check(res, { 'status is 200 or 4xx for api/chat/unread-count': (r) => r.status < 500 });
    }

    // Route: api/chat/users
    {
        let res = http.get(`${BASE_URL}/api/chat/users`, params);
        check(res, { 'status is 200 or 4xx for api/chat/users': (r) => r.status < 500 });
    }

    // Route: api.complaints.index
    {
        let res = http.get(`${BASE_URL}/api/complaints`, params);
        check(res, { 'status is 200 or 4xx for api.complaints.index': (r) => r.status < 500 });
    }

    // Route: api.complaints.export
    {
        let res = http.get(`${BASE_URL}/api/complaints/export`, params);
        check(res, { 'status is 200 or 4xx for api.complaints.export': (r) => r.status < 500 });
    }

    // Route: api.complaints.stats
    {
        let res = http.get(`${BASE_URL}/api/complaints/stats`, params);
        check(res, { 'status is 200 or 4xx for api.complaints.stats': (r) => r.status < 500 });
    }

    // Route: api.complaints.show
    {
        let res = http.get(`${BASE_URL}/api/complaints/1`, params);
        check(res, { 'status is 200 or 4xx for api.complaints.show': (r) => r.status < 500 });
    }

    // Route: api.credit-notes.index
    {
        let res = http.get(`${BASE_URL}/api/credit-notes`, params);
        check(res, { 'status is 200 or 4xx for api.credit-notes.index': (r) => r.status < 500 });
    }

    // Route: api/customer-settings/1
    {
        let res = http.get(`${BASE_URL}/api/customer-settings/1`, params);
        check(res, { 'status is 200 or 4xx for api/customer-settings/1': (r) => r.status < 500 });
    }

    // Route: api.customers.index
    {
        let res = http.get(`${BASE_URL}/api/customers`, params);
        check(res, { 'status is 200 or 4xx for api.customers.index': (r) => r.status < 500 });
    }

    // Route: api.customers.check-referral
    {
        let res = http.get(`${BASE_URL}/api/customers/check-referral/1`, params);
        check(res, { 'status is 200 or 4xx for api.customers.check-referral': (r) => r.status < 500 });
    }

    // Route: api.customers.show
    {
        let res = http.get(`${BASE_URL}/api/customers/1`, params);
        check(res, { 'status is 200 or 4xx for api.customers.show': (r) => r.status < 500 });
    }

    // Route: api.dashboard
    {
        let res = http.get(`${BASE_URL}/api/dashboard`, params);
        check(res, { 'status is 200 or 4xx for api.dashboard': (r) => r.status < 500 });
    }

    // Route: api/departments
    {
        let res = http.get(`${BASE_URL}/api/departments`, params);
        check(res, { 'status is 200 or 4xx for api/departments': (r) => r.status < 500 });
    }

    // Route: api/departments/1
    {
        let res = http.get(`${BASE_URL}/api/departments/1`, params);
        check(res, { 'status is 200 or 4xx for api/departments/1': (r) => r.status < 500 });
    }

    // Route: api/files
    {
        let res = http.get(`${BASE_URL}/api/files`, params);
        check(res, { 'status is 200 or 4xx for api/files': (r) => r.status < 500 });
    }

    // Route: holidays.index
    {
        let res = http.get(`${BASE_URL}/api/holidays`, params);
        check(res, { 'status is 200 or 4xx for holidays.index': (r) => r.status < 500 });
    }

    // Route: holidays.show
    {
        let res = http.get(`${BASE_URL}/api/holidays/1`, params);
        check(res, { 'status is 200 or 4xx for holidays.show': (r) => r.status < 500 });
    }

    // Route: api/hr-settings/1
    {
        let res = http.get(`${BASE_URL}/api/hr-settings/1`, params);
        check(res, { 'status is 200 or 4xx for api/hr-settings/1': (r) => r.status < 500 });
    }

    // Route: hsn-codes.index
    {
        let res = http.get(`${BASE_URL}/api/hsn-codes`, params);
        check(res, { 'status is 200 or 4xx for hsn-codes.index': (r) => r.status < 500 });
    }

    // Route: hsn-codes.show
    {
        let res = http.get(`${BASE_URL}/api/hsn-codes/1`, params);
        check(res, { 'status is 200 or 4xx for hsn-codes.show': (r) => r.status < 500 });
    }

    // Route: api.inventory.adjustments.index
    {
        let res = http.get(`${BASE_URL}/api/inventory/adjustments`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.adjustments.index': (r) => r.status < 500 });
    }

    // Route: api.inventory.adjustments.options
    {
        let res = http.get(`${BASE_URL}/api/inventory/adjustments/options`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.adjustments.options': (r) => r.status < 500 });
    }

    // Route: api.inventory.adjustments.show
    {
        let res = http.get(`${BASE_URL}/api/inventory/adjustments/1`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.adjustments.show': (r) => r.status < 500 });
    }

    // Route: api.inventory.stocks.index
    {
        let res = http.get(`${BASE_URL}/api/inventory/stocks`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.stocks.index': (r) => r.status < 500 });
    }

    // Route: api.inventory.stocks.show
    {
        let res = http.get(`${BASE_URL}/api/inventory/stocks/show`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.stocks.show': (r) => r.status < 500 });
    }

    // Route: api.inventory.stocks.warehouse-options
    {
        let res = http.get(`${BASE_URL}/api/inventory/stocks/warehouse-options`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.stocks.warehouse-options': (r) => r.status < 500 });
    }

    // Route: api.inventory.transfers.index
    {
        let res = http.get(`${BASE_URL}/api/inventory/transfers`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.transfers.index': (r) => r.status < 500 });
    }

    // Route: api.inventory.transfers.options
    {
        let res = http.get(`${BASE_URL}/api/inventory/transfers/options`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.transfers.options': (r) => r.status < 500 });
    }

    // Route: api.inventory.transfers.show
    {
        let res = http.get(`${BASE_URL}/api/inventory/transfers/1`, params);
        check(res, { 'status is 200 or 4xx for api.inventory.transfers.show': (r) => r.status < 500 });
    }

    // Route: api.invoices.index
    {
        let res = http.get(`${BASE_URL}/api/invoices`, params);
        check(res, { 'status is 200 or 4xx for api.invoices.index': (r) => r.status < 500 });
    }

    // Route: api.invoices.show
    {
        let res = http.get(`${BASE_URL}/api/invoices/1`, params);
        check(res, { 'status is 200 or 4xx for api.invoices.show': (r) => r.status < 500 });
    }

    // Route: api.invoices.pdf
    {
        let res = http.get(`${BASE_URL}/api/invoices/1/pdf`, params);
        check(res, { 'status is 200 or 4xx for api.invoices.pdf': (r) => r.status < 500 });
    }

    // Route: leave-balances.index
    {
        let res = http.get(`${BASE_URL}/api/leave-balances`, params);
        check(res, { 'status is 200 or 4xx for leave-balances.index': (r) => r.status < 500 });
    }

    // Route: leave-balances.show
    {
        let res = http.get(`${BASE_URL}/api/leave-balances/1`, params);
        check(res, { 'status is 200 or 4xx for leave-balances.show': (r) => r.status < 500 });
    }

    // Route: api/leaves
    {
        let res = http.get(`${BASE_URL}/api/leaves`, params);
        check(res, { 'status is 200 or 4xx for api/leaves': (r) => r.status < 500 });
    }

    // Route: api/leaves/1
    {
        let res = http.get(`${BASE_URL}/api/leaves/1`, params);
        check(res, { 'status is 200 or 4xx for api/leaves/1': (r) => r.status < 500 });
    }

    // Route: api.order-reasons.list
    {
        let res = http.get(`${BASE_URL}/api/order-reasons/1`, params);
        check(res, { 'status is 200 or 4xx for api.order-reasons.list': (r) => r.status < 500 });
    }

    // Route: api.orders.index
    {
        let res = http.get(`${BASE_URL}/api/orders`, params);
        check(res, { 'status is 200 or 4xx for api.orders.index': (r) => r.status < 500 });
    }

    // Route: api.orders.bulk-print
    {
        let res = http.get(`${BASE_URL}/api/orders/bulk-print`, params);
        check(res, { 'status is 200 or 4xx for api.orders.bulk-print': (r) => r.status < 500 });
    }

    // Route: api.orders.export
    {
        let res = http.get(`${BASE_URL}/api/orders/export`, params);
        check(res, { 'status is 200 or 4xx for api.orders.export': (r) => r.status < 500 });
    }

    // Route: api.orders.show
    {
        let res = http.get(`${BASE_URL}/api/orders/1`, params);
        check(res, { 'status is 200 or 4xx for api.orders.show': (r) => r.status < 500 });
    }

    // Route: api/org-chart
    {
        let res = http.get(`${BASE_URL}/api/org-chart`, params);
        check(res, { 'status is 200 or 4xx for api/org-chart': (r) => r.status < 500 });
    }

    // Route: api.payments.index
    {
        let res = http.get(`${BASE_URL}/api/payments`, params);
        check(res, { 'status is 200 or 4xx for api.payments.index': (r) => r.status < 500 });
    }

    // Route: api.payments.show
    {
        let res = http.get(`${BASE_URL}/api/payments/1`, params);
        check(res, { 'status is 200 or 4xx for api.payments.show': (r) => r.status < 500 });
    }

    // Route: api.permissions.index
    {
        let res = http.get(`${BASE_URL}/api/permissions`, params);
        check(res, { 'status is 200 or 4xx for api.permissions.index': (r) => r.status < 500 });
    }

    // Route: api.permissions.options
    {
        let res = http.get(`${BASE_URL}/api/permissions/options`, params);
        check(res, { 'status is 200 or 4xx for api.permissions.options': (r) => r.status < 500 });
    }

    // Route: api.permissions.show
    {
        let res = http.get(`${BASE_URL}/api/permissions/1`, params);
        check(res, { 'status is 200 or 4xx for api.permissions.show': (r) => r.status < 500 });
    }

    // Route: api.procurement.goods-receipts.index
    {
        let res = http.get(`${BASE_URL}/api/procurement/goods-receipts`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.goods-receipts.index': (r) => r.status < 500 });
    }

    // Route: api.procurement.goods-receipts.pdf
    {
        let res = http.get(`${BASE_URL}/api/procurement/goods-receipts/1/pdf`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.goods-receipts.pdf': (r) => r.status < 500 });
    }

    // Route: api.procurement.purchase-orders.index
    {
        let res = http.get(`${BASE_URL}/api/procurement/purchase-orders`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.purchase-orders.index': (r) => r.status < 500 });
    }

    // Route: api.procurement.purchase-orders.pdf
    {
        let res = http.get(`${BASE_URL}/api/procurement/purchase-orders/1/pdf`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.purchase-orders.pdf': (r) => r.status < 500 });
    }

    // Route: api.procurement.suppliers.index
    {
        let res = http.get(`${BASE_URL}/api/procurement/suppliers`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.suppliers.index': (r) => r.status < 500 });
    }

    // Route: api.procurement.suppliers.show
    {
        let res = http.get(`${BASE_URL}/api/procurement/suppliers/1`, params);
        check(res, { 'status is 200 or 4xx for api.procurement.suppliers.show': (r) => r.status < 500 });
    }

    // Route: api.products.index
    {
        let res = http.get(`${BASE_URL}/api/products`, params);
        check(res, { 'status is 200 or 4xx for api.products.index': (r) => r.status < 500 });
    }

    // Route: api.products.search.api
    {
        let res = http.get(`${BASE_URL}/api/products-search-api`, params);
        check(res, { 'status is 200 or 4xx for api.products.search.api': (r) => r.status < 500 });
    }

    // Route: api.products.export
    {
        let res = http.get(`${BASE_URL}/api/products/export`, params);
        check(res, { 'status is 200 or 4xx for api.products.export': (r) => r.status < 500 });
    }

    // Route: api.products.show
    {
        let res = http.get(`${BASE_URL}/api/products/1`, params);
        check(res, { 'status is 200 or 4xx for api.products.show': (r) => r.status < 500 });
    }

    // Route: api.promotions.coupons.index
    {
        let res = http.get(`${BASE_URL}/api/promotions/coupons`, params);
        check(res, { 'status is 200 or 4xx for api.promotions.coupons.index': (r) => r.status < 500 });
    }

    // Route: api.promotions.offers.index
    {
        let res = http.get(`${BASE_URL}/api/promotions/offers`, params);
        check(res, { 'status is 200 or 4xx for api.promotions.offers.index': (r) => r.status < 500 });
    }

    // Route: api.promotions.referrals.programs.index
    {
        let res = http.get(`${BASE_URL}/api/promotions/referral-programs`, params);
        check(res, { 'status is 200 or 4xx for api.promotions.referrals.programs.index': (r) => r.status < 500 });
    }

    // Route: api.refunds.index
    {
        let res = http.get(`${BASE_URL}/api/refunds`, params);
        check(res, { 'status is 200 or 4xx for api.refunds.index': (r) => r.status < 500 });
    }

    // Route: api.refunds.show
    {
        let res = http.get(`${BASE_URL}/api/refunds/1`, params);
        check(res, { 'status is 200 or 4xx for api.refunds.show': (r) => r.status < 500 });
    }

    // Route: api.reports
    {
        let res = http.get(`${BASE_URL}/api/reports`, params);
        check(res, { 'status is 200 or 4xx for api.reports': (r) => r.status < 500 });
    }

    // Route: api.returns.index
    {
        let res = http.get(`${BASE_URL}/api/returns`, params);
        check(res, { 'status is 200 or 4xx for api.returns.index': (r) => r.status < 500 });
    }

    // Route: api.returns.show
    {
        let res = http.get(`${BASE_URL}/api/returns/1`, params);
        check(res, { 'status is 200 or 4xx for api.returns.show': (r) => r.status < 500 });
    }

    // Route: api.roles.index
    {
        let res = http.get(`${BASE_URL}/api/roles`, params);
        check(res, { 'status is 200 or 4xx for api.roles.index': (r) => r.status < 500 });
    }

    // Route: api.roles.options
    {
        let res = http.get(`${BASE_URL}/api/roles/options`, params);
        check(res, { 'status is 200 or 4xx for api.roles.options': (r) => r.status < 500 });
    }

    // Route: api.roles.show
    {
        let res = http.get(`${BASE_URL}/api/roles/1`, params);
        check(res, { 'status is 200 or 4xx for api.roles.show': (r) => r.status < 500 });
    }

    // Route: api/security/activity
    {
        let res = http.get(`${BASE_URL}/api/security/activity`, params);
        check(res, { 'status is 200 or 4xx for api/security/activity': (r) => r.status < 500 });
    }

    // Route: api/security/sessions
    {
        let res = http.get(`${BASE_URL}/api/security/sessions`, params);
        check(res, { 'status is 200 or 4xx for api/security/sessions': (r) => r.status < 500 });
    }

    // Route: api.shipping.awb-logs
    {
        let res = http.get(`${BASE_URL}/api/shipping/awb-logs/1`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.awb-logs': (r) => r.status < 500 });
    }

    // Route: api.shipping.ranges.index
    {
        let res = http.get(`${BASE_URL}/api/shipping/offices/1/ranges`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.ranges.index': (r) => r.status < 500 });
    }

    // Route: api.shipping.services.index
    {
        let res = http.get(`${BASE_URL}/api/shipping/services`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.services.index': (r) => r.status < 500 });
    }

    // Route: api.shipping.services.provider-options
    {
        let res = http.get(`${BASE_URL}/api/shipping/services/provider-options`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.services.provider-options': (r) => r.status < 500 });
    }

    // Route: api.shipping.shipments.index
    {
        let res = http.get(`${BASE_URL}/api/shipping/shipments`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.shipments.index': (r) => r.status < 500 });
    }

    // Route: api.shipping.shipments.live-tracking
    {
        let res = http.get(`${BASE_URL}/api/shipping/shipments/1/live-tracking`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.shipments.live-tracking': (r) => r.status < 500 });
    }

    // Route: api.shipping.shipments.tracking
    {
        let res = http.get(`${BASE_URL}/api/shipping/shipments/1/tracking`, params);
        check(res, { 'status is 200 or 4xx for api.shipping.shipments.tracking': (r) => r.status < 500 });
    }

    // Route: tax-rates.index
    {
        let res = http.get(`${BASE_URL}/api/tax-rates`, params);
        check(res, { 'status is 200 or 4xx for tax-rates.index': (r) => r.status < 500 });
    }

    // Route: tax-rates.show
    {
        let res = http.get(`${BASE_URL}/api/tax-rates/1`, params);
        check(res, { 'status is 200 or 4xx for tax-rates.show': (r) => r.status < 500 });
    }

    // Route: api.teams.index
    {
        let res = http.get(`${BASE_URL}/api/teams`, params);
        check(res, { 'status is 200 or 4xx for api.teams.index': (r) => r.status < 500 });
    }

    // Route: api.teams.show
    {
        let res = http.get(`${BASE_URL}/api/teams/1`, params);
        check(res, { 'status is 200 or 4xx for api.teams.show': (r) => r.status < 500 });
    }

    // Route: uom.index
    {
        let res = http.get(`${BASE_URL}/api/uom`, params);
        check(res, { 'status is 200 or 4xx for uom.index': (r) => r.status < 500 });
    }

    // Route: uom.show
    {
        let res = http.get(`${BASE_URL}/api/uom/1`, params);
        check(res, { 'status is 200 or 4xx for uom.show': (r) => r.status < 500 });
    }

    // Route: api.users.index
    {
        let res = http.get(`${BASE_URL}/api/users`, params);
        check(res, { 'status is 200 or 4xx for api.users.index': (r) => r.status < 500 });
    }

    // Route: api.users.show
    {
        let res = http.get(`${BASE_URL}/api/users/1`, params);
        check(res, { 'status is 200 or 4xx for api.users.show': (r) => r.status < 500 });
    }

    // Route: api.users.login-history
    {
        let res = http.get(`${BASE_URL}/api/users/1/login-history`, params);
        check(res, { 'status is 200 or 4xx for api.users.login-history': (r) => r.status < 500 });
    }

    // Route: api.villages.index
    {
        let res = http.get(`${BASE_URL}/api/villages`, params);
        check(res, { 'status is 200 or 4xx for api.villages.index': (r) => r.status < 500 });
    }

    // Route: api.villages.export
    {
        let res = http.get(`${BASE_URL}/api/villages/export`, params);
        check(res, { 'status is 200 or 4xx for api.villages.export': (r) => r.status < 500 });
    }

    // Route: api.villages.import-template
    {
        let res = http.get(`${BASE_URL}/api/villages/import-template`, params);
        check(res, { 'status is 200 or 4xx for api.villages.import-template': (r) => r.status < 500 });
    }

    // Route: api.villages.search
    {
        let res = http.get(`${BASE_URL}/api/villages/search`, params);
        check(res, { 'status is 200 or 4xx for api.villages.search': (r) => r.status < 500 });
    }

    // Route: api.villages.services-options
    {
        let res = http.get(`${BASE_URL}/api/villages/services-options`, params);
        check(res, { 'status is 200 or 4xx for api.villages.services-options': (r) => r.status < 500 });
    }

    // Route: api.villages.show
    {
        let res = http.get(`${BASE_URL}/api/villages/1`, params);
        check(res, { 'status is 200 or 4xx for api.villages.show': (r) => r.status < 500 });
    }

    // Route: warehouses.index
    {
        let res = http.get(`${BASE_URL}/api/warehouses`, params);
        check(res, { 'status is 200 or 4xx for warehouses.index': (r) => r.status < 500 });
    }

    // Route: warehouses.show
    {
        let res = http.get(`${BASE_URL}/api/warehouses/1`, params);
        check(res, { 'status is 200 or 4xx for warehouses.show': (r) => r.status < 500 });
    }

    // Route: attendances
    {
        let res = http.get(`${BASE_URL}/attendances`, params);
        check(res, { 'status is 200 or 4xx for attendances': (r) => r.status < 500 });
    }

    // Route: calendar
    {
        let res = http.get(`${BASE_URL}/calendar`, params);
        check(res, { 'status is 200 or 4xx for calendar': (r) => r.status < 500 });
    }

    // Route: call-tags
    {
        let res = http.get(`${BASE_URL}/call-tags`, params);
        check(res, { 'status is 200 or 4xx for call-tags': (r) => r.status < 500 });
    }

    // Route: call-tags.index
    {
        let res = http.get(`${BASE_URL}/call-tags-admin`, params);
        check(res, { 'status is 200 or 4xx for call-tags.index': (r) => r.status < 500 });
    }

    // Route: call-tags/1/form
    {
        let res = http.get(`${BASE_URL}/call-tags/1/form`, params);
        check(res, { 'status is 200 or 4xx for call-tags/1/form': (r) => r.status < 500 });
    }

    // Route: catalog.attributes
    {
        let res = http.get(`${BASE_URL}/catalog/attributes`, params);
        check(res, { 'status is 200 or 4xx for catalog.attributes': (r) => r.status < 500 });
    }

    // Route: catalog.brands
    {
        let res = http.get(`${BASE_URL}/catalog/brands`, params);
        check(res, { 'status is 200 or 4xx for catalog.brands': (r) => r.status < 500 });
    }

    // Route: catalog.categories
    {
        let res = http.get(`${BASE_URL}/catalog/categories`, params);
        check(res, { 'status is 200 or 4xx for catalog.categories': (r) => r.status < 500 });
    }

    // Route: catalog.hsn-codes
    {
        let res = http.get(`${BASE_URL}/catalog/hsn-codes`, params);
        check(res, { 'status is 200 or 4xx for catalog.hsn-codes': (r) => r.status < 500 });
    }

    // Route: catalog.products
    {
        let res = http.get(`${BASE_URL}/catalog/products`, params);
        check(res, { 'status is 200 or 4xx for catalog.products': (r) => r.status < 500 });
    }

    // Route: catalog.tax-rates
    {
        let res = http.get(`${BASE_URL}/catalog/tax-rates`, params);
        check(res, { 'status is 200 or 4xx for catalog.tax-rates': (r) => r.status < 500 });
    }

    // Route: catalog.uom
    {
        let res = http.get(`${BASE_URL}/catalog/uom`, params);
        check(res, { 'status is 200 or 4xx for catalog.uom': (r) => r.status < 500 });
    }

    // Route: catalog.warehouses
    {
        let res = http.get(`${BASE_URL}/catalog/warehouses`, params);
        check(res, { 'status is 200 or 4xx for catalog.warehouses': (r) => r.status < 500 });
    }

    // Route: chat.index
    {
        let res = http.get(`${BASE_URL}/chat`, params);
        check(res, { 'status is 200 or 4xx for chat.index': (r) => r.status < 500 });
    }

    // Route: clear-views
    {
        let res = http.get(`${BASE_URL}/clear-views`, params);
        check(res, { 'status is 200 or 4xx for clear-views': (r) => r.status < 500 });
    }

    // Route: complaints.index
    {
        let res = http.get(`${BASE_URL}/complaints`, params);
        check(res, { 'status is 200 or 4xx for complaints.index': (r) => r.status < 500 });
    }

    // Route: credit-notes.index
    {
        let res = http.get(`${BASE_URL}/credit-notes`, params);
        check(res, { 'status is 200 or 4xx for credit-notes.index': (r) => r.status < 500 });
    }

    // Route: customer-settings.index
    {
        let res = http.get(`${BASE_URL}/customer-settings`, params);
        check(res, { 'status is 200 or 4xx for customer-settings.index': (r) => r.status < 500 });
    }

    // Route: customers
    {
        let res = http.get(`${BASE_URL}/customers`, params);
        check(res, { 'status is 200 or 4xx for customers': (r) => r.status < 500 });
    }

    // Route: customers.search-by-phone
    {
        let res = http.get(`${BASE_URL}/customers/search-by-phone`, params);
        check(res, { 'status is 200 or 4xx for customers.search-by-phone': (r) => r.status < 500 });
    }

    // Route: customers.show
    {
        let res = http.get(`${BASE_URL}/customers/1`, params);
        check(res, { 'status is 200 or 4xx for customers.show': (r) => r.status < 500 });
    }

    // Route: departments
    {
        let res = http.get(`${BASE_URL}/departments`, params);
        check(res, { 'status is 200 or 4xx for departments': (r) => r.status < 500 });
    }

    // Route: scramble.docs.ui
    {
        let res = http.get(`${BASE_URL}/docs/api`, params);
        check(res, { 'status is 200 or 4xx for scramble.docs.ui': (r) => r.status < 500 });
    }

    // Route: scramble.docs.document
    {
        let res = http.get(`${BASE_URL}/docs/api.json`, params);
        check(res, { 'status is 200 or 4xx for scramble.docs.document': (r) => r.status < 500 });
    }

    // Route: elements
    {
        let res = http.get(`${BASE_URL}/elements`, params);
        check(res, { 'status is 200 or 4xx for elements': (r) => r.status < 500 });
    }

    // Route: elements-alerts
    {
        let res = http.get(`${BASE_URL}/elements/alerts`, params);
        check(res, { 'status is 200 or 4xx for elements-alerts': (r) => r.status < 500 });
    }

    // Route: elements-badges
    {
        let res = http.get(`${BASE_URL}/elements/badges`, params);
        check(res, { 'status is 200 or 4xx for elements-badges': (r) => r.status < 500 });
    }

    // Route: elements-buttons
    {
        let res = http.get(`${BASE_URL}/elements/buttons`, params);
        check(res, { 'status is 200 or 4xx for elements-buttons': (r) => r.status < 500 });
    }

    // Route: elements-cards
    {
        let res = http.get(`${BASE_URL}/elements/cards`, params);
        check(res, { 'status is 200 or 4xx for elements-cards': (r) => r.status < 500 });
    }

    // Route: elements-forms
    {
        let res = http.get(`${BASE_URL}/elements/forms`, params);
        check(res, { 'status is 200 or 4xx for elements-forms': (r) => r.status < 500 });
    }

    // Route: elements-modals
    {
        let res = http.get(`${BASE_URL}/elements/modals`, params);
        check(res, { 'status is 200 or 4xx for elements-modals': (r) => r.status < 500 });
    }

    // Route: elements-tables
    {
        let res = http.get(`${BASE_URL}/elements/tables`, params);
        check(res, { 'status is 200 or 4xx for elements-tables': (r) => r.status < 500 });
    }

    // Route: files
    {
        let res = http.get(`${BASE_URL}/files`, params);
        check(res, { 'status is 200 or 4xx for files': (r) => r.status < 500 });
    }

    // Route: forms
    {
        let res = http.get(`${BASE_URL}/forms`, params);
        check(res, { 'status is 200 or 4xx for forms': (r) => r.status < 500 });
    }

    // Route: help
    {
        let res = http.get(`${BASE_URL}/help`, params);
        check(res, { 'status is 200 or 4xx for help': (r) => r.status < 500 });
    }

    // Route: images/1
    {
        let res = http.get(`${BASE_URL}/images/1`, params);
        check(res, { 'status is 200 or 4xx for images/1': (r) => r.status < 500 });
    }

    // Route: inventory.adjustments
    {
        let res = http.get(`${BASE_URL}/inventory/adjustments`, params);
        check(res, { 'status is 200 or 4xx for inventory.adjustments': (r) => r.status < 500 });
    }

    // Route: inventory.dashboard
    {
        let res = http.get(`${BASE_URL}/inventory/dashboard`, params);
        check(res, { 'status is 200 or 4xx for inventory.dashboard': (r) => r.status < 500 });
    }

    // Route: inventory.dashboard.activities
    {
        let res = http.get(`${BASE_URL}/inventory/dashboard/activities`, params);
        check(res, { 'status is 200 or 4xx for inventory.dashboard.activities': (r) => r.status < 500 });
    }

    // Route: inventory.stock-management
    {
        let res = http.get(`${BASE_URL}/inventory/stock-management`, params);
        check(res, { 'status is 200 or 4xx for inventory.stock-management': (r) => r.status < 500 });
    }

    // Route: inventory.stock-transfers
    {
        let res = http.get(`${BASE_URL}/inventory/stock-transfers`, params);
        check(res, { 'status is 200 or 4xx for inventory.stock-transfers': (r) => r.status < 500 });
    }

    // Route: invoices.index
    {
        let res = http.get(`${BASE_URL}/invoices`, params);
        check(res, { 'status is 200 or 4xx for invoices.index': (r) => r.status < 500 });
    }

    // Route: invoices.show
    {
        let res = http.get(`${BASE_URL}/invoices/1`, params);
        check(res, { 'status is 200 or 4xx for invoices.show': (r) => r.status < 500 });
    }

    // Route: leaves
    {
        let res = http.get(`${BASE_URL}/leaves`, params);
        check(res, { 'status is 200 or 4xx for leaves': (r) => r.status < 500 });
    }

    // Route: login
    {
        let res = http.get(`${BASE_URL}/login`, params);
        check(res, { 'status is 200 or 4xx for login': (r) => r.status < 500 });
    }

    // Route: logout
    {
        let res = http.get(`${BASE_URL}/logout`, params);
        check(res, { 'status is 200 or 4xx for logout': (r) => r.status < 500 });
    }

    // Route: messages
    {
        let res = http.get(`${BASE_URL}/messages`, params);
        check(res, { 'status is 200 or 4xx for messages': (r) => r.status < 500 });
    }

    // Route: order.reasons
    {
        let res = http.get(`${BASE_URL}/order-reasons`, params);
        check(res, { 'status is 200 or 4xx for order.reasons': (r) => r.status < 500 });
    }

    // Route: orders
    {
        let res = http.get(`${BASE_URL}/orders`, params);
        check(res, { 'status is 200 or 4xx for orders': (r) => r.status < 500 });
    }

    // Route: orders.bulk-print
    {
        let res = http.get(`${BASE_URL}/orders/bulk-print`, params);
        check(res, { 'status is 200 or 4xx for orders.bulk-print': (r) => r.status < 500 });
    }

    // Route: orders.create
    {
        let res = http.get(`${BASE_URL}/orders/create`, params);
        check(res, { 'status is 200 or 4xx for orders.create': (r) => r.status < 500 });
    }

    // Route: orders.export
    {
        let res = http.get(`${BASE_URL}/orders/export`, params);
        check(res, { 'status is 200 or 4xx for orders.export': (r) => r.status < 500 });
    }

    // Route: orders.import-deliver-template
    {
        let res = http.get(`${BASE_URL}/orders/import-deliver-template`, params);
        check(res, { 'status is 200 or 4xx for orders.import-deliver-template': (r) => r.status < 500 });
    }

    // Route: orders.import-new-template
    {
        let res = http.get(`${BASE_URL}/orders/import-new-template`, params);
        check(res, { 'status is 200 or 4xx for orders.import-new-template': (r) => r.status < 500 });
    }

    // Route: orders.import-return-template
    {
        let res = http.get(`${BASE_URL}/orders/import-return-template`, params);
        check(res, { 'status is 200 or 4xx for orders.import-return-template': (r) => r.status < 500 });
    }

    // Route: orders.show
    {
        let res = http.get(`${BASE_URL}/orders/1`, params);
        check(res, { 'status is 200 or 4xx for orders.show': (r) => r.status < 500 });
    }

    // Route: orders.cod-pdf
    {
        let res = http.get(`${BASE_URL}/orders/1/cod-pdf`, params);
        check(res, { 'status is 200 or 4xx for orders.cod-pdf': (r) => r.status < 500 });
    }

    // Route: orders.edit
    {
        let res = http.get(`${BASE_URL}/orders/1/edit`, params);
        check(res, { 'status is 200 or 4xx for orders.edit': (r) => r.status < 500 });
    }

    // Route: orders.invoice-pdf
    {
        let res = http.get(`${BASE_URL}/orders/1/invoice-pdf`, params);
        check(res, { 'status is 200 or 4xx for orders.invoice-pdf': (r) => r.status < 500 });
    }

    // Route: orders.receipt
    {
        let res = http.get(`${BASE_URL}/orders/1/receipt`, params);
        check(res, { 'status is 200 or 4xx for orders.receipt': (r) => r.status < 500 });
    }

    // Route: orders.shipping-label
    {
        let res = http.get(`${BASE_URL}/orders/1/shipping-label`, params);
        check(res, { 'status is 200 or 4xx for orders.shipping-label': (r) => r.status < 500 });
    }

    // Route: payments.index
    {
        let res = http.get(`${BASE_URL}/payments`, params);
        check(res, { 'status is 200 or 4xx for payments.index': (r) => r.status < 500 });
    }

    // Route: payments.import.sample
    {
        let res = http.get(`${BASE_URL}/payments/import/sample`, params);
        check(res, { 'status is 200 or 4xx for payments.import.sample': (r) => r.status < 500 });
    }

    // Route: payments.show
    {
        let res = http.get(`${BASE_URL}/payments/1`, params);
        check(res, { 'status is 200 or 4xx for payments.show': (r) => r.status < 500 });
    }

    // Route: procurement.goods-receipts.index
    {
        let res = http.get(`${BASE_URL}/procurement/goods-receipts`, params);
        check(res, { 'status is 200 or 4xx for procurement.goods-receipts.index': (r) => r.status < 500 });
    }

    // Route: procurement.goods-receipts.pdf
    {
        let res = http.get(`${BASE_URL}/procurement/goods-receipts/1/pdf`, params);
        check(res, { 'status is 200 or 4xx for procurement.goods-receipts.pdf': (r) => r.status < 500 });
    }

    // Route: procurement.purchase-orders.index
    {
        let res = http.get(`${BASE_URL}/procurement/purchase-orders`, params);
        check(res, { 'status is 200 or 4xx for procurement.purchase-orders.index': (r) => r.status < 500 });
    }

    // Route: procurement.purchase-orders.bulk-pdf
    {
        let res = http.get(`${BASE_URL}/procurement/purchase-orders/bulk-pdf`, params);
        check(res, { 'status is 200 or 4xx for procurement.purchase-orders.bulk-pdf': (r) => r.status < 500 });
    }

    // Route: procurement.purchase-orders.pdf
    {
        let res = http.get(`${BASE_URL}/procurement/purchase-orders/1/pdf`, params);
        check(res, { 'status is 200 or 4xx for procurement.purchase-orders.pdf': (r) => r.status < 500 });
    }

    // Route: procurement.suppliers.index
    {
        let res = http.get(`${BASE_URL}/procurement/suppliers`, params);
        check(res, { 'status is 200 or 4xx for procurement.suppliers.index': (r) => r.status < 500 });
    }

    // Route: products.search.api
    {
        let res = http.get(`${BASE_URL}/products-search-api`, params);
        check(res, { 'status is 200 or 4xx for products.search.api': (r) => r.status < 500 });
    }

    // Route: promotions.coupons
    {
        let res = http.get(`${BASE_URL}/promotions/coupons`, params);
        check(res, { 'status is 200 or 4xx for promotions.coupons': (r) => r.status < 500 });
    }

    // Route: promotions.offers
    {
        let res = http.get(`${BASE_URL}/promotions/offers`, params);
        check(res, { 'status is 200 or 4xx for promotions.offers': (r) => r.status < 500 });
    }

    // Route: referrals.programs.index
    {
        let res = http.get(`${BASE_URL}/promotions/referral-programs`, params);
        check(res, { 'status is 200 or 4xx for referrals.programs.index': (r) => r.status < 500 });
    }

    // Route: refunds.index
    {
        let res = http.get(`${BASE_URL}/refunds`, params);
        check(res, { 'status is 200 or 4xx for refunds.index': (r) => r.status < 500 });
    }

    // Route: reports
    {
        let res = http.get(`${BASE_URL}/reports`, params);
        check(res, { 'status is 200 or 4xx for reports': (r) => r.status < 500 });
    }

    // Route: reports.export
    {
        let res = http.get(`${BASE_URL}/reports/export`, params);
        check(res, { 'status is 200 or 4xx for reports.export': (r) => r.status < 500 });
    }

    // Route: returns.index
    {
        let res = http.get(`${BASE_URL}/returns`, params);
        check(res, { 'status is 200 or 4xx for returns.index': (r) => r.status < 500 });
    }

    // Route: returns.show
    {
        let res = http.get(`${BASE_URL}/returns/1`, params);
        check(res, { 'status is 200 or 4xx for returns.show': (r) => r.status < 500 });
    }

    // Route: roles-permissions
    {
        let res = http.get(`${BASE_URL}/roles-permissions`, params);
        check(res, { 'status is 200 or 4xx for roles-permissions': (r) => r.status < 500 });
    }

    // Route: sanctum.csrf-cookie
    {
        let res = http.get(`${BASE_URL}/sanctum/csrf-cookie`, params);
        check(res, { 'status is 200 or 4xx for sanctum.csrf-cookie': (r) => r.status < 500 });
    }

    // Route: global.search
    {
        let res = http.get(`${BASE_URL}/search`, params);
        check(res, { 'status is 200 or 4xx for global.search': (r) => r.status < 500 });
    }

    // Route: security
    {
        let res = http.get(`${BASE_URL}/security`, params);
        check(res, { 'status is 200 or 4xx for security': (r) => r.status < 500 });
    }

    // Route: shipping.services
    {
        let res = http.get(`${BASE_URL}/shipping/services`, params);
        check(res, { 'status is 200 or 4xx for shipping.services': (r) => r.status < 500 });
    }

    // Route: shipping.settings
    {
        let res = http.get(`${BASE_URL}/shipping/settings`, params);
        check(res, { 'status is 200 or 4xx for shipping.settings': (r) => r.status < 500 });
    }

    // Route: shipping.shipments
    {
        let res = http.get(`${BASE_URL}/shipping/shipments`, params);
        check(res, { 'status is 200 or 4xx for shipping.shipments': (r) => r.status < 500 });
    }

    // Route: storage.local
    {
        let res = http.get(`${BASE_URL}/storage/1`, params);
        check(res, { 'status is 200 or 4xx for storage.local': (r) => r.status < 500 });
    }

    // Route: targets.index
    {
        let res = http.get(`${BASE_URL}/targets`, params);
        check(res, { 'status is 200 or 4xx for targets.index': (r) => r.status < 500 });
    }

    // Route: targets.create
    {
        let res = http.get(`${BASE_URL}/targets/create`, params);
        check(res, { 'status is 200 or 4xx for targets.create': (r) => r.status < 500 });
    }

    // Route: targets.export
    {
        let res = http.get(`${BASE_URL}/targets/export`, params);
        check(res, { 'status is 200 or 4xx for targets.export': (r) => r.status < 500 });
    }

    // Route: targets.import
    {
        let res = http.get(`${BASE_URL}/targets/import`, params);
        check(res, { 'status is 200 or 4xx for targets.import': (r) => r.status < 500 });
    }

    // Route: targets.import.template
    {
        let res = http.get(`${BASE_URL}/targets/import-template`, params);
        check(res, { 'status is 200 or 4xx for targets.import.template': (r) => r.status < 500 });
    }

    // Route: targets.edit
    {
        let res = http.get(`${BASE_URL}/targets/1/edit`, params);
        check(res, { 'status is 200 or 4xx for targets.edit': (r) => r.status < 500 });
    }

    // Route: teams
    {
        let res = http.get(`${BASE_URL}/teams`, params);
        check(res, { 'status is 200 or 4xx for teams': (r) => r.status < 500 });
    }

    // Route: up
    {
        let res = http.get(`${BASE_URL}/up`, params);
        check(res, { 'status is 200 or 4xx for up': (r) => r.status < 500 });
    }

    // Route: users
    {
        let res = http.get(`${BASE_URL}/users`, params);
        check(res, { 'status is 200 or 4xx for users': (r) => r.status < 500 });
    }

    // Route: villages
    {
        let res = http.get(`${BASE_URL}/villages`, params);
        check(res, { 'status is 200 or 4xx for villages': (r) => r.status < 500 });
    }
}
