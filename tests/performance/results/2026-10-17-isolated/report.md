# Isolated HTTP load results

Synthetic fixtures; local PHP development server. These measurements do not establish production capacity.

## site

Threshold failures: checks.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 1048 | 0.00% | 635.44 | 975.57 |

PHP process-group CPU: 98.44 CPU seconds over 121.55 wall seconds (81.0% of one core on average). Peak summed RSS: 317.2 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/` | 13 | 200: 13 | 615.79 | 702.19 |
| `/admin/audit-logs` | 14 | 200: 14 | 458.29 | 637.96 |
| `/analytics` | 15 | 200: 15 | 482.03 | 1016.97 |
| `/attendances` | 16 | 200: 16 | 546.43 | 893.04 |
| `/calendar` | 16 | 200: 16 | 605.15 | 707.15 |
| `/call-tags-admin` | 16 | 200: 16 | 608.73 | 986.44 |
| `/catalog/attributes` | 17 | 200: 17 | 462.21 | 548.24 |
| `/catalog/brands` | 18 | 200: 18 | 523.41 | 741.90 |
| `/catalog/categories` | 18 | 200: 18 | 563.79 | 913.22 |
| `/catalog/hsn-codes` | 19 | 200: 19 | 562.84 | 655.01 |
| `/catalog/products` | 20 | 200: 20 | 543.98 | 1383.69 |
| `/catalog/tax-rates` | 21 | 200: 21 | 847.32 | 894.47 |
| `/catalog/uom` | 20 | 200: 20 | 632.70 | 670.75 |
| `/catalog/warehouses` | 20 | 200: 20 | 525.09 | 877.77 |
| `/chat` | 20 | 200: 20 | 505.80 | 710.79 |
| `/complaints` | 20 | 200: 20 | 376.22 | 454.49 |
| `/credit-notes` | 20 | 200: 20 | 633.67 | 749.22 |
| `/customer-settings` | 20 | 200: 20 | 853.69 | 887.86 |
| `/customers` | 20 | 200: 20 | 802.79 | 1081.57 |
| `/departments` | 20 | 200: 20 | 604.78 | 1050.30 |
| `/elements` | 20 | 200: 20 | 486.49 | 751.01 |
| `/elements/alerts` | 20 | 200: 20 | 425.01 | 626.77 |
| `/elements/badges` | 19 | 200: 19 | 432.07 | 530.67 |
| `/elements/buttons` | 19 | 200: 19 | 476.63 | 564.18 |
| `/elements/cards` | 19 | 200: 19 | 463.41 | 562.14 |
| `/elements/forms` | 19 | 200: 19 | 555.36 | 823.97 |
| `/elements/modals` | 19 | 200: 19 | 421.26 | 722.20 |
| `/elements/tables` | 19 | 200: 19 | 546.36 | 747.11 |
| `/files` | 18 | 200: 18 | 513.70 | 948.84 |
| `/forms` | 18 | 200: 18 | 463.29 | 1257.59 |
| `/help` | 17 | 200: 17 | 349.14 | 622.96 |
| `/inventory/adjustments` | 17 | 200: 17 | 404.99 | 425.49 |
| `/inventory/dashboard` | 17 | 200: 17 | 453.50 | 597.84 |
| `/inventory/dashboard/activities` | 17 | 200: 17 | 285.77 | 348.62 |
| `/inventory/stock-management` | 17 | 200: 17 | 600.74 | 613.28 |
| `/inventory/stock-transfers` | 17 | 200: 17 | 505.51 | 579.73 |
| `/invoices` | 17 | 200: 17 | 507.70 | 885.99 |
| `/leaves` | 16 | 200: 16 | 658.24 | 684.58 |
| `/messages` | 16 | 200: 16 | 456.34 | 1200.02 |
| `/order-reasons` | 16 | 200: 16 | 667.18 | 685.95 |
| `/orders` | 15 | 200: 15 | 963.32 | 1471.59 |
| `/orders/create` | 15 | 200: 15 | 620.82 | 641.61 |
| `/payments` | 15 | 200: 15 | 739.79 | 922.97 |
| `/procurement/goods-receipts` | 15 | 200: 15 | 492.09 | 495.09 |
| `/procurement/purchase-orders` | 15 | 200: 15 | 646.03 | 765.38 |
| `/procurement/suppliers` | 15 | 200: 15 | 561.63 | 568.50 |
| `/promotions/coupons` | 15 | 200: 15 | 349.93 | 490.16 |
| `/promotions/offers` | 15 | 200: 15 | 449.81 | 649.30 |
| `/promotions/referral-programs` | 15 | 200: 15 | 587.22 | 721.97 |
| `/refunds` | 14 | 200: 14 | 496.95 | 518.82 |
| `/reports` | 14 | 200: 14 | 1047.38 | 1278.83 |
| `/returns` | 14 | 200: 14 | 447.77 | 739.96 |
| `/roles-permissions` | 14 | 200: 14 | 336.23 | 633.27 |
| `/security` | 14 | 200: 14 | 386.98 | 506.74 |
| `/shipping/services` | 14 | 200: 14 | 637.66 | 647.22 |
| `/shipping/settings` | 14 | 200: 14 | 584.61 | 600.88 |
| `/shipping/shipments` | 14 | 200: 14 | 528.78 | 575.65 |
| `/targets` | 13 | 200: 13 | 705.09 | 1135.44 |
| `/targets/create` | 13 | 302: 13 | 252.98 | 412.06 |
| `/targets/import` | 13 | 302: 13 | 207.71 | 235.27 |
| `/teams` | 13 | 200: 13 | 553.46 | 636.40 |
| `/users` | 13 | 200: 13 | 416.93 | 839.80 |
| `/villages` | 13 | 200: 13 | 394.94 | 702.49 |

## apis

Threshold failures: http_req_duration.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 1151 | 0.00% | 290.38 | 9929.61 |

PHP process-group CPU: 179.82 CPU seconds over 123.61 wall seconds (145.5% of one core on average). Peak summed RSS: 701.9 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/api/activities/recent` | 7 | 200: 7 | 198.00 | 243.01 |
| `/api/admin/audit-logs` | 7 | 200: 7 | 176.40 | 204.67 |
| `/api/attendances` | 8 | 200: 8 | 197.82 | 203.45 |
| `/api/attendances/export/detailed` | 1 | 200: 1 | 51.40 | 51.40 |
| `/api/attendances/export/summary` | 1 | 200: 1 | 52.33 | 52.33 |
| `/api/attendances/{attendance}` | 9 | 200: 9 | 175.47 | 226.30 |
| `/api/attributes` | 10 | 200: 10 | 189.51 | 249.04 |
| `/api/attributes/{attribute}` | 11 | 200: 11 | 202.12 | 203.88 |
| `/api/brands` | 12 | 200: 12 | 118.89 | 164.32 |
| `/api/brands/{brand}` | 12 | 200: 12 | 159.41 | 197.60 |
| `/api/call-tags` | 12 | 200: 12 | 109.47 | 143.91 |
| `/api/call-tags-admin` | 12 | 200: 12 | 316.53 | 359.93 |
| `/api/call-tags/{tag}/form` | 12 | 200: 12 | 129.46 | 182.58 |
| `/api/categories` | 12 | 200: 12 | 135.22 | 167.86 |
| `/api/categories/{category}` | 12 | 404: 12 | 157.36 | 200.58 |
| `/api/chat/active-status` | 12 | 200: 12 | 192.69 | 273.03 |
| `/api/chat/conversations` | 12 | 200: 12 | 203.37 | 252.46 |
| `/api/chat/conversations/{conversation}` | 12 | 404: 12 | 101.32 | 118.37 |
| `/api/chat/conversations/{conversation}/messages` | 12 | 404: 12 | 120.55 | 135.40 |
| `/api/chat/search` | 12 | 200: 12 | 203.71 | 380.10 |
| `/api/chat/unread-count` | 12 | 200: 12 | 153.16 | 250.67 |
| `/api/chat/users` | 12 | 200: 12 | 120.51 | 166.03 |
| `/api/complaints` | 12 | 200: 12 | 206.40 | 215.52 |
| `/api/complaints/export` | 1 | 404: 1 | 49.53 | 49.53 |
| `/api/complaints/stats` | 12 | 200: 12 | 131.74 | 334.43 |
| `/api/complaints/{complaint}` | 12 | 404: 12 | 130.82 | 136.44 |
| `/api/credit-notes` | 12 | 200: 12 | 132.56 | 305.22 |
| `/api/customer-settings/{type}` | 12 | 200: 12 | 114.61 | 148.15 |
| `/api/customers` | 12 | 200: 12 | 225.28 | 284.05 |
| `/api/customers/check-referral/{code}` | 12 | 200: 12 | 91.52 | 442.24 |
| `/api/customers/{customer}` | 12 | 200: 12 | 234.42 | 360.36 |
| `/api/dashboard` | 12 | 200: 12 | 240.69 | 413.86 |
| `/api/departments` | 12 | 200: 12 | 172.96 | 218.38 |
| `/api/departments/{department}` | 12 | 404: 12 | 132.40 | 165.39 |
| `/api/files` | 12 | 200: 12 | 118.55 | 123.69 |
| `/api/files/preview` | 1 | 404: 1 | 45.06 | 45.06 |
| `/api/holidays` | 12 | 200: 12 | 153.24 | 165.92 |
| `/api/holidays/{holiday}` | 12 | 404: 12 | 107.54 | 165.52 |
| `/api/hr-settings/{type}` | 12 | 200: 12 | 176.67 | 223.61 |
| `/api/hsn-codes` | 12 | 200: 12 | 144.57 | 206.86 |
| `/api/hsn-codes/{hsn_code}` | 12 | 404: 12 | 127.99 | 144.91 |
| `/api/inventory/adjustments` | 12 | 200: 12 | 186.54 | 204.09 |
| `/api/inventory/adjustments/options` | 12 | 200: 12 | 184.40 | 202.97 |
| `/api/inventory/adjustments/{inventoryAdjustment}` | 12 | 404: 12 | 166.31 | 226.39 |
| `/api/inventory/stocks` | 12 | 200: 12 | 503.98 | 526.00 |
| `/api/inventory/stocks/show` | 12 | 422: 12 | 167.08 | 231.45 |
| `/api/inventory/stocks/warehouse-options` | 12 | 200: 12 | 138.90 | 184.53 |
| `/api/inventory/transfers` | 12 | 200: 12 | 164.91 | 266.22 |
| `/api/inventory/transfers/options` | 12 | 200: 12 | 183.46 | 363.37 |
| `/api/inventory/transfers/{transfer}` | 11 | 200: 11 | 164.33 | 179.67 |
| `/api/invoices` | 11 | 200: 11 | 260.71 | 422.19 |
| `/api/invoices/{invoice}` | 11 | 200: 11 | 164.44 | 185.76 |
| `/api/invoices/{invoice}/pdf` | 1 | 200: 1 | 579.13 | 579.13 |
| `/api/leave-balances` | 11 | 200: 11 | 147.77 | 162.56 |
| `/api/leave-balances/{leave_balance}` | 11 | 404: 11 | 114.37 | 158.79 |
| `/api/leaves` | 11 | 200: 11 | 178.62 | 730.13 |
| `/api/leaves/{leave}` | 11 | 404: 11 | 124.84 | 164.99 |
| `/api/order-reasons/{type}` | 11 | 200: 11 | 131.75 | 137.75 |
| `/api/orders` | 11 | 200: 11 | 252.96 | 306.94 |
| `/api/orders/bulk-print` | 1 | 404: 1 | 75.90 | 75.90 |
| `/api/orders/export` | 1 | 404: 1 | 100.20 | 100.20 |
| `/api/orders/{order}` | 11 | 200: 11 | 404.75 | 811.82 |
| `/api/org-chart` | 11 | 200: 11 | 163.15 | 387.35 |
| `/api/payments` | 11 | 200: 11 | 254.55 | 445.96 |
| `/api/payments/{payment}` | 10 | 200: 10 | 258.66 | 375.57 |
| `/api/permissions` | 10 | 200: 10 | 174.88 | 227.37 |
| `/api/permissions/options` | 10 | 200: 10 | 124.48 | 195.11 |
| `/api/permissions/{permission}` | 10 | 200: 10 | 166.90 | 181.67 |
| `/api/procurement/goods-receipts` | 10 | 200: 10 | 140.83 | 180.47 |
| `/api/procurement/goods-receipts/{receipt}/pdf` | 1 | 404: 1 | 81.84 | 81.84 |
| `/api/procurement/purchase-orders` | 10 | 200: 10 | 152.91 | 177.87 |
| `/api/procurement/purchase-orders/{order}/payments` | 10 | 404: 10 | 167.15 | 255.80 |
| `/api/procurement/purchase-orders/{order}/pdf` | 1 | 404: 1 | 97.61 | 97.61 |
| `/api/procurement/suppliers` | 10 | 200: 10 | 223.33 | 245.76 |
| `/api/procurement/suppliers/{supplier}` | 10 | 404: 10 | 183.73 | 201.81 |
| `/api/products` | 10 | 200: 10 | 313.71 | 396.24 |
| `/api/products-search-api` | 10 | 200: 10 | 167.38 | 199.00 |
| `/api/products/export` | 1 | 200: 1 | 87.08 | 87.08 |
| `/api/products/{product}` | 10 | 200: 10 | 289.91 | 842.79 |
| `/api/promotions/coupons` | 10 | 200: 10 | 179.00 | 197.61 |
| `/api/promotions/offers` | 10 | 200: 10 | 111.43 | 150.24 |
| `/api/promotions/referral-programs` | 10 | 200: 10 | 137.27 | 207.11 |
| `/api/refunds` | 10 | 200: 10 | 154.70 | 212.26 |
| `/api/refunds/{refund}` | 10 | 200: 10 | 169.97 | 187.67 |
| `/api/reports` | 10 | 200: 10 | 403.95 | 494.24 |
| `/api/returns` | 10 | 200: 10 | 253.19 | 261.61 |
| `/api/returns/{orderReturn}` | 10 | 200: 10 | 267.04 | 276.89 |
| `/api/roles` | 10 | 200: 10 | 255.77 | 299.53 |
| `/api/roles/options` | 10 | 200: 10 | 137.97 | 203.91 |
| `/api/roles/{role}` | 10 | 200: 10 | 136.88 | 248.30 |
| `/api/security/activity` | 10 | 200: 10 | 89.76 | 160.65 |
| `/api/security/sessions` | 10 | 200: 10 | 146.69 | 262.10 |
| `/api/shipping/awb-logs/{officeId}` | 10 | 200: 10 | 101.57 | 107.14 |
| `/api/shipping/offices/{officeId}/ranges` | 10 | 200: 10 | 157.64 | 160.92 |
| `/api/shipping/services` | 10 | 200: 10 | 196.20 | 196.22 |
| `/api/shipping/services/provider-options` | 10 | 200: 10 | 266.70 | 591.96 |
| `/api/shipping/shipments` | 10 | 200: 10 | 221.97 | 306.91 |
| `/api/shipping/shipments/{shipment}/tracking` | 10 | 200: 10 | 173.53 | 231.63 |
| `/api/tax-rates` | 10 | 200: 10 | 156.01 | 233.18 |
| `/api/tax-rates/{tax_rate}` | 10 | 404: 10 | 116.30 | 120.33 |
| `/api/teams` | 10 | 200: 10 | 146.65 | 218.64 |
| `/api/teams/{team}` | 10 | 200: 10 | 137.04 | 149.86 |
| `/api/uom` | 10 | 200: 10 | 128.53 | 144.78 |
| `/api/uom/{uom}` | 10 | 200: 10 | 159.15 | 352.16 |
| `/api/users` | 10 | 200: 10 | 197.98 | 273.03 |
| `/api/users/{user}` | 10 | 200: 10 | 114.44 | 124.00 |
| `/api/users/{user}/login-history` | 10 | 200: 10 | 145.14 | 179.50 |
| `/api/villages` | 10 | 200: 10 | 229.87 | 292.05 |
| `/api/villages/export` | 1 | 200: 1 | 113.83 | 113.83 |
| `/api/villages/import-template` | 1 | 200: 1 | 75.18 | 75.18 |
| `/api/villages/search` | 10 | 200: 10 | 115.47 | 143.23 |
| `/api/villages/services-options` | 10 | 200: 10 | 191.61 | 429.75 |
| `/api/villages/{village}` | 10 | 200: 10 | 181.26 | 345.01 |
| `/api/warehouses` | 10 | 200: 10 | 168.10 | 263.33 |
| `/api/warehouses/{warehouse}` | 10 | 200: 10 | 205.59 | 332.25 |
| `/docs/api` | 10 | 200: 10 | 16031.82 | 16177.16 |
| `/docs/api.json` | 7 | 200: 7 | 14229.26 | 15045.27 |
| `/products-search-api` | 7 | 200: 7 | 135.55 | 410.20 |
