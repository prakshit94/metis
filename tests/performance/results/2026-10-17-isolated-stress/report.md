# Isolated HTTP load results

Synthetic fixtures; local PHP development server. These measurements do not establish production capacity.

## site

Threshold failures: http_req_duration.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 2045 | 0.00% | 2725.04 | 2963.16 |

PHP process-group CPU: 223.18 CPU seconds over 182.91 wall seconds (122.0% of one core on average). Peak summed RSS: 318.0 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/` | 22 | 200: 22 | 2665.36 | 2971.13 |
| `/admin/audit-logs` | 22 | 200: 22 | 2212.76 | 2560.75 |
| `/analytics` | 23 | 200: 23 | 2322.45 | 3104.73 |
| `/attendances` | 22 | 200: 22 | 2190.00 | 2675.20 |
| `/calendar` | 22 | 200: 22 | 2367.01 | 2703.00 |
| `/call-tags-admin` | 23 | 200: 23 | 2155.42 | 2431.19 |
| `/catalog/attributes` | 23 | 200: 23 | 1843.50 | 2920.96 |
| `/catalog/brands` | 23 | 200: 23 | 1950.65 | 2369.48 |
| `/catalog/categories` | 23 | 200: 23 | 2247.97 | 2379.63 |
| `/catalog/hsn-codes` | 24 | 200: 24 | 2259.32 | 2648.77 |
| `/catalog/products` | 25 | 200: 25 | 2437.19 | 2953.36 |
| `/catalog/tax-rates` | 26 | 200: 26 | 2068.97 | 2618.07 |
| `/catalog/uom` | 27 | 200: 27 | 2219.75 | 2413.42 |
| `/catalog/warehouses` | 27 | 200: 27 | 2292.30 | 2796.21 |
| `/chat` | 28 | 200: 28 | 2575.00 | 2860.64 |
| `/complaints` | 28 | 200: 28 | 1965.16 | 2640.61 |
| `/credit-notes` | 29 | 200: 29 | 2271.47 | 2566.44 |
| `/customer-settings` | 29 | 200: 29 | 2336.50 | 2643.67 |
| `/customers` | 30 | 200: 30 | 2387.04 | 2541.99 |
| `/departments` | 31 | 200: 31 | 2417.69 | 2697.30 |
| `/elements` | 32 | 200: 32 | 2188.01 | 2296.82 |
| `/elements/alerts` | 33 | 200: 33 | 2346.87 | 2638.92 |
| `/elements/badges` | 34 | 200: 34 | 2488.92 | 2835.06 |
| `/elements/buttons` | 35 | 200: 35 | 2002.27 | 2725.30 |
| `/elements/cards` | 36 | 200: 36 | 2315.53 | 2398.76 |
| `/elements/forms` | 37 | 200: 37 | 2517.38 | 2874.50 |
| `/elements/modals` | 38 | 200: 38 | 2420.16 | 2993.01 |
| `/elements/tables` | 38 | 200: 38 | 2377.64 | 2660.44 |
| `/files` | 39 | 200: 39 | 2592.41 | 2997.52 |
| `/forms` | 39 | 200: 39 | 2529.37 | 2911.79 |
| `/help` | 40 | 200: 40 | 2498.18 | 2836.64 |
| `/inventory/adjustments` | 41 | 200: 41 | 2434.04 | 2510.07 |
| `/inventory/dashboard` | 42 | 200: 42 | 2427.96 | 2708.38 |
| `/inventory/dashboard/activities` | 43 | 200: 43 | 2152.45 | 2378.79 |
| `/inventory/stock-management` | 42 | 200: 42 | 2619.89 | 2917.94 |
| `/inventory/stock-transfers` | 43 | 200: 43 | 2578.74 | 2811.40 |
| `/invoices` | 44 | 200: 44 | 2695.97 | 3105.63 |
| `/leaves` | 45 | 200: 45 | 2645.10 | 3068.91 |
| `/messages` | 46 | 200: 46 | 2679.50 | 2894.20 |
| `/order-reasons` | 45 | 200: 45 | 2638.52 | 2903.14 |
| `/orders` | 46 | 200: 46 | 3028.59 | 3257.67 |
| `/orders/create` | 47 | 200: 47 | 2701.25 | 2859.39 |
| `/payments` | 46 | 200: 46 | 2872.10 | 3078.36 |
| `/procurement/goods-receipts` | 44 | 200: 44 | 2787.61 | 3053.31 |
| `/procurement/purchase-orders` | 42 | 200: 42 | 2755.68 | 3002.84 |
| `/procurement/suppliers` | 35 | 200: 35 | 2745.07 | 2944.44 |
| `/promotions/coupons` | 34 | 200: 34 | 2769.95 | 2870.94 |
| `/promotions/offers` | 33 | 200: 33 | 2740.07 | 2768.58 |
| `/promotions/referral-programs` | 31 | 200: 31 | 2691.51 | 2747.47 |
| `/refunds` | 31 | 200: 31 | 2827.61 | 3267.23 |
| `/reports` | 30 | 200: 30 | 2641.84 | 2747.29 |
| `/returns` | 30 | 200: 30 | 2962.85 | 3111.04 |
| `/roles-permissions` | 30 | 200: 30 | 2650.88 | 2740.06 |
| `/security` | 30 | 200: 30 | 2621.27 | 2963.01 |
| `/shipping/services` | 30 | 200: 30 | 2797.01 | 2831.34 |
| `/shipping/settings` | 29 | 200: 29 | 2790.46 | 2919.72 |
| `/shipping/shipments` | 28 | 200: 28 | 2550.44 | 3203.64 |
| `/targets` | 26 | 200: 26 | 2739.34 | 2928.13 |
| `/targets/create` | 26 | 302: 26 | 2406.47 | 2472.93 |
| `/targets/import` | 25 | 302: 25 | 2560.71 | 2630.73 |
| `/teams` | 24 | 200: 24 | 2452.39 | 2869.69 |
| `/users` | 24 | 200: 24 | 2501.91 | 2760.83 |
| `/villages` | 22 | 200: 22 | 2376.76 | 2818.44 |

## apis

Threshold failures: none.

| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |
| ---: | ---: | ---: | ---: |
| 3692 | 0.00% | 861.90 | 1136.54 |

PHP process-group CPU: 219.36 CPU seconds over 182.77 wall seconds (120.0% of one core on average). Peak summed RSS: 461.9 MiB; shared pages may be counted more than once. Database CPU is not included.

| Route | Samples | Status counts | p95 (ms) | Max (ms) |
| --- | ---: | --- | ---: | ---: |
| `/api/activities/recent` | 28 | 200: 28 | 792.26 | 927.30 |
| `/api/admin/audit-logs` | 29 | 200: 29 | 860.04 | 1068.60 |
| `/api/attendances` | 30 | 200: 30 | 662.65 | 805.78 |
| `/api/attendances/export/detailed` | 1 | 200: 1 | 76.64 | 76.64 |
| `/api/attendances/export/summary` | 1 | 200: 1 | 75.21 | 75.21 |
| `/api/attendances/{attendance}` | 30 | 200: 30 | 627.21 | 695.69 |
| `/api/attributes` | 30 | 200: 30 | 666.27 | 820.17 |
| `/api/attributes/{attribute}` | 31 | 200: 31 | 776.34 | 1027.09 |
| `/api/brands` | 31 | 200: 31 | 793.13 | 1009.29 |
| `/api/brands/{brand}` | 32 | 200: 32 | 890.66 | 1208.63 |
| `/api/call-tags` | 33 | 200: 33 | 716.61 | 820.42 |
| `/api/call-tags-admin` | 34 | 200: 34 | 1086.78 | 1368.45 |
| `/api/call-tags/{tag}/form` | 35 | 200: 35 | 706.02 | 889.29 |
| `/api/categories` | 36 | 200: 36 | 714.54 | 807.24 |
| `/api/categories/{category}` | 36 | 404: 36 | 658.96 | 861.99 |
| `/api/chat/active-status` | 37 | 200: 37 | 655.46 | 970.55 |
| `/api/chat/conversations` | 38 | 200: 38 | 585.01 | 752.98 |
| `/api/chat/conversations/{conversation}` | 39 | 404: 39 | 813.32 | 919.64 |
| `/api/chat/conversations/{conversation}/messages` | 40 | 404: 40 | 750.63 | 1030.04 |
| `/api/chat/search` | 40 | 200: 40 | 754.44 | 968.42 |
| `/api/chat/unread-count` | 41 | 200: 41 | 804.43 | 1092.26 |
| `/api/chat/users` | 42 | 200: 42 | 686.66 | 1000.08 |
| `/api/complaints` | 43 | 200: 43 | 822.42 | 992.86 |
| `/api/complaints/export` | 1 | 200: 1 | 85.24 | 85.24 |
| `/api/complaints/stats` | 44 | 200: 44 | 804.77 | 822.49 |
| `/api/complaints/{complaint}` | 44 | 404: 44 | 796.62 | 1345.11 |
| `/api/credit-notes` | 42 | 200: 42 | 876.92 | 1075.41 |
| `/api/customer-settings/{type}` | 42 | 200: 42 | 693.37 | 918.24 |
| `/api/customers` | 42 | 200: 42 | 826.47 | 1008.08 |
| `/api/customers/check-referral/{code}` | 41 | 200: 41 | 791.90 | 933.31 |
| `/api/customers/{customer}` | 41 | 200: 41 | 1000.97 | 1235.68 |
| `/api/dashboard` | 41 | 200: 41 | 880.26 | 1020.23 |
| `/api/departments` | 41 | 200: 41 | 704.47 | 828.05 |
| `/api/departments/{department}` | 40 | 404: 40 | 632.29 | 693.33 |
| `/api/files` | 40 | 200: 40 | 777.65 | 874.77 |
| `/api/files/preview` | 1 | 404: 1 | 23.75 | 23.75 |
| `/api/holidays` | 40 | 200: 40 | 764.63 | 898.19 |
| `/api/holidays/{holiday}` | 40 | 404: 40 | 741.60 | 1037.19 |
| `/api/hr-settings/{type}` | 40 | 200: 40 | 812.76 | 1322.43 |
| `/api/hsn-codes` | 40 | 200: 40 | 1210.45 | 1408.56 |
| `/api/hsn-codes/{hsn_code}` | 40 | 404: 40 | 806.01 | 1037.09 |
| `/api/inventory/adjustments` | 40 | 200: 40 | 748.05 | 1292.82 |
| `/api/inventory/adjustments/options` | 40 | 200: 40 | 885.13 | 1236.90 |
| `/api/inventory/adjustments/{inventoryAdjustment}` | 40 | 404: 40 | 722.45 | 911.83 |
| `/api/inventory/stocks` | 40 | 200: 40 | 1141.00 | 1351.08 |
| `/api/inventory/stocks/show` | 40 | 422: 40 | 766.08 | 930.41 |
| `/api/inventory/stocks/warehouse-options` | 39 | 200: 39 | 872.04 | 912.26 |
| `/api/inventory/transfers` | 38 | 200: 38 | 851.41 | 879.67 |
| `/api/inventory/transfers/options` | 38 | 200: 38 | 775.89 | 1113.09 |
| `/api/inventory/transfers/{transfer}` | 38 | 200: 38 | 747.86 | 919.69 |
| `/api/invoices` | 38 | 200: 38 | 748.89 | 916.56 |
| `/api/invoices/{invoice}` | 38 | 200: 38 | 842.50 | 905.80 |
| `/api/invoices/{invoice}/pdf` | 1 | 200: 1 | 738.47 | 738.47 |
| `/api/leave-balances` | 38 | 200: 38 | 771.84 | 902.84 |
| `/api/leave-balances/{leave_balance}` | 38 | 404: 38 | 775.31 | 1076.33 |
| `/api/leaves` | 38 | 200: 38 | 902.51 | 1343.54 |
| `/api/leaves/{leave}` | 37 | 404: 37 | 795.71 | 904.28 |
| `/api/order-reasons/{type}` | 37 | 200: 37 | 695.18 | 1149.21 |
| `/api/orders` | 37 | 200: 37 | 1173.56 | 1299.51 |
| `/api/orders/bulk-print` | 1 | 422: 1 | 68.57 | 68.57 |
| `/api/orders/export` | 1 | 200: 1 | 985.09 | 985.09 |
| `/api/orders/{order}` | 36 | 200: 36 | 938.25 | 963.56 |
| `/api/org-chart` | 36 | 200: 36 | 682.59 | 727.87 |
| `/api/payments` | 36 | 200: 36 | 805.78 | 1207.65 |
| `/api/payments/{payment}` | 36 | 200: 36 | 915.54 | 980.34 |
| `/api/permissions` | 36 | 200: 36 | 831.81 | 917.72 |
| `/api/permissions/options` | 36 | 200: 36 | 739.35 | 1213.29 |
| `/api/permissions/{permission}` | 36 | 200: 36 | 819.09 | 1063.48 |
| `/api/procurement/goods-receipts` | 35 | 200: 35 | 739.85 | 799.27 |
| `/api/procurement/goods-receipts/{receipt}/pdf` | 1 | 404: 1 | 103.74 | 103.74 |
| `/api/procurement/purchase-orders` | 35 | 200: 35 | 771.66 | 835.50 |
| `/api/procurement/purchase-orders/{order}/payments` | 34 | 404: 34 | 832.99 | 927.56 |
| `/api/procurement/purchase-orders/{order}/pdf` | 1 | 404: 1 | 94.15 | 94.15 |
| `/api/procurement/suppliers` | 34 | 200: 34 | 761.38 | 1010.54 |
| `/api/procurement/suppliers/{supplier}` | 34 | 404: 34 | 687.08 | 847.86 |
| `/api/products` | 34 | 200: 34 | 906.04 | 1266.37 |
| `/api/products-search-api` | 34 | 200: 34 | 815.86 | 913.76 |
| `/api/products/export` | 1 | 200: 1 | 89.63 | 89.63 |
| `/api/products/{product}` | 34 | 200: 34 | 874.11 | 1019.97 |
| `/api/promotions/coupons` | 34 | 200: 34 | 817.19 | 856.83 |
| `/api/promotions/offers` | 34 | 200: 34 | 1008.03 | 1446.66 |
| `/api/promotions/referral-programs` | 34 | 200: 34 | 900.72 | 976.87 |
| `/api/refunds` | 34 | 200: 34 | 915.36 | 1278.48 |
| `/api/refunds/{refund}` | 34 | 200: 34 | 868.31 | 932.51 |
| `/api/reports` | 34 | 200: 34 | 1228.11 | 1312.46 |
| `/api/returns` | 34 | 200: 34 | 902.46 | 1184.74 |
| `/api/returns/{orderReturn}` | 34 | 200: 34 | 755.63 | 847.99 |
| `/api/roles` | 33 | 200: 33 | 747.56 | 820.78 |
| `/api/roles/options` | 33 | 200: 33 | 837.38 | 927.23 |
| `/api/roles/{role}` | 33 | 200: 33 | 692.75 | 861.25 |
| `/api/security/activity` | 32 | 200: 32 | 751.39 | 783.42 |
| `/api/security/sessions` | 32 | 200: 32 | 695.39 | 927.39 |
| `/api/shipping/awb-logs/{officeId}` | 32 | 200: 32 | 728.89 | 804.00 |
| `/api/shipping/offices/{officeId}/ranges` | 32 | 200: 32 | 802.67 | 920.35 |
| `/api/shipping/services` | 31 | 200: 31 | 916.05 | 1158.39 |
| `/api/shipping/services/provider-options` | 31 | 200: 31 | 768.95 | 1150.27 |
| `/api/shipping/shipments` | 31 | 200: 31 | 923.67 | 1139.85 |
| `/api/shipping/shipments/{shipment}/tracking` | 31 | 200: 31 | 672.49 | 1136.22 |
| `/api/tax-rates` | 31 | 200: 31 | 1007.05 | 1351.18 |
| `/api/tax-rates/{tax_rate}` | 30 | 404: 30 | 679.08 | 821.01 |
| `/api/teams` | 30 | 200: 30 | 831.97 | 868.76 |
| `/api/teams/{team}` | 30 | 200: 30 | 664.51 | 764.55 |
| `/api/uom` | 30 | 200: 30 | 765.86 | 917.34 |
| `/api/uom/{uom}` | 30 | 200: 30 | 713.64 | 779.89 |
| `/api/users` | 30 | 200: 30 | 976.58 | 1258.25 |
| `/api/users/{user}` | 30 | 200: 30 | 909.84 | 1117.76 |
| `/api/users/{user}/login-history` | 30 | 200: 30 | 721.90 | 821.81 |
| `/api/villages` | 30 | 200: 30 | 827.98 | 896.59 |
| `/api/villages/export` | 1 | 200: 1 | 86.90 | 86.90 |
| `/api/villages/import-template` | 1 | 200: 1 | 81.87 | 81.87 |
| `/api/villages/search` | 30 | 200: 30 | 673.26 | 803.07 |
| `/api/villages/services-options` | 30 | 200: 30 | 814.43 | 860.28 |
| `/api/villages/{village}` | 30 | 200: 30 | 868.48 | 1050.58 |
| `/api/warehouses` | 30 | 200: 30 | 649.37 | 969.77 |
| `/api/warehouses/{warehouse}` | 30 | 200: 30 | 697.75 | 971.74 |
| `/docs/api` | 1 | 200: 1 | 8102.44 | 8102.44 |
| `/docs/api.json` | 1 | 200: 1 | 11567.53 | 11567.53 |
| `/products-search-api` | 28 | 200: 28 | 692.36 | 731.99 |
