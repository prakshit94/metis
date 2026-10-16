# Controller and route inventory — 17 October 2026

Static route-action checks establish that a method exists and is public. They do not establish workflow correctness. Query-pattern counts are review signals, not confirmed defects.

| Controller | Route entries | Read entries | Write entries | Query signals |
| --- | ---: | ---: | ---: | --- |
| `App\Http\Controllers\Api\Chat\ConversationController` | 6 | 3 | 3 |  |
| `App\Http\Controllers\Api\Chat\GroupController` | 9 | 0 | 9 | loops: 1 |
| `App\Http\Controllers\Api\Chat\MessageController` | 8 | 1 | 7 | loops: 2 |
| `App\Http\Controllers\Api\Chat\PresenceController` | 2 | 1 | 1 | get: 1 |
| `App\Http\Controllers\Api\Chat\SearchController` | 1 | 1 | 0 |  |
| `App\Http\Controllers\Api\Chat\UserController` | 1 | 1 | 0 |  |
| `App\Http\Controllers\AuditLogController` | 8 | 3 | 5 | get: 1, loops: 11 |
| `App\Http\Controllers\GlobalSearchController` | 1 | 1 | 0 |  |
| `App\Http\Controllers\ReferralProgramController` | 12 | 2 | 10 | get: 2, loops: 1 |
| `App\Http\Controllers\TargetController` | 11 | 6 | 5 | get: 6, date wrappers: 4, loops: 14 |
| `App\Http\Controllers\Web\Chat\ChatController` | 1 | 1 | 0 |  |
| `App\Modules\Catalog\Controllers\BrandController` | 5 | 2 | 3 |  |
| `App\Modules\Catalog\Controllers\CatalogController` | 8 | 8 | 0 |  |
| `App\Modules\Catalog\Controllers\CategoryController` | 5 | 2 | 3 |  |
| `App\Modules\Catalog\Controllers\HsnCodeController` | 5 | 2 | 3 |  |
| `App\Modules\Catalog\Controllers\ProductAttributeController` | 8 | 2 | 6 |  |
| `App\Modules\Catalog\Controllers\ProductController` | 18 | 5 | 13 | get: 11, loops: 9 |
| `App\Modules\Catalog\Controllers\TaxRateController` | 5 | 2 | 3 |  |
| `App\Modules\Catalog\Controllers\UnitOfMeasureController` | 5 | 2 | 3 |  |
| `App\Modules\Catalog\Controllers\WarehouseController` | 5 | 2 | 3 |  |
| `App\Modules\Core\Controllers\FileManagerController` | 8 | 2 | 6 | get: 2, loops: 3 |
| `App\Modules\Core\Controllers\PageController` | 35 | 35 | 0 | get: 37, cursor: 4, loops: 19 |
| `App\Modules\Core\Controllers\ShippingController` | 14 | 5 | 9 | get: 4, loops: 6 |
| `App\Modules\Core\Controllers\ShippingSettingsController` | 8 | 3 | 5 | get: 1, loops: 2 |
| `App\Modules\Core\Controllers\VillageController` | 13 | 6 | 7 | get: 11, loops: 6 |
| `App\Modules\Customers\Controllers\CustomerAddressController` | 3 | 0 | 3 |  |
| `App\Modules\Customers\Controllers\CustomerController` | 12 | 4 | 8 | get: 13, loops: 3 |
| `App\Modules\Customers\Controllers\CustomerSettingsController` | 6 | 2 | 4 | get: 1 |
| `App\Modules\Inventory\Controllers\GoodsReceiptController` | 12 | 4 | 8 | get: 1, loops: 2 |
| `App\Modules\Inventory\Controllers\InventoryAdjustmentController` | 9 | 3 | 6 | get: 4, loops: 3 |
| `App\Modules\Inventory\Controllers\PurchaseOrderController` | 22 | 7 | 15 | get: 6, loops: 1 |
| `App\Modules\Inventory\Controllers\StockManagementController` | 4 | 3 | 1 | get: 1 |
| `App\Modules\Inventory\Controllers\StockTransferController` | 10 | 3 | 7 | get: 3, loops: 3 |
| `App\Modules\Inventory\Controllers\SupplierController` | 11 | 3 | 8 |  |
| `App\Modules\Inventory\Controllers\WarehouseDashboardController` | 2 | 2 | 0 | get: 1 |
| `App\Modules\Orders\Controllers\CallTagAdminController` | 10 | 2 | 8 | get: 4, loops: 2 |
| `App\Modules\Orders\Controllers\CallTaggingController` | 6 | 4 | 2 | get: 2, loops: 2 |
| `App\Modules\Orders\Controllers\CouponController` | 2 | 0 | 2 | get: 2, loops: 1 |
| `App\Modules\Orders\Controllers\CreditNoteController` | 9 | 3 | 6 | get: 6 |
| `App\Modules\Orders\Controllers\IndiaPostWebhookController` | 1 | 0 | 1 | loops: 1 |
| `App\Modules\Orders\Controllers\InvoiceController` | 9 | 5 | 4 | get: 2, loops: 3 |
| `App\Modules\Orders\Controllers\OrderComplaintController` | 13 | 5 | 8 | get: 12, cursor: 2, loops: 7 |
| `App\Modules\Orders\Controllers\OrderController` | 50 | 17 | 33 | get: 40, cursor: 2, loops: 16 |
| `App\Modules\Orders\Controllers\OrderReasonController` | 6 | 2 | 4 | get: 1 |
| `App\Modules\Orders\Controllers\OrderReturnController` | 14 | 4 | 10 | get: 6, loops: 9 |
| `App\Modules\Orders\Controllers\PaymentController` | 10 | 4 | 6 | get: 2, loops: 2 |
| `App\Modules\Orders\Controllers\PaymentImportController` | 3 | 1 | 2 | loops: 4 |
| `App\Modules\Orders\Controllers\PromotionsController` | 14 | 4 | 10 | get: 4 |
| `App\Modules\Orders\Controllers\RefundController` | 5 | 3 | 2 | get: 1 |
| `App\Modules\Users\Controllers\AttendanceController` | 10 | 4 | 6 | get: 5, loops: 3 |
| `App\Modules\Users\Controllers\AuthController` | 5 | 1 | 5 | loops: 1 |
| `App\Modules\Users\Controllers\BulkUserController` | 1 | 0 | 1 |  |
| `App\Modules\Users\Controllers\HolidayController` | 6 | 2 | 4 |  |
| `App\Modules\Users\Controllers\LeaveBalanceController` | 6 | 2 | 4 |  |
| `App\Modules\Users\Controllers\LeaveController` | 2 | 0 | 2 | get: 2, loops: 2 |
| `App\Modules\Users\Controllers\PermissionController` | 8 | 3 | 5 | get: 1 |
| `App\Modules\Users\Controllers\RoleController` | 8 | 3 | 5 | get: 1 |
| `App\Modules\Users\Controllers\SecurityController` | 8 | 2 | 6 | get: 3 |
| `App\Modules\Users\Controllers\TeamController` | 5 | 2 | 3 | get: 1 |
| `App\Modules\Users\Controllers\UserController` | 13 | 3 | 10 | get: 2, loops: 1 |

## Broken route actions

- `POST /api/orders/import` → `App\Modules\Orders\Controllers\OrderController@bulkImport`
- `POST /api/payments` → `App\Modules\Orders\Controllers\PaymentController@store`
- `POST /api/returns/{orderReturn}/process` → `App\Modules\Orders\Controllers\OrderReturnController@process`

## Review priorities

- Large all-record responses and exports: test with representative row counts; the synthetic HTTP fixture is small.
- Leading-wildcard searches: current search behavior requires matching within strings and can scan many rows.
- Financial and lifecycle serialization: preserve completed/deleted transaction semantics and latest return/shipment state. Existing query regressions cover those cases.
- Polling: the users page requests recent activity every three seconds; HTTP k6 page tests do not execute JavaScript and therefore do not measure this browser-generated traffic.
- Routes that call carriers or webhooks need a stubbed integration before repeated write/workflow stress tests.
- Pagination, background exports, search semantics, and polling cadence were not changed as part of the bounded query fixes.
