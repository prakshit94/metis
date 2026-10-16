# Isolated HTTP load results

Synthetic fixtures; local PHP development server. These measurements do not establish production capacity.

## site

Threshold failures: none.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 66 | 0.00% | 336.57 | 440.48 |

PHP process-group CPU: 5.42 CPU seconds over 26.86 wall seconds (20.2% of one core on average). Peak summed RSS: 291.4 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/` | 1 | 200: 1 | 205.19 | 205.19 |
| `/admin/audit-logs` | 1 | 200: 1 | 109.14 | 109.14 |
| `/analytics` | 1 | 200: 1 | 96.57 | 96.57 |
| `/attendances` | 1 | 200: 1 | 128.40 | 128.40 |
| `/calendar` | 1 | 200: 1 | 131.37 | 131.37 |
| `/call-tags-admin` | 1 | 200: 1 | 252.71 | 252.71 |
| `/catalog/attributes` | 1 | 200: 1 | 132.47 | 132.47 |
| `/catalog/brands` | 1 | 200: 1 | 142.47 | 142.47 |
| `/catalog/categories` | 1 | 200: 1 | 160.68 | 160.68 |
| `/catalog/hsn-codes` | 1 | 200: 1 | 205.10 | 205.10 |
| `/catalog/products` | 1 | 200: 1 | 237.34 | 237.34 |
| `/catalog/tax-rates` | 1 | 200: 1 | 189.73 | 189.73 |
| `/catalog/uom` | 1 | 200: 1 | 160.20 | 160.20 |
| `/catalog/warehouses` | 1 | 200: 1 | 210.86 | 210.86 |
| `/chat` | 1 | 200: 1 | 179.46 | 179.46 |
| `/complaints` | 1 | 200: 1 | 154.03 | 154.03 |
| `/credit-notes` | 1 | 200: 1 | 222.00 | 222.00 |
| `/customer-settings` | 1 | 200: 1 | 171.41 | 171.41 |
| `/customers` | 1 | 200: 1 | 303.73 | 303.73 |
| `/departments` | 1 | 200: 1 | 307.72 | 307.72 |
| `/elements` | 1 | 200: 1 | 193.96 | 193.96 |
| `/elements/alerts` | 1 | 200: 1 | 128.32 | 128.32 |
| `/elements/badges` | 1 | 200: 1 | 153.19 | 153.19 |
| `/elements/buttons` | 1 | 200: 1 | 172.05 | 172.05 |
| `/elements/cards` | 1 | 200: 1 | 165.05 | 165.05 |
| `/elements/forms` | 1 | 200: 1 | 482.86 | 482.86 |
| `/elements/modals` | 1 | 200: 1 | 145.55 | 145.55 |
| `/elements/tables` | 1 | 200: 1 | 242.05 | 242.05 |
| `/files` | 1 | 200: 1 | 205.61 | 205.61 |
| `/forms` | 1 | 200: 1 | 166.90 | 166.90 |
| `/help` | 1 | 200: 1 | 153.65 | 153.65 |
| `/inventory/adjustments` | 1 | 200: 1 | 81.85 | 81.85 |
| `/inventory/dashboard` | 1 | 200: 1 | 178.11 | 178.11 |
| `/inventory/dashboard/activities` | 1 | 200: 1 | 57.91 | 57.91 |
| `/inventory/stock-management` | 1 | 200: 1 | 128.11 | 128.11 |
| `/inventory/stock-transfers` | 1 | 200: 1 | 145.81 | 145.81 |
| `/invoices` | 1 | 200: 1 | 157.39 | 157.39 |
| `/leaves` | 1 | 200: 1 | 100.78 | 100.78 |
| `/messages` | 1 | 200: 1 | 238.79 | 238.79 |
| `/order-reasons` | 1 | 200: 1 | 121.10 | 121.10 |
| `/orders` | 1 | 200: 1 | 417.66 | 417.66 |
| `/orders/create` | 1 | 200: 1 | 197.23 | 197.23 |
| `/payments` | 1 | 200: 1 | 156.98 | 156.98 |
| `/procurement/goods-receipts` | 1 | 200: 1 | 129.89 | 129.89 |
| `/procurement/purchase-orders` | 1 | 200: 1 | 186.65 | 186.65 |
| `/procurement/suppliers` | 1 | 200: 1 | 110.35 | 110.35 |
| `/promotions/coupons` | 1 | 200: 1 | 151.08 | 151.08 |
| `/promotions/offers` | 1 | 200: 1 | 119.69 | 119.69 |
| `/promotions/referral-programs` | 1 | 200: 1 | 186.08 | 186.08 |
| `/refunds` | 1 | 200: 1 | 144.00 | 144.00 |
| `/reports` | 1 | 200: 1 | 147.06 | 147.06 |
| `/returns` | 1 | 200: 1 | 92.39 | 92.39 |
| `/roles-permissions` | 1 | 200: 1 | 362.96 | 362.96 |
| `/security` | 1 | 200: 1 | 127.71 | 127.71 |
| `/shipping/services` | 1 | 200: 1 | 144.34 | 144.34 |
| `/shipping/settings` | 1 | 200: 1 | 103.44 | 103.44 |
| `/shipping/shipments` | 1 | 200: 1 | 136.70 | 136.70 |
| `/targets` | 1 | 200: 1 | 95.22 | 95.22 |
| `/targets/create` | 1 | 302: 1 | 48.11 | 48.11 |
| `/targets/import` | 1 | 302: 1 | 69.15 | 69.15 |
| `/teams` | 1 | 200: 1 | 118.14 | 118.14 |
| `/users` | 1 | 200: 1 | 83.42 | 83.42 |
| `/villages` | 1 | 200: 1 | 149.03 | 149.03 |

## apis

Threshold failures: none.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 121 | 0.00% | 210.82 | 4209.15 |

PHP process-group CPU: 15.31 CPU seconds over 23.15 wall seconds (66.1% of one core on average). Peak summed RSS: 506.3 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/api/activities/recent` | 1 | 200: 1 | 53.38 | 53.38 |
| `/api/admin/audit-logs` | 1 | 200: 1 | 61.17 | 61.17 |
| `/api/attendances` | 1 | 200: 1 | 48.70 | 48.70 |
| `/api/attendances/export/detailed` | 1 | 200: 1 | 43.57 | 43.57 |
| `/api/attendances/export/summary` | 1 | 200: 1 | 52.70 | 52.70 |
| `/api/attendances/{attendance}` | 1 | 200: 1 | 53.70 | 53.70 |
| `/api/attributes` | 1 | 200: 1 | 48.47 | 48.47 |
| `/api/attributes/{attribute}` | 1 | 200: 1 | 46.49 | 46.49 |
| `/api/brands` | 1 | 200: 1 | 47.80 | 47.80 |
| `/api/brands/{brand}` | 1 | 200: 1 | 37.59 | 37.59 |
| `/api/call-tags` | 1 | 200: 1 | 48.30 | 48.30 |
| `/api/call-tags-admin` | 1 | 200: 1 | 144.14 | 144.14 |
| `/api/call-tags/{tag}/form` | 1 | 200: 1 | 68.07 | 68.07 |
| `/api/categories` | 1 | 200: 1 | 66.74 | 66.74 |
| `/api/categories/{category}` | 1 | 404: 1 | 55.66 | 55.66 |
| `/api/chat/active-status` | 1 | 200: 1 | 109.41 | 109.41 |
| `/api/chat/conversations` | 1 | 200: 1 | 74.06 | 74.06 |
| `/api/chat/conversations/{conversation}` | 1 | 404: 1 | 62.29 | 62.29 |
| `/api/chat/conversations/{conversation}/messages` | 1 | 404: 1 | 73.09 | 73.09 |
| `/api/chat/search` | 1 | 200: 1 | 108.65 | 108.65 |
| `/api/chat/unread-count` | 1 | 200: 1 | 78.86 | 78.86 |
| `/api/chat/users` | 1 | 200: 1 | 77.18 | 77.18 |
| `/api/complaints` | 1 | 200: 1 | 166.99 | 166.99 |
| `/api/complaints/export` | 1 | 200: 1 | 152.84 | 152.84 |
| `/api/complaints/stats` | 1 | 200: 1 | 82.91 | 82.91 |
| `/api/complaints/{complaint}` | 1 | 200: 1 | 60.75 | 60.75 |
| `/api/credit-notes` | 1 | 200: 1 | 60.59 | 60.59 |
| `/api/customer-settings/{type}` | 1 | 200: 1 | 54.05 | 54.05 |
| `/api/customers` | 1 | 200: 1 | 78.10 | 78.10 |
| `/api/customers/check-referral/{code}` | 1 | 200: 1 | 49.36 | 49.36 |
| `/api/customers/{customer}` | 1 | 200: 1 | 210.82 | 210.82 |
| `/api/dashboard` | 1 | 200: 1 | 45.63 | 45.63 |
| `/api/departments` | 1 | 200: 1 | 46.24 | 46.24 |
| `/api/departments/{department}` | 1 | 404: 1 | 45.62 | 45.62 |
| `/api/files` | 1 | 200: 1 | 35.32 | 35.32 |
| `/api/files/preview` | 1 | 404: 1 | 21.45 | 21.45 |
| `/api/holidays` | 1 | 200: 1 | 81.59 | 81.59 |
| `/api/holidays/{holiday}` | 1 | 404: 1 | 55.29 | 55.29 |
| `/api/hr-settings/{type}` | 1 | 200: 1 | 72.15 | 72.15 |
| `/api/hsn-codes` | 1 | 200: 1 | 109.78 | 109.78 |
| `/api/hsn-codes/{hsn_code}` | 1 | 404: 1 | 55.42 | 55.42 |
| `/api/inventory/adjustments` | 1 | 200: 1 | 92.51 | 92.51 |
| `/api/inventory/adjustments/options` | 1 | 200: 1 | 76.14 | 76.14 |
| `/api/inventory/adjustments/{inventoryAdjustment}` | 1 | 404: 1 | 59.21 | 59.21 |
| `/api/inventory/stocks` | 1 | 200: 1 | 187.84 | 187.84 |
| `/api/inventory/stocks/show` | 1 | 422: 1 | 81.29 | 81.29 |
| `/api/inventory/stocks/warehouse-options` | 1 | 200: 1 | 55.96 | 55.96 |
| `/api/inventory/transfers` | 1 | 200: 1 | 61.95 | 61.95 |
| `/api/inventory/transfers/options` | 1 | 200: 1 | 69.27 | 69.27 |
| `/api/inventory/transfers/{transfer}` | 1 | 200: 1 | 51.81 | 51.81 |
| `/api/invoices` | 1 | 200: 1 | 91.70 | 91.70 |
| `/api/invoices/{invoice}` | 1 | 200: 1 | 87.16 | 87.16 |
| `/api/invoices/{invoice}/pdf` | 1 | 200: 1 | 427.70 | 427.70 |
| `/api/leave-balances` | 1 | 200: 1 | 39.36 | 39.36 |
| `/api/leave-balances/{leave_balance}` | 1 | 404: 1 | 26.63 | 26.63 |
| `/api/leaves` | 1 | 200: 1 | 40.62 | 40.62 |
| `/api/leaves/{leave}` | 1 | 404: 1 | 45.36 | 45.36 |
| `/api/order-reasons/{type}` | 1 | 200: 1 | 33.42 | 33.42 |
| `/api/orders` | 1 | 200: 1 | 78.03 | 78.03 |
| `/api/orders/bulk-print` | 1 | 422: 1 | 56.71 | 56.71 |
| `/api/orders/export` | 1 | 200: 1 | 250.88 | 250.88 |
| `/api/orders/{order}` | 1 | 200: 1 | 161.35 | 161.35 |
| `/api/org-chart` | 1 | 200: 1 | 96.24 | 96.24 |
| `/api/payments` | 1 | 200: 1 | 141.98 | 141.98 |
| `/api/payments/{payment}` | 1 | 200: 1 | 100.06 | 100.06 |
| `/api/permissions` | 1 | 200: 1 | 87.30 | 87.30 |
| `/api/permissions/options` | 1 | 200: 1 | 71.79 | 71.79 |
| `/api/permissions/{permission}` | 1 | 200: 1 | 73.17 | 73.17 |
| `/api/procurement/goods-receipts` | 1 | 200: 1 | 79.73 | 79.73 |
| `/api/procurement/goods-receipts/{receipt}/pdf` | 1 | 200: 1 | 290.22 | 290.22 |
| `/api/procurement/purchase-orders` | 1 | 200: 1 | 66.88 | 66.88 |
| `/api/procurement/purchase-orders/{order}/payments` | 1 | 200: 1 | 70.34 | 70.34 |
| `/api/procurement/purchase-orders/{order}/pdf` | 1 | 200: 1 | 282.55 | 282.55 |
| `/api/procurement/suppliers` | 1 | 200: 1 | 56.18 | 56.18 |
| `/api/procurement/suppliers/{supplier}` | 1 | 200: 1 | 38.01 | 38.01 |
| `/api/products` | 1 | 200: 1 | 113.79 | 113.79 |
| `/api/products-search-api` | 1 | 200: 1 | 91.03 | 91.03 |
| `/api/products/export` | 1 | 200: 1 | 75.44 | 75.44 |
| `/api/products/{product}` | 1 | 200: 1 | 109.77 | 109.77 |
| `/api/promotions/coupons` | 1 | 200: 1 | 84.32 | 84.32 |
| `/api/promotions/offers` | 1 | 200: 1 | 52.08 | 52.08 |
| `/api/promotions/referral-programs` | 1 | 200: 1 | 88.26 | 88.26 |
| `/api/refunds` | 1 | 200: 1 | 98.68 | 98.68 |
| `/api/refunds/{refund}` | 1 | 200: 1 | 114.31 | 114.31 |
| `/api/reports` | 1 | 200: 1 | 146.88 | 146.88 |
| `/api/returns` | 1 | 200: 1 | 100.46 | 100.46 |
| `/api/returns/{orderReturn}` | 1 | 200: 1 | 67.15 | 67.15 |
| `/api/roles` | 1 | 200: 1 | 55.41 | 55.41 |
| `/api/roles/options` | 1 | 200: 1 | 64.87 | 64.87 |
| `/api/roles/{role}` | 1 | 200: 1 | 68.27 | 68.27 |
| `/api/security/activity` | 1 | 200: 1 | 59.06 | 59.06 |
| `/api/security/sessions` | 1 | 200: 1 | 65.21 | 65.21 |
| `/api/shipping/awb-logs/{officeId}` | 1 | 200: 1 | 60.91 | 60.91 |
| `/api/shipping/offices/{officeId}/ranges` | 1 | 200: 1 | 76.35 | 76.35 |
| `/api/shipping/services` | 1 | 200: 1 | 66.61 | 66.61 |
| `/api/shipping/services/provider-options` | 1 | 200: 1 | 55.92 | 55.92 |
| `/api/shipping/shipments` | 1 | 200: 1 | 117.64 | 117.64 |
| `/api/shipping/shipments/{shipment}/tracking` | 1 | 200: 1 | 60.75 | 60.75 |
| `/api/tax-rates` | 1 | 200: 1 | 58.35 | 58.35 |
| `/api/tax-rates/{tax_rate}` | 1 | 404: 1 | 43.32 | 43.32 |
| `/api/teams` | 1 | 200: 1 | 60.50 | 60.50 |
| `/api/teams/{team}` | 1 | 200: 1 | 48.77 | 48.77 |
| `/api/uom` | 1 | 200: 1 | 52.21 | 52.21 |
| `/api/uom/{uom}` | 1 | 200: 1 | 62.72 | 62.72 |
| `/api/users` | 1 | 200: 1 | 147.77 | 147.77 |
| `/api/users/{user}` | 1 | 200: 1 | 83.71 | 83.71 |
| `/api/users/{user}/login-history` | 1 | 200: 1 | 46.77 | 46.77 |
| `/api/villages` | 1 | 200: 1 | 60.86 | 60.86 |
| `/api/villages/export` | 1 | 200: 1 | 74.72 | 74.72 |
| `/api/villages/import-template` | 1 | 200: 1 | 36.38 | 36.38 |
| `/api/villages/search` | 1 | 200: 1 | 76.00 | 76.00 |
| `/api/villages/services-options` | 1 | 200: 1 | 87.16 | 87.16 |
| `/api/villages/{village}` | 1 | 200: 1 | 92.81 | 92.81 |
| `/api/warehouses` | 1 | 200: 1 | 82.92 | 82.92 |
| `/api/warehouses/{warehouse}` | 1 | 200: 1 | 68.86 | 68.86 |
| `/docs/api` | 1 | 200: 1 | 7109.62 | 7109.62 |
| `/docs/api.json` | 1 | 200: 1 | 5154.52 | 5154.52 |
| `/products-search-api` | 1 | 200: 1 | 68.32 | 68.32 |
