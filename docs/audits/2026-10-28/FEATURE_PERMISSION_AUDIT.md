# Feature permission audit — 28 October 2026

Later update: Mail Configuration now additionally requires the protected master account (user ID 1 with Super Admin). Delegated permissions and other Super Admins cannot access it. See `SECURITY_METHOD_CHOICE_UI.md`.

The change is limited to authorization gates, permission catalog/default seeding, compatibility grants and visibility of permission-controlled navigation/actions. No order transitions, inventory calculations, financial settlement rules, HR review hierarchy, pricing or record-visibility/LOB rules were rewritten.

## Implemented

- Pages enforce their feature permissions independently of sidebar visibility, for browser and mobile API paths.
- Financial actions use their own invoice/payment/refund/credit-note permissions. Returns have separate create/process/approve/cancel/QC/finance permissions.
- Bulk order status changes have individual `orders.bulk_status.<status>` permissions in addition to the existing bulk-operation gate.
- Split procurement invoice/payment actions, stock send/receive/cancel, inventory approval, holiday/leave-balance/HR settings, call-tag CRUD, file actions, target import, report export and each report type.
- Chat endpoint gates now enforce view/create/group-create/message edit/delete/group-management permissions. Existing conversation membership and group-owner rules still apply. Personal pin/archive actions remain available to chat viewers.
- Mail configuration view/save/test and admin-required user two-step login have distinct permissions. Password, verified-factor, impersonation and Super Admin safeguards remain.
- Product export no longer bypasses its export permission through view access. Permanent role/permission deletion no longer accidentally requires the separate soft-delete permission as well.
- Navigation and action visibility use matching permission names; My Security remains discoverable for every signed-in account.

## Safe installation and seeding

`2026_10_28_140000_complete_feature_permissions.php` was applied to the local application. It adds catalog entries and copies the previous broader grants to their corresponding granular actions, preserving full role/direct-user pivot rows and team identifiers. This preserves previously authorized workflows while allowing administrators to revoke individual new grants. It does not assign a blanket new role, create a user or reset credentials.

Fresh installations use the updated `RolesAndPermissionsSeeder`, whose default roles expand through the same compatibility map. For an existing installation, use the dedicated catalog-only refresh:

```sh
php artisan db:seed --class=FeaturePermissionsSeeder
```

This refresh adds missing catalog entries without resetting roles or re-copying old grants over administrator revocations. Intentionally soft-deleted permissions are not resurrected by the additive updater. Existing Super Admin bypass behavior remains unchanged. Permission-only grants normally need the corresponding view permission for the page that exposes their controls.

## Intentional exceptions

Login and second-factor challenge/enrollment endpoints remain public challenge-authorized flows. Logout, the current user's profile/token operations, own sessions, security activity, emergency account lockdown, and owner second-factor enrollment/recovery remain authenticated self-service operations. Blocking these behind administrator permissions could lock users out of required enrollment or recovery. Impersonation retains its existing Super Admin/browser-session restriction. Public uploaded branding/media URLs, static assets and provider-authenticated webhooks retain their current access model.

Routes using inline or action-dependent authorization retain those checks: user detail/photo/activation/permanent deletion, role/permission picker options, bulk user/catalog/procurement/HR/village actions, personal security, attendance correction/roster reviews and the per-user activity feed. The route matrix below labels those as inline/self-service rather than suggesting they are unrestricted.

## Verification and manual testing

421 unique catalog permissions cover 585 route permission gates and all literal application/template permission checks. All 616 routes resolve, 1,302 response-method checks pass, 17 changed Blade views compile, PHP syntax and the production build pass. Nine isolated permission-migration checks verify preservation of custom role grants, direct grants including null and distinct team IDs, idempotence, soft-deletion, no user/role reset, explicit revocations and safe rollback. Business feature walkthroughs and browser/mobile interaction testing are left to the user as requested.

## Granular grants and compatibility sources

These mappings apply only when installing the additive update or building fresh default roles; runtime authorization does not fall back to old broad grants.

| Permission | Previously authorized by |
|---|---|
| `product-export` | `product-view` |
| `orders.view.unfulfillable` | `orders.view.pending` |
| `orders.view.delivery_attempted` | `orders.view.dispatched` |
| `orders.shipping_label` | `orders.ship`, `orders.bulk_print` |
| `reports-export` | `reports-view` |
| `target-import` | `target-create` |
| `files-view` | `settings-view` |
| `files-create` | `settings-view` |
| `files-edit` | `settings-view` |
| `files-delete` | `settings-view` |
| `files-download` | `settings-view` |
| `calltag-view` | `settings-view` |
| `calltag-create` | `settings-view` |
| `calltag-edit` | `settings-view` |
| `calltag-delete` | `settings-view` |
| `calllog-create` | `customer-view`, `orders.create` |
| `hrsetting-view` | `department-view` |
| `hrsetting-create` | `department-create` |
| `hrsetting-edit` | `department-edit` |
| `hrsetting-delete` | `department-delete` |
| `holiday-view` | `leave-view` |
| `holiday-create` | `leave-create` |
| `holiday-edit` | `leave-edit` |
| `holiday-delete` | `leave-delete` |
| `leavebalance-view` | `leave-view` |
| `leavebalance-create` | `leave-create` |
| `leavebalance-edit` | `leave-edit` |
| `leavebalance-delete` | `leave-delete` |
| `purchaseorder-invoice-upload` | `purchaseorder-create` |
| `purchaseorder-payment-create` | `purchaseorder-create` |
| `inventoryadjustment-approve` | `inventoryadjustment-edit` |
| `stocktransfer-send` | `stocktransfer-edit` |
| `stocktransfer-receive` | `stocktransfer-edit` |
| `stocktransfer-cancel` | `stocktransfer-edit` |
| `invoices.view` | `orders.view` |
| `invoices.edit` | `orders.receipt` |
| `invoices.export` | `orders.view` |
| `invoices.pdf` | `orders.view` |
| `invoices.record-payment` | `orders.receipt` |
| `payments.view` | `orders.view` |
| `payments.create` | `orders.receipt` |
| `payments.edit` | `orders.receipt` |
| `payments.delete` | `orders.receipt` |
| `payments.import` | `orders.receipt` |
| `payments.export` | `orders.view` |
| `refunds.view` | `orders.view` |
| `refunds.edit` | `orders.edit` |
| `credit-notes.view` | `orders.view` |
| `credit-notes.create` | `orders.receipt` |
| `credit-notes.edit` | `orders.receipt` |
| `credit-notes.delete` | `orders.receipt` |
| `returns.view` | `orders.view` |
| `returns.create` | `orders.return` |
| `returns.process` | `orders.return` |
| `returns.qc` | `orders.return` |
| `returns.finance` | `orders.return` |
| `returns.approve` | `orders.return` |
| `returns.cancel` | `orders.return` |
| `messages-view` | `chat-view` |
| `messages-create` | `chat-create` |
| `messages-edit` | `chat-edit` |
| `messages-delete` | `chat-delete` |
| `chat-group-manage` | `chat-edit` |
| `user-security-requirement` | `settings-edit` |
| `security-mail-view` | `settings-edit` |
| `security-mail-edit` | `settings-edit` |
| `security-mail-test` | `settings-edit` |
| `orders.bulk_status.pending` | `orders.bulk_status` |
| `orders.bulk_status.confirmed` | `orders.bulk_status` |
| `orders.bulk_status.processing` | `orders.bulk_status` |
| `orders.bulk_status.ready_to_ship` | `orders.bulk_status` |
| `orders.bulk_status.dispatched` | `orders.bulk_status` |
| `orders.bulk_status.delivered` | `orders.bulk_status` |
| `orders.bulk_status.cancelled` | `orders.bulk_status` |
| `orders.bulk_status.returned` | `orders.bulk_status` |
| `reports.export.sales_overview` | `reports-view` |
| `reports.export.india_post` | `reports-view` |
| `reports.export.product_sales` | `reports-view` |
| `reports.export.sales_region` | `reports-view` |
| `reports.export.payment_reconciliation` | `reports-view` |
| `reports.export.stock_valuation` | `reports-view` |
| `reports.export.stock_ledger` | `reports-view` |
| `reports.export.low_stock` | `reports-view` |
| `reports.export.po_fulfillment` | `reports-view` |
| `reports.export.grn_discrepancy` | `reports-view` |
| `reports.export.call_performance` | `reports-view` |
| `reports.export.call_tagging` | `reports-view` |
| `reports.export.customer_retention` | `reports-view` |
| `reports.export.return_analysis` | `reports-view` |
| `reports.export.audit_trail` | `reports-view` |
| `reports.export.suppliers_report` | `reports-view` |
| `reports.export.attendance_report` | `reports-view` |

## Route/controller permission matrix

The same controller gate applies to browser and API aliases. Inline/self-service checks are summarized above.

| Handler | Feature gate | Routes |
|---|---|---|
| `Http\Controllers\Api\Chat\ConversationController@archive` | `chat-view` | `POST api/chat/conversations/{conversation}/archive` |
| `Http\Controllers\Api\Chat\ConversationController@index` | `chat-view` | `GET|HEAD api/chat/conversations` |
| `Http\Controllers\Api\Chat\ConversationController@pin` | `chat-view` | `POST api/chat/conversations/{conversation}/pin` |
| `Http\Controllers\Api\Chat\ConversationController@show` | `chat-view` | `GET|HEAD api/chat/conversations/{conversation}` |
| `Http\Controllers\Api\Chat\ConversationController@store` | `chat-create`<br>`chat-view` | `POST api/chat/conversations` |
| `Http\Controllers\Api\Chat\ConversationController@unreadCount` | `chat-view` | `GET|HEAD api/chat/unread-count` |
| `Http\Controllers\Api\Chat\GroupController@addMember` | `chat-group-manage`<br>`chat-view` | `POST api/chat/groups/{conversation}/members` |
| `Http\Controllers\Api\Chat\GroupController@destroy` | `chat-delete`<br>`chat-view` | `DELETE api/chat/groups/{conversation}` |
| `Http\Controllers\Api\Chat\GroupController@leave` | `chat-view` | `POST api/chat/groups/{conversation}/leave` |
| `Http\Controllers\Api\Chat\GroupController@removeMember` | `chat-group-manage`<br>`chat-view` | `DELETE api/chat/groups/{conversation}/members`<br>`POST api/chat/groups/{conversation}/members/remove` |
| `Http\Controllers\Api\Chat\GroupController@transferOwner` | `chat-group-manage`<br>`chat-view` | `POST api/chat/groups/{conversation}/transfer-owner` |
| `Http\Controllers\Api\Chat\GroupController@update` | `chat-group-manage`<br>`chat-view` | `PUT api/chat/groups/{conversation}` |
| `Http\Controllers\Api\Chat\GroupController@updateRole` | `chat-group-manage`<br>`chat-view` | `POST api/chat/groups/{conversation}/members/role`<br>`PUT api/chat/groups/{conversation}/members/role` |
| `Http\Controllers\Api\Chat\MessageController@destroy` | `chat-view`<br>`messages-delete`<br>`messages-view` | `DELETE api/chat/messages/{message}`<br>`POST api/chat/messages/{message}/delete` |
| `Http\Controllers\Api\Chat\MessageController@forward` | `chat-view`<br>`messages-create`<br>`messages-view` | `POST api/chat/messages/{message}/forward` |
| `Http\Controllers\Api\Chat\MessageController@index` | `chat-view`<br>`messages-view` | `GET|HEAD api/chat/conversations/{conversation}/messages` |
| `Http\Controllers\Api\Chat\MessageController@markRead` | `chat-view`<br>`messages-view` | `POST api/chat/conversations/{conversation}/read` |
| `Http\Controllers\Api\Chat\MessageController@store` | `chat-view`<br>`messages-create`<br>`messages-view` | `POST api/chat/conversations/{conversation}/messages` |
| `Http\Controllers\Api\Chat\MessageController@update` | `chat-view`<br>`messages-edit`<br>`messages-view` | `POST api/chat/messages/{message}/edit`<br>`PUT api/chat/messages/{message}` |
| `Http\Controllers\Api\Chat\PresenceController@index` | `chat-view` | `GET|HEAD api/chat/active-status` |
| `Http\Controllers\Api\Chat\PresenceController@update` | `chat-view` | `POST api/chat/active-status` |
| `Http\Controllers\Api\Chat\SearchController` | `chat-view` | `GET|HEAD api/chat/search` |
| `Http\Controllers\Api\Chat\UserController@export` | `chat-export`<br>`chat-view` | `GET|HEAD api/chat/users/export` |
| `Http\Controllers\Api\Chat\UserController@index` | `chat-view` | `GET|HEAD api/chat/users` |
| `Http\Controllers\AuditLogController@clearAll` | `audit-log-delete` | `DELETE admin/audit-logs/clear`<br>`DELETE api/admin/audit-logs/clear` |
| `Http\Controllers\AuditLogController@destroy` | `audit-log-delete` | `DELETE admin/audit-logs/destroy`<br>`DELETE api/admin/audit-logs/destroy` |
| `Http\Controllers\AuditLogController@index` | `audit-log-view` | `GET|HEAD admin/audit-logs`<br>`GET|HEAD api/admin/audit-logs` |
| `Http\Controllers\AuditLogController@markAsRead` | Inline / self-service / public exception | `POST api/activities/{id}/read` |
| `Http\Controllers\AuditLogController@recentActivities` | Inline / self-service / public exception | `GET|HEAD api/activities/recent` |
| `Http\Controllers\GlobalSearchController@search` | `search-view` | `GET|HEAD search` |
| `Http\Controllers\ReferralProgramController@bulk` | `promotions-edit` | `POST api/promotions/referral-programs/bulk-action`<br>`POST promotions/referral-programs/bulk-action` |
| `Http\Controllers\ReferralProgramController@destroy` | `promotions-delete` | `DELETE api/promotions/referral-programs/{id}`<br>`DELETE promotions/referral-programs/{id}` |
| `Http\Controllers\ReferralProgramController@index` | `promotions-view` | `GET|HEAD api/promotions/referral-programs`<br>`GET|HEAD promotions/referral-programs` |
| `Http\Controllers\ReferralProgramController@store` | `promotions-create` | `POST api/promotions/referral-programs`<br>`POST promotions/referral-programs` |
| `Http\Controllers\ReferralProgramController@toggle` | `promotions-edit` | `PATCH api/promotions/referral-programs/{id}/toggle`<br>`PATCH promotions/referral-programs/{id}/toggle` |
| `Http\Controllers\ReferralProgramController@update` | `promotions-edit` | `PUT api/promotions/referral-programs/{id}`<br>`PUT promotions/referral-programs/{id}` |
| `Http\Controllers\TargetController@create` | `target-create` | `GET|HEAD targets/create` |
| `Http\Controllers\TargetController@destroy` | `target-delete` | `DELETE api/targets/{target}`<br>`DELETE targets/{target}` |
| `Http\Controllers\TargetController@edit` | `target-edit` | `GET|HEAD targets/{target}/edit` |
| `Http\Controllers\TargetController@export` | `target-export` | `GET|HEAD api/targets/export`<br>`GET|HEAD targets/export` |
| `Http\Controllers\TargetController@import` | `target-import` | `POST api/targets/import`<br>`POST targets/import` |
| `Http\Controllers\TargetController@importForm` | `target-import` | `GET|HEAD targets/import` |
| `Http\Controllers\TargetController@importTemplate` | `target-import` | `GET|HEAD api/targets/import-template`<br>`GET|HEAD targets/import-template` |
| `Http\Controllers\TargetController@index` | `target-view or target-view-all` | `GET|HEAD api/targets`<br>`GET|HEAD targets` |
| `Http\Controllers\TargetController@recalculate` | `target-edit` | `POST api/targets/recalculate`<br>`POST targets/recalculate` |
| `Http\Controllers\TargetController@store` | `target-create` | `POST api/targets`<br>`POST targets` |
| `Http\Controllers\TargetController@update` | `target-edit` | `PUT api/targets/{target}`<br>`PUT targets/{target}` |
| `Http\Controllers\Web\Chat\ChatController` | `chat-view` | `GET|HEAD chat` |
| `Modules\Catalog\Controllers\BrandController@destroy` | `brand-delete` | `DELETE api/brands/{brand}` |
| `Modules\Catalog\Controllers\BrandController@index` | `brand-view` | `GET|HEAD api/brands` |
| `Modules\Catalog\Controllers\BrandController@show` | `brand-view` | `GET|HEAD api/brands/{brand}` |
| `Modules\Catalog\Controllers\BrandController@store` | `brand-create` | `POST api/brands` |
| `Modules\Catalog\Controllers\BrandController@update` | `brand-edit` | `PUT|PATCH api/brands/{brand}` |
| `Modules\Catalog\Controllers\CatalogController@attributes` | `productattribute-view` | `GET|HEAD catalog/attributes` |
| `Modules\Catalog\Controllers\CatalogController@brands` | `brand-view` | `GET|HEAD catalog/brands` |
| `Modules\Catalog\Controllers\CatalogController@categories` | `category-view` | `GET|HEAD catalog/categories` |
| `Modules\Catalog\Controllers\CatalogController@hsnCodes` | `hsncode-view` | `GET|HEAD catalog/hsn-codes` |
| `Modules\Catalog\Controllers\CatalogController@products` | `product-view` | `GET|HEAD catalog/products` |
| `Modules\Catalog\Controllers\CatalogController@taxRates` | `taxrate-view` | `GET|HEAD catalog/tax-rates` |
| `Modules\Catalog\Controllers\CatalogController@uom` | `unitofmeasure-view` | `GET|HEAD catalog/uom` |
| `Modules\Catalog\Controllers\CatalogController@warehouses` | `warehouse-view` | `GET|HEAD catalog/warehouses` |
| `Modules\Catalog\Controllers\CategoryController@destroy` | `category-delete` | `DELETE api/categories/{category}` |
| `Modules\Catalog\Controllers\CategoryController@index` | `category-view` | `GET|HEAD api/categories` |
| `Modules\Catalog\Controllers\CategoryController@show` | `category-view` | `GET|HEAD api/categories/{category}` |
| `Modules\Catalog\Controllers\CategoryController@store` | `category-create` | `POST api/categories` |
| `Modules\Catalog\Controllers\CategoryController@update` | `category-edit` | `PUT|PATCH api/categories/{category}` |
| `Modules\Catalog\Controllers\HsnCodeController@destroy` | `hsncode-delete` | `DELETE api/hsn-codes/{hsn_code}` |
| `Modules\Catalog\Controllers\HsnCodeController@index` | `hsncode-view` | `GET|HEAD api/hsn-codes` |
| `Modules\Catalog\Controllers\HsnCodeController@show` | `hsncode-view` | `GET|HEAD api/hsn-codes/{hsn_code}` |
| `Modules\Catalog\Controllers\HsnCodeController@store` | `hsncode-create` | `POST api/hsn-codes` |
| `Modules\Catalog\Controllers\HsnCodeController@update` | `hsncode-edit` | `PUT|PATCH api/hsn-codes/{hsn_code}` |
| `Modules\Catalog\Controllers\ProductAttributeController@destroy` | `productattribute-delete` | `DELETE api/attributes/{attribute}` |
| `Modules\Catalog\Controllers\ProductAttributeController@destroyValue` | `productattribute-delete` | `DELETE api/attributes/values/{value}` |
| `Modules\Catalog\Controllers\ProductAttributeController@index` | `productattribute-view` | `GET|HEAD api/attributes` |
| `Modules\Catalog\Controllers\ProductAttributeController@show` | `productattribute-view` | `GET|HEAD api/attributes/{attribute}` |
| `Modules\Catalog\Controllers\ProductAttributeController@store` | `productattribute-create` | `POST api/attributes` |
| `Modules\Catalog\Controllers\ProductAttributeController@storeValue` | `productattribute-create` | `POST api/attributes/{attribute}/values` |
| `Modules\Catalog\Controllers\ProductAttributeController@update` | `productattribute-edit` | `PUT|PATCH api/attributes/{attribute}` |
| `Modules\Catalog\Controllers\ProductAttributeController@updateValue` | `productattribute-edit` | `PATCH api/attributes/values/{value}` |
| `Modules\Catalog\Controllers\ProductController@bulkDelete` | Inline / self-service / public exception | `POST api/products/bulk-delete` |
| `Modules\Catalog\Controllers\ProductController@bulkDisableSku` | Inline / self-service / public exception | `POST api/products/bulk-disable-sku` |
| `Modules\Catalog\Controllers\ProductController@bulkEnableSku` | Inline / self-service / public exception | `POST api/products/bulk-enable-sku` |
| `Modules\Catalog\Controllers\ProductController@bulkForceDelete` | Inline / self-service / public exception | `POST api/products/bulk-force-delete` |
| `Modules\Catalog\Controllers\ProductController@bulkRestore` | Inline / self-service / public exception | `POST api/products/bulk-restore` |
| `Modules\Catalog\Controllers\ProductController@bulkStatus` | Inline / self-service / public exception | `POST api/products/bulk-status` |
| `Modules\Catalog\Controllers\ProductController@destroy` | Inline / self-service / public exception | `DELETE api/products/{product}` |
| `Modules\Catalog\Controllers\ProductController@duplicate` | Inline / self-service / public exception | `POST api/products/{product}/duplicate` |
| `Modules\Catalog\Controllers\ProductController@export` | Inline / self-service / public exception | `GET|HEAD api/products/export` |
| `Modules\Catalog\Controllers\ProductController@forceDelete` | Inline / self-service / public exception | `DELETE api/products/{product}/force-delete` |
| `Modules\Catalog\Controllers\ProductController@import` | Inline / self-service / public exception | `POST api/products/import` |
| `Modules\Catalog\Controllers\ProductController@index` | Inline / self-service / public exception | `GET|HEAD api/products` |
| `Modules\Catalog\Controllers\ProductController@restore` | Inline / self-service / public exception | `POST api/products/{product}/restore` |
| `Modules\Catalog\Controllers\ProductController@searchApi` | `orders.create or orders.edit` | `GET|HEAD api/products-search-api`<br>`GET|HEAD products-search-api` |
| `Modules\Catalog\Controllers\ProductController@show` | Inline / self-service / public exception | `GET|HEAD api/products/{product}` |
| `Modules\Catalog\Controllers\ProductController@store` | Inline / self-service / public exception | `POST api/products` |
| `Modules\Catalog\Controllers\ProductController@update` | Inline / self-service / public exception | `PATCH api/products/{product}` |
| `Modules\Catalog\Controllers\TaxRateController@destroy` | `taxrate-delete` | `DELETE api/tax-rates/{tax_rate}` |
| `Modules\Catalog\Controllers\TaxRateController@index` | `taxrate-view` | `GET|HEAD api/tax-rates` |
| `Modules\Catalog\Controllers\TaxRateController@show` | `taxrate-view` | `GET|HEAD api/tax-rates/{tax_rate}` |
| `Modules\Catalog\Controllers\TaxRateController@store` | `taxrate-create` | `POST api/tax-rates` |
| `Modules\Catalog\Controllers\TaxRateController@update` | `taxrate-edit` | `PUT|PATCH api/tax-rates/{tax_rate}` |
| `Modules\Catalog\Controllers\UnitOfMeasureController@destroy` | `unitofmeasure-delete` | `DELETE api/uom/{uom}` |
| `Modules\Catalog\Controllers\UnitOfMeasureController@index` | `unitofmeasure-view` | `GET|HEAD api/uom` |
| `Modules\Catalog\Controllers\UnitOfMeasureController@show` | `unitofmeasure-view` | `GET|HEAD api/uom/{uom}` |
| `Modules\Catalog\Controllers\UnitOfMeasureController@store` | `unitofmeasure-create` | `POST api/uom` |
| `Modules\Catalog\Controllers\UnitOfMeasureController@update` | `unitofmeasure-edit` | `PUT|PATCH api/uom/{uom}` |
| `Modules\Catalog\Controllers\WarehouseController@destroy` | `warehouse-delete` | `DELETE api/warehouses/{warehouse}` |
| `Modules\Catalog\Controllers\WarehouseController@index` | `warehouse-view` | `GET|HEAD api/warehouses` |
| `Modules\Catalog\Controllers\WarehouseController@show` | `warehouse-view` | `GET|HEAD api/warehouses/{warehouse}` |
| `Modules\Catalog\Controllers\WarehouseController@store` | `warehouse-create` | `POST api/warehouses` |
| `Modules\Catalog\Controllers\WarehouseController@update` | `warehouse-edit` | `PUT|PATCH api/warehouses/{warehouse}` |
| `Modules\Core\Controllers\FileManagerController@delete` | `files-delete` | `DELETE api/files` |
| `Modules\Core\Controllers\FileManagerController@download` | `files-download` | `GET|HEAD api/files/download` |
| `Modules\Core\Controllers\FileManagerController@downloadZip` | `files-download` | `POST api/files/download-zip` |
| `Modules\Core\Controllers\FileManagerController@index` | `files-view` | `GET|HEAD api/files` |
| `Modules\Core\Controllers\FileManagerController@preview` | Inline / self-service / public exception | `GET|HEAD api/files/preview` |
| `Modules\Core\Controllers\FileManagerController@rename` | `files-edit` | `POST api/files/rename` |
| `Modules\Core\Controllers\FileManagerController@setDefaultImage` | `settings-edit` | `POST api/files/set-default-image` |
| `Modules\Core\Controllers\FileManagerController@setLoginBackground` | `settings-edit` | `POST api/files/login-background` |
| `Modules\Core\Controllers\FileManagerController@upload` | `files-create` | `POST api/files/upload` |
| `Modules\Core\Controllers\PageController@analytics` | `analytics-view` | `GET|HEAD analytics` |
| `Modules\Core\Controllers\PageController@analyticsData` | `analytics-view` | `GET|HEAD analytics/data`<br>`GET|HEAD api/analytics/data` |
| `Modules\Core\Controllers\PageController@attendances` | `attendance-view` | `GET|HEAD attendances` |
| `Modules\Core\Controllers\PageController@calendar` | `calendar-view` | `GET|HEAD calendar` |
| `Modules\Core\Controllers\PageController@customers` | `customer-view` | `GET|HEAD customers` |
| `Modules\Core\Controllers\PageController@dashboard` | `dashboard-view or warehouse-dashboard-view` | `GET|HEAD /`<br>`GET|HEAD api/dashboard` |
| `Modules\Core\Controllers\PageController@departments` | `department-view` | `GET|HEAD departments` |
| `Modules\Core\Controllers\PageController@elementsAlerts` | `settings-view` | `GET|HEAD elements/alerts` |
| `Modules\Core\Controllers\PageController@elementsBadges` | `settings-view` | `GET|HEAD elements/badges` |
| `Modules\Core\Controllers\PageController@elementsButtons` | `settings-view` | `GET|HEAD elements/buttons` |
| `Modules\Core\Controllers\PageController@elementsCards` | `settings-view` | `GET|HEAD elements/cards` |
| `Modules\Core\Controllers\PageController@elementsForms` | `settings-view` | `GET|HEAD elements/forms` |
| `Modules\Core\Controllers\PageController@elementsModals` | `settings-view` | `GET|HEAD elements/modals` |
| `Modules\Core\Controllers\PageController@elementsOverview` | `settings-view` | `GET|HEAD elements` |
| `Modules\Core\Controllers\PageController@elementsTables` | `settings-view` | `GET|HEAD elements/tables` |
| `Modules\Core\Controllers\PageController@exportReports` | `reports-export` | `GET|HEAD api/reports/export`<br>`GET|HEAD reports/export` |
| `Modules\Core\Controllers\PageController@files` | `files-view` | `GET|HEAD files` |
| `Modules\Core\Controllers\PageController@forms` | `forms-view` | `GET|HEAD forms` |
| `Modules\Core\Controllers\PageController@help` | `help-view` | `GET|HEAD help` |
| `Modules\Core\Controllers\PageController@inventoryAdjustments` | `inventoryadjustment-view` | `GET|HEAD inventory/adjustments` |
| `Modules\Core\Controllers\PageController@leaves` | `leave-view` | `GET|HEAD leaves` |
| `Modules\Core\Controllers\PageController@login` | Inline / self-service / public exception | `GET|HEAD login` |
| `Modules\Core\Controllers\PageController@messages` | `messages-view` | `GET|HEAD messages` |
| `Modules\Core\Controllers\PageController@reports` | `reports-view` | `GET|HEAD api/reports`<br>`GET|HEAD reports` |
| `Modules\Core\Controllers\PageController@rolesPermissions` | `role-view or permission-view` | `GET|HEAD roles-permissions` |
| `Modules\Core\Controllers\PageController@security` | Inline / self-service / public exception | `GET|HEAD security` |
| `Modules\Core\Controllers\PageController@shipments` | `shipping-view` | `GET|HEAD shipping/shipments` |
| `Modules\Core\Controllers\PageController@shippingServices` | `shipping-view` | `GET|HEAD shipping/services` |
| `Modules\Core\Controllers\PageController@stockManagement` | `stockmanagement-view` | `GET|HEAD inventory/stock-management` |
| `Modules\Core\Controllers\PageController@stockTransfers` | `stocktransfer-view` | `GET|HEAD inventory/stock-transfers` |
| `Modules\Core\Controllers\PageController@teams` | `team-view` | `GET|HEAD teams` |
| `Modules\Core\Controllers\PageController@users` | `user-view` | `GET|HEAD users` |
| `Modules\Core\Controllers\PageController@villages` | `village-view` | `GET|HEAD villages` |
| `Modules\Core\Controllers\ShippingController@addTrackingEvent` | `shipping-create` | `POST api/shipping/shipments/{shipment}/tracking-event` |
| `Modules\Core\Controllers\ShippingController@destroyService` | `shipping-delete` | `DELETE api/shipping/services/{service}` |
| `Modules\Core\Controllers\ShippingController@fetchLiveTracking` | `shipping-view` | `GET|HEAD api/shipping/shipments/{shipment}/live-tracking` |
| `Modules\Core\Controllers\ShippingController@providerOptions` | `shipping-view` | `GET|HEAD api/shipping/services/provider-options` |
| `Modules\Core\Controllers\ShippingController@returnShipment` | `shipping-edit` | `POST api/shipping/shipments/{shipment}/return` |
| `Modules\Core\Controllers\ShippingController@servicesBulk` | `shipping-edit` | `POST api/shipping/services/bulk-action` |
| `Modules\Core\Controllers\ShippingController@servicesIndex` | `shipping-view` | `GET|HEAD api/shipping/services` |
| `Modules\Core\Controllers\ShippingController@shipmentsBulk` | `shipping-edit` | `POST api/shipping/shipments/bulk-action` |
| `Modules\Core\Controllers\ShippingController@shipmentsIndex` | `shipping-view` | `GET|HEAD api/shipping/shipments` |
| `Modules\Core\Controllers\ShippingController@storeService` | `shipping-create` | `POST api/shipping/services` |
| `Modules\Core\Controllers\ShippingController@toggleService` | `shipping-edit` | `POST api/shipping/services/{service}/toggle` |
| `Modules\Core\Controllers\ShippingController@trackingEvents` | `shipping-view` | `GET|HEAD api/shipping/shipments/{shipment}/tracking` |
| `Modules\Core\Controllers\ShippingController@updateService` | `shipping-edit` | `PATCH api/shipping/services/{service}` |
| `Modules\Core\Controllers\ShippingController@updateShipment` | `shipping-edit` | `PATCH api/shipping/shipments/{shipment}` |
| `Modules\Core\Controllers\ShippingController@updateShipmentStatus` | `shipping-edit` | `POST api/shipping/shipments/{shipment}/status` |
| `Modules\Core\Controllers\ShippingSettingsController@activateRange` | `settings-edit` | `POST api/shipping/ranges/{rangeId}/activate` |
| `Modules\Core\Controllers\ShippingSettingsController@awbLogs` | `shipping-view` | `GET|HEAD api/shipping/awb-logs/{officeId}` |
| `Modules\Core\Controllers\ShippingSettingsController@deleteRange` | `settings-edit` | `DELETE api/shipping/ranges/{rangeId}` |
| `Modules\Core\Controllers\ShippingSettingsController@getRanges` | `shipping-view` | `GET|HEAD api/shipping/offices/{officeId}/ranges` |
| `Modules\Core\Controllers\ShippingSettingsController@index` | `shipping-view` | `GET|HEAD api/shipping/settings`<br>`GET|HEAD shipping/settings` |
| `Modules\Core\Controllers\ShippingSettingsController@store` | `settings-edit` | `POST api/shipping/settings` |
| `Modules\Core\Controllers\ShippingSettingsController@storeRange` | `settings-edit` | `POST api/shipping/offices/{officeId}/ranges` |
| `Modules\Core\Controllers\ShippingSettingsController@updateRange` | `settings-edit` | `PUT api/shipping/ranges/{rangeId}` |
| `Modules\Core\Controllers\VillageController@bulkAction` | Inline / self-service / public exception | `POST api/villages/bulk-action` |
| `Modules\Core\Controllers\VillageController@destroy` | `village-delete` | `DELETE api/villages/{village}` |
| `Modules\Core\Controllers\VillageController@export` | `village-export` | `GET|HEAD api/villages/export` |
| `Modules\Core\Controllers\VillageController@exportSelected` | `village-export` | `POST api/villages/export-selected` |
| `Modules\Core\Controllers\VillageController@import` | `village-import` | `POST api/villages/import` |
| `Modules\Core\Controllers\VillageController@importTemplate` | `village-import` | `GET|HEAD api/villages/import-template` |
| `Modules\Core\Controllers\VillageController@index` | `village-view` | `GET|HEAD api/villages` |
| `Modules\Core\Controllers\VillageController@search` | `village-view` | `GET|HEAD api/villages/search` |
| `Modules\Core\Controllers\VillageController@serviceSelection` | `village-view` | `POST api/villages/service-selection` |
| `Modules\Core\Controllers\VillageController@servicesOptions` | `village-view` | `GET|HEAD api/villages/services-options` |
| `Modules\Core\Controllers\VillageController@show` | `village-view` | `GET|HEAD api/villages/{village}` |
| `Modules\Core\Controllers\VillageController@store` | `village-create` | `POST api/villages` |
| `Modules\Core\Controllers\VillageController@syncIndiaPostPincodes` | `village-edit` | `POST api/villages/sync-indiapost` |
| `Modules\Core\Controllers\VillageController@update` | `village-edit` | `PUT|PATCH api/villages/{village}` |
| `Modules\Customers\Controllers\CustomerAddressController@destroy` | `customeraddress-delete` | `DELETE api/customers/{customer}/addresses/{address}` |
| `Modules\Customers\Controllers\CustomerAddressController@store` | `customeraddress-create` | `POST api/customers/{customer}/addresses` |
| `Modules\Customers\Controllers\CustomerAddressController@update` | `customeraddress-edit` | `PUT|PATCH api/customers/{customer}/addresses/{address}` |
| `Modules\Customers\Controllers\CustomerController@bulkAction` | `customer-edit` | `POST api/customers/bulk-action` |
| `Modules\Customers\Controllers\CustomerController@destroy` | `customer-delete` | `DELETE api/customers/{customer}` |
| `Modules\Customers\Controllers\CustomerController@forceDelete` | `customer-delete` | `DELETE api/customers/{customer}/force` |
| `Modules\Customers\Controllers\CustomerController@index` | `customer-view` | `GET|HEAD api/customers` |
| `Modules\Customers\Controllers\CustomerController@placeOrder` | `orders.create` | `POST api/customers/{customer}/orders/place`<br>`POST customers/{customer}/orders/place` |
| `Modules\Customers\Controllers\CustomerController@restore` | `customer-restore` | `PATCH api/customers/{customer}/restore` |
| `Modules\Customers\Controllers\CustomerController@searchByPhone` | `customer-view or orders.create` | `GET|HEAD api/customers/search-by-phone`<br>`GET|HEAD customers/search-by-phone` |
| `Modules\Customers\Controllers\CustomerController@show` | `customer-view` | `GET|HEAD api/customers/{customer}`<br>`GET|HEAD customers/{customer}` |
| `Modules\Customers\Controllers\CustomerController@store` | `customer-create` | `POST api/customers` |
| `Modules\Customers\Controllers\CustomerController@toggleActive` | `customer-edit` | `PATCH api/customers/{customer}/toggle-active` |
| `Modules\Customers\Controllers\CustomerController@update` | `customer-edit` | `PUT|PATCH api/customers/{customer}` |
| `Modules\Customers\Controllers\CustomerSettingsController@destroy` | `settings-edit` | `DELETE api/customer-settings/{type}/{id}` |
| `Modules\Customers\Controllers\CustomerSettingsController@index` | `settings-view` | `GET|HEAD customer-settings` |
| `Modules\Customers\Controllers\CustomerSettingsController@list` | `settings-view` | `GET|HEAD api/customer-settings/{type}` |
| `Modules\Customers\Controllers\CustomerSettingsController@store` | `settings-edit` | `POST api/customer-settings/{type}` |
| `Modules\Customers\Controllers\CustomerSettingsController@toggle` | `settings-edit` | `PATCH api/customer-settings/{type}/{id}/toggle` |
| `Modules\Customers\Controllers\CustomerSettingsController@update` | `settings-edit` | `PUT api/customer-settings/{type}/{id}` |
| `Modules\Inventory\Controllers\GoodsReceiptController@bulkAction` | Inline / self-service / public exception | `POST api/procurement/goods-receipts/bulk`<br>`POST procurement/goods-receipts/bulk` |
| `Modules\Inventory\Controllers\GoodsReceiptController@destroy` | `goodsreceipt-delete` | `DELETE api/procurement/goods-receipts/{receipt}`<br>`DELETE procurement/goods-receipts/{receipt}` |
| `Modules\Inventory\Controllers\GoodsReceiptController@downloadPdf` | `goodsreceipt-view` | `GET|HEAD api/procurement/goods-receipts/{receipt}/pdf`<br>`GET|HEAD procurement/goods-receipts/{receipt}/pdf` |
| `Modules\Inventory\Controllers\GoodsReceiptController@index` | `goodsreceipt-view` | `GET|HEAD api/procurement/goods-receipts`<br>`GET|HEAD procurement/goods-receipts` |
| `Modules\Inventory\Controllers\GoodsReceiptController@restore` | `goodsreceipt-restore` | `POST api/procurement/goods-receipts/{id}/restore`<br>`POST procurement/goods-receipts/{id}/restore` |
| `Modules\Inventory\Controllers\GoodsReceiptController@store` | `goodsreceipt-create` | `POST api/procurement/purchase-orders/{order}/receive`<br>`POST procurement/purchase-orders/{order}/receive` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@approve` | `inventoryadjustment-approve` | `POST api/inventory/adjustments/{inventoryAdjustment}/approve` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@bulkAction` | `inventoryadjustment-approve or inventoryadjustment-delete` | `POST api/inventory/adjustments/bulk-action` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@destroy` | `inventoryadjustment-delete` | `DELETE api/inventory/adjustments/{inventoryAdjustment}` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@index` | `inventoryadjustment-view` | `GET|HEAD api/inventory/adjustments` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@options` | `inventoryadjustment-view` | `GET|HEAD api/inventory/adjustments/options` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@reject` | `inventoryadjustment-approve` | `POST api/inventory/adjustments/{inventoryAdjustment}/reject` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@show` | `inventoryadjustment-view` | `GET|HEAD api/inventory/adjustments/{inventoryAdjustment}` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@store` | `inventoryadjustment-create` | `POST api/inventory/adjustments` |
| `Modules\Inventory\Controllers\InventoryAdjustmentController@update` | `inventoryadjustment-edit` | `PUT|PATCH api/inventory/adjustments/{inventoryAdjustment}` |
| `Modules\Inventory\Controllers\PurchaseOrderController@addPayment` | `purchaseorder-payment-create` | `POST api/procurement/purchase-orders/{order}/payments`<br>`POST procurement/purchase-orders/{order}/payments` |
| `Modules\Inventory\Controllers\PurchaseOrderController@approve` | `purchaseorder-approve` | `POST api/procurement/purchase-orders/{order}/approve`<br>`POST procurement/purchase-orders/{order}/approve` |
| `Modules\Inventory\Controllers\PurchaseOrderController@bulkAction` | Inline / self-service / public exception | `POST api/procurement/purchase-orders/bulk`<br>`POST procurement/purchase-orders/bulk` |
| `Modules\Inventory\Controllers\PurchaseOrderController@bulkDownloadPdf` | `purchaseorder-view` | `GET|HEAD api/procurement/purchase-orders/bulk-pdf`<br>`GET|HEAD procurement/purchase-orders/bulk-pdf` |
| `Modules\Inventory\Controllers\PurchaseOrderController@destroy` | `purchaseorder-delete` | `DELETE api/procurement/purchase-orders/{order}`<br>`DELETE procurement/purchase-orders/{order}` |
| `Modules\Inventory\Controllers\PurchaseOrderController@downloadPdf` | `purchaseorder-view` | `GET|HEAD api/procurement/purchase-orders/{order}/pdf`<br>`GET|HEAD procurement/purchase-orders/{order}/pdf` |
| `Modules\Inventory\Controllers\PurchaseOrderController@getPayments` | `purchaseorder-view` | `GET|HEAD api/procurement/purchase-orders/{order}/payments`<br>`GET|HEAD procurement/purchase-orders/{order}/payments` |
| `Modules\Inventory\Controllers\PurchaseOrderController@index` | `purchaseorder-view` | `GET|HEAD api/procurement/purchase-orders`<br>`GET|HEAD procurement/purchase-orders` |
| `Modules\Inventory\Controllers\PurchaseOrderController@reject` | `purchaseorder-approve` | `POST api/procurement/purchase-orders/{order}/reject`<br>`POST procurement/purchase-orders/{order}/reject` |
| `Modules\Inventory\Controllers\PurchaseOrderController@restore` | `purchaseorder-restore` | `POST api/procurement/purchase-orders/{id}/restore`<br>`POST procurement/purchase-orders/{id}/restore` |
| `Modules\Inventory\Controllers\PurchaseOrderController@store` | `purchaseorder-create` | `POST api/procurement/purchase-orders`<br>`POST procurement/purchase-orders` |
| `Modules\Inventory\Controllers\PurchaseOrderController@uploadInvoice` | `purchaseorder-invoice-upload` | `POST api/procurement/purchase-orders/{order}/invoice`<br>`POST procurement/purchase-orders/{order}/invoice` |
| `Modules\Inventory\Controllers\StockManagementController@index` | `stockmanagement-view` | `GET|HEAD api/inventory/stocks` |
| `Modules\Inventory\Controllers\StockManagementController@setStock` | `stockmanagement-edit` | `POST api/inventory/stocks/set` |
| `Modules\Inventory\Controllers\StockManagementController@show` | `stockmanagement-view` | `GET|HEAD api/inventory/stocks/show` |
| `Modules\Inventory\Controllers\StockManagementController@warehouseOptions` | `stockmanagement-view` | `GET|HEAD api/inventory/stocks/warehouse-options` |
| `Modules\Inventory\Controllers\StockTransferController@bulkAction` | `stocktransfer-send or stocktransfer-receive or stocktransfer-cancel or stocktransfer-delete` | `POST api/inventory/transfers/bulk-action` |
| `Modules\Inventory\Controllers\StockTransferController@cancel` | `stocktransfer-cancel` | `POST api/inventory/transfers/{stockTransfer}/cancel` |
| `Modules\Inventory\Controllers\StockTransferController@destroy` | `stocktransfer-delete` | `DELETE api/inventory/transfers/{stockTransfer}` |
| `Modules\Inventory\Controllers\StockTransferController@index` | `stocktransfer-view` | `GET|HEAD api/inventory/transfers` |
| `Modules\Inventory\Controllers\StockTransferController@options` | `stocktransfer-view or stockmanagement-view` | `GET|HEAD api/inventory/transfers/options` |
| `Modules\Inventory\Controllers\StockTransferController@receive` | `stocktransfer-receive` | `POST api/inventory/transfers/{stockTransfer}/receive` |
| `Modules\Inventory\Controllers\StockTransferController@send` | `stocktransfer-send` | `POST api/inventory/transfers/{stockTransfer}/send` |
| `Modules\Inventory\Controllers\StockTransferController@show` | `stocktransfer-view` | `GET|HEAD api/inventory/transfers/{stockTransfer}` |
| `Modules\Inventory\Controllers\StockTransferController@store` | `stocktransfer-create` | `POST api/inventory/transfers` |
| `Modules\Inventory\Controllers\StockTransferController@update` | `stocktransfer-edit` | `PUT|PATCH api/inventory/transfers/{stockTransfer}` |
| `Modules\Inventory\Controllers\SupplierController@bulkAction` | Inline / self-service / public exception | `POST api/procurement/suppliers/bulk`<br>`POST procurement/suppliers/bulk` |
| `Modules\Inventory\Controllers\SupplierController@destroy` | `supplier-delete` | `DELETE api/procurement/suppliers/{supplier}`<br>`DELETE procurement/suppliers/{supplier}` |
| `Modules\Inventory\Controllers\SupplierController@index` | `supplier-view` | `GET|HEAD api/procurement/suppliers`<br>`GET|HEAD procurement/suppliers` |
| `Modules\Inventory\Controllers\SupplierController@show` | `supplier-view` | `GET|HEAD api/procurement/suppliers/{supplier}` |
| `Modules\Inventory\Controllers\SupplierController@store` | `supplier-create` | `POST api/procurement/suppliers`<br>`POST procurement/suppliers` |
| `Modules\Inventory\Controllers\SupplierController@update` | `supplier-edit` | `PUT|PATCH api/procurement/suppliers/{supplier}`<br>`PUT|PATCH procurement/suppliers/{supplier}` |
| `Modules\Inventory\Controllers\WarehouseDashboardController@activities` | `warehouse-dashboard-view` | `GET|HEAD api/inventory/dashboard/activities`<br>`GET|HEAD inventory/dashboard/activities` |
| `Modules\Inventory\Controllers\WarehouseDashboardController@index` | `warehouse-dashboard-view` | `GET|HEAD api/inventory/dashboard`<br>`GET|HEAD inventory/dashboard` |
| `Modules\Orders\Controllers\CallTagAdminController@bulkAction` | `calltag-edit or calltag-delete` | `POST api/call-tags-admin/bulk-action`<br>`POST call-tags-admin/bulk-action` |
| `Modules\Orders\Controllers\CallTagAdminController@destroy` | `calltag-delete` | `DELETE api/call-tags-admin/{callTag}`<br>`DELETE call-tags-admin/{callTag}` |
| `Modules\Orders\Controllers\CallTagAdminController@index` | `calltag-view` | `GET|HEAD api/call-tags-admin`<br>`GET|HEAD call-tags-admin` |
| `Modules\Orders\Controllers\CallTagAdminController@store` | `calltag-create` | `POST api/call-tags-admin`<br>`POST call-tags-admin` |
| `Modules\Orders\Controllers\CallTagAdminController@update` | `calltag-edit` | `PUT api/call-tags-admin/{callTag}`<br>`PUT call-tags-admin/{callTag}` |
| `Modules\Orders\Controllers\CallTaggingController@getFormFields` | `calllog-create` | `GET|HEAD api/call-tags/{tag}/form`<br>`GET|HEAD call-tags/{tag}/form` |
| `Modules\Orders\Controllers\CallTaggingController@getTags` | `calllog-create` | `GET|HEAD api/call-tags`<br>`GET|HEAD call-tags` |
| `Modules\Orders\Controllers\CallTaggingController@storeCallLog` | `calllog-create` | `POST api/call-logs`<br>`POST call-logs` |
| `Modules\Orders\Controllers\CouponController@validateApi` | `orders.create or orders.edit` | `POST api/coupons/validate`<br>`POST coupons/validate` |
| `Modules\Orders\Controllers\CreditNoteController@destroy` | `credit-notes.delete` | `DELETE api/credit-notes/{creditNote}`<br>`DELETE credit-notes/{creditNote}` |
| `Modules\Orders\Controllers\CreditNoteController@index` | `credit-notes.view` | `GET|HEAD api/credit-notes`<br>`GET|HEAD credit-notes` |
| `Modules\Orders\Controllers\CreditNoteController@searchCustomers` | `credit-notes.create or credit-notes.edit` | `GET|HEAD api/credit-notes/customers/search`<br>`GET|HEAD credit-notes/customers/search` |
| `Modules\Orders\Controllers\CreditNoteController@store` | `credit-notes.create` | `POST api/credit-notes`<br>`POST credit-notes` |
| `Modules\Orders\Controllers\CreditNoteController@update` | `credit-notes.edit` | `PATCH api/credit-notes/{creditNote}`<br>`PUT credit-notes/{creditNote}` |
| `Modules\Orders\Controllers\IndiaPostWebhookController@handle` | Inline / self-service / public exception | `POST api/webhooks/indiapost` |
| `Modules\Orders\Controllers\InvoiceController@bulkStatus` | `invoices.edit` | `POST api/invoices/bulk-status`<br>`POST invoices/bulk-status` |
| `Modules\Orders\Controllers\InvoiceController@downloadPdf` | `invoices.pdf` | `GET|HEAD api/invoices/{invoice}/pdf` |
| `Modules\Orders\Controllers\InvoiceController@exportSelected` | `invoices.export` | `POST api/invoices/export-selected`<br>`POST invoices/export` |
| `Modules\Orders\Controllers\InvoiceController@index` | `invoices.view` | `GET|HEAD api/invoices`<br>`GET|HEAD invoices` |
| `Modules\Orders\Controllers\InvoiceController@recordPayment` | `invoices.record-payment` | `POST api/invoices/{invoice}/payments`<br>`POST invoices/{invoice}/payments` |
| `Modules\Orders\Controllers\InvoiceController@show` | `invoices.view` | `GET|HEAD api/invoices/{invoice}`<br>`GET|HEAD invoices/{invoice}` |
| `Modules\Orders\Controllers\OrderComplaintController@bulkAction` | `complaints.edit` | `POST api/complaints/bulk-action` |
| `Modules\Orders\Controllers\OrderComplaintController@bulkExport` | `complaints.view` | `GET|HEAD api/complaints/export` |
| `Modules\Orders\Controllers\OrderComplaintController@destroy` | `complaints.delete` | `DELETE api/complaints/{complaint}` |
| `Modules\Orders\Controllers\OrderComplaintController@exportSelected` | `complaints.view` | `POST api/complaints/export-selected` |
| `Modules\Orders\Controllers\OrderComplaintController@forceDelete` | `complaints.permanent-delete` | `DELETE api/complaints/{id}/force-delete` |
| `Modules\Orders\Controllers\OrderComplaintController@index` | `complaints.view` | `GET|HEAD api/complaints`<br>`GET|HEAD complaints` |
| `Modules\Orders\Controllers\OrderComplaintController@reply` | `complaints.reply` | `POST api/complaints/{complaint}/reply` |
| `Modules\Orders\Controllers\OrderComplaintController@restore` | `complaints.restore` | `PATCH api/complaints/{id}/restore` |
| `Modules\Orders\Controllers\OrderComplaintController@show` | `complaints.view` | `GET|HEAD api/complaints/{complaint}` |
| `Modules\Orders\Controllers\OrderComplaintController@stats` | `complaints.view` | `GET|HEAD api/complaints/stats` |
| `Modules\Orders\Controllers\OrderComplaintController@store` | `complaints.create` | `POST api/complaints` |
| `Modules\Orders\Controllers\OrderComplaintController@update` | `complaints.edit` | `PUT api/complaints/{complaint}` |
| `Modules\Orders\Controllers\OrderController@bulkExport` | `orders.export` | `GET|HEAD api/orders/export`<br>`GET|HEAD orders/export` |
| `Modules\Orders\Controllers\OrderController@bulkImport` | `orders.create` | `POST api/orders/import` |
| `Modules\Orders\Controllers\OrderController@bulkPrint` | `orders.bulk_print` | `GET|HEAD api/orders/bulk-print`<br>`GET|HEAD orders/bulk-print` |
| `Modules\Orders\Controllers\OrderController@bulkStatus` | `orders.bulk_status` | `POST api/orders/bulk-status`<br>`POST orders/bulk-status` |
| `Modules\Orders\Controllers\OrderController@cancel` | `orders.cancel` | `POST api/orders/{order}/cancel`<br>`POST orders/{order}/cancel` |
| `Modules\Orders\Controllers\OrderController@confirm` | `orders.confirm` | `POST api/orders/{order}/confirm`<br>`POST orders/{order}/confirm` |
| `Modules\Orders\Controllers\OrderController@create` | `orders.create` | `GET|HEAD api/orders/create-options`<br>`GET|HEAD orders/create` |
| `Modules\Orders\Controllers\OrderController@destroy` | `orders.delete` | `DELETE api/orders/{order}`<br>`DELETE orders/{order}` |
| `Modules\Orders\Controllers\OrderController@dispatch` | `orders.dispatch` | `POST api/orders/{order}/dispatch`<br>`POST orders/{order}/dispatch` |
| `Modules\Orders\Controllers\OrderController@downloadInvoice` | `orders.invoice_pdf` | `GET|HEAD api/orders/{order}/invoice-pdf`<br>`GET|HEAD orders/{order}/invoice-pdf` |
| `Modules\Orders\Controllers\OrderController@downloadReceipt` | `orders.cod` | `GET|HEAD api/orders/{order}/cod-pdf`<br>`GET|HEAD orders/{order}/cod-pdf` |
| `Modules\Orders\Controllers\OrderController@downloadShippingLabel` | `orders.shipping_label` | `GET|HEAD api/orders/{order}/shipping-label`<br>`GET|HEAD orders/{order}/shipping-label` |
| `Modules\Orders\Controllers\OrderController@edit` | `orders.edit` | `GET|HEAD api/orders/{order}/edit-options`<br>`GET|HEAD orders/{order}/edit` |
| `Modules\Orders\Controllers\OrderController@exportSelected` | `orders.export` | `POST api/orders/export-selected`<br>`POST orders/export-selected` |
| `Modules\Orders\Controllers\OrderController@generateBulkInvoices` | `orders.generate_invoice` | `POST api/orders/bulk-generate-invoices`<br>`POST orders/bulk-generate-invoices` |
| `Modules\Orders\Controllers\OrderController@generateInvoice` | `orders.generate_invoice` | `POST api/orders/{order}/generate-invoice`<br>`POST orders/{order}/generate-invoice` |
| `Modules\Orders\Controllers\OrderController@importBulkDeliver` | `orders.deliver` | `POST api/orders/import-deliver`<br>`POST orders/import-deliver` |
| `Modules\Orders\Controllers\OrderController@importBulkDeliverTemplate` | `orders.deliver` | `GET|HEAD api/orders/import-deliver-template`<br>`GET|HEAD orders/import-deliver-template` |
| `Modules\Orders\Controllers\OrderController@importBulkReturn` | `orders.return` | `POST api/orders/import-return`<br>`POST orders/import-return` |
| `Modules\Orders\Controllers\OrderController@importBulkReturnTemplate` | `orders.return` | `GET|HEAD api/orders/import-return-template`<br>`GET|HEAD orders/import-return-template` |
| `Modules\Orders\Controllers\OrderController@index` | `orders.view` | `GET|HEAD api/orders`<br>`GET|HEAD orders` |
| `Modules\Orders\Controllers\OrderController@markDelivered` | `orders.deliver` | `POST api/orders/{order}/deliver`<br>`POST orders/{order}/deliver` |
| `Modules\Orders\Controllers\OrderController@markProcessing` | `orders.processing` | `POST api/orders/{order}/processing`<br>`POST orders/{order}/processing` |
| `Modules\Orders\Controllers\OrderController@markReturned` | `orders.return` | `POST api/orders/{order}/return`<br>`POST orders/{order}/return` |
| `Modules\Orders\Controllers\OrderController@nearbySellingInsights` | `orders.create or orders.edit` | `GET|HEAD api/orders/nearby-selling-insights`<br>`GET|HEAD orders/nearby-selling-insights` |
| `Modules\Orders\Controllers\OrderController@preview` | `orders.create` | `POST api/orders/preview` |
| `Modules\Orders\Controllers\OrderController@receipt` | `orders.receipt` | `GET|HEAD api/orders/{order}/receipt`<br>`GET|HEAD orders/{order}/receipt` |
| `Modules\Orders\Controllers\OrderController@revertStatus` | `orders.revert_status` | `POST api/orders/{order}/revert-status`<br>`POST orders/{order}/revert-status` |
| `Modules\Orders\Controllers\OrderController@ship` | `orders.ship` | `POST api/orders/{order}/ship`<br>`POST orders/{order}/ship` |
| `Modules\Orders\Controllers\OrderController@show` | `orders.view` | `GET|HEAD api/orders/{order}`<br>`GET|HEAD orders/{order}` |
| `Modules\Orders\Controllers\OrderController@store` | `orders.create` | `POST api/orders`<br>`POST orders` |
| `Modules\Orders\Controllers\OrderController@update` | `orders.edit` | `PATCH api/orders/{order}`<br>`PUT|PATCH orders/{order}` |
| `Modules\Orders\Controllers\OrderReasonController@destroy` | `orderreason-delete` | `DELETE api/order-reasons/{type}/{id}` |
| `Modules\Orders\Controllers\OrderReasonController@index` | `orderreason-view` | `GET|HEAD order-reasons` |
| `Modules\Orders\Controllers\OrderReasonController@list` | `orderreason-view` | `GET|HEAD api/order-reasons/{type}` |
| `Modules\Orders\Controllers\OrderReasonController@store` | `orderreason-create` | `POST api/order-reasons/{type}` |
| `Modules\Orders\Controllers\OrderReasonController@toggleActive` | `orderreason-edit` | `PATCH api/order-reasons/{type}/{id}/toggle` |
| `Modules\Orders\Controllers\OrderReasonController@update` | `orderreason-edit` | `PUT api/order-reasons/{type}/{id}` |
| `Modules\Orders\Controllers\OrderReturnController@approve` | `returns.approve` | `POST api/returns/{return}/approve`<br>`POST returns/{return}/approve` |
| `Modules\Orders\Controllers\OrderReturnController@bulkStore` | `orders.bulk_return` | `POST api/orders/bulk-return`<br>`POST orders/bulk-return` |
| `Modules\Orders\Controllers\OrderReturnController@cancel` | `returns.cancel` | `POST api/returns/{return}/cancel`<br>`POST returns/{return}/cancel` |
| `Modules\Orders\Controllers\OrderReturnController@importBulkQc` | `returns.qc` | `POST api/returns/bulk-qc-import`<br>`POST returns/bulk-qc-import` |
| `Modules\Orders\Controllers\OrderReturnController@index` | `returns.view` | `GET|HEAD api/returns`<br>`GET|HEAD returns` |
| `Modules\Orders\Controllers\OrderReturnController@process` | `returns.process` | `POST api/returns/{return}/process` |
| `Modules\Orders\Controllers\OrderReturnController@processFinancials` | `returns.finance` | `POST api/returns/{return}/finance`<br>`POST returns/{return}/finance` |
| `Modules\Orders\Controllers\OrderReturnController@processQc` | `returns.qc` | `POST api/returns/{return}/qc`<br>`POST returns/{return}/qc` |
| `Modules\Orders\Controllers\OrderReturnController@show` | `returns.view` | `GET|HEAD api/returns/{return}`<br>`GET|HEAD returns/{return}` |
| `Modules\Orders\Controllers\OrderReturnController@store` | `returns.create` | `POST orders/{order}/returns` |
| `Modules\Orders\Controllers\OrderReturnController@storeApi` | `returns.create` | `POST api/returns` |
| `Modules\Orders\Controllers\PaymentController@bulkStatus` | `payments.edit` | `POST api/payments/bulk-status`<br>`POST payments/bulk-status` |
| `Modules\Orders\Controllers\PaymentController@destroy` | `payments.delete` | `DELETE api/payments/{payment}`<br>`DELETE payments/{payment}` |
| `Modules\Orders\Controllers\PaymentController@exportSelected` | `payments.export` | `POST api/payments/export-selected`<br>`POST payments/export` |
| `Modules\Orders\Controllers\PaymentController@index` | `payments.view` | `GET|HEAD api/payments`<br>`GET|HEAD payments` |
| `Modules\Orders\Controllers\PaymentController@show` | `payments.view` | `GET|HEAD api/payments/{payment}`<br>`GET|HEAD payments/{payment}` |
| `Modules\Orders\Controllers\PaymentController@store` | `payments.create` | `POST api/payments` |
| `Modules\Orders\Controllers\PaymentController@update` | `payments.edit` | `PATCH api/payments/{payment}`<br>`PUT payments/{payment}` |
| `Modules\Orders\Controllers\PaymentImportController@downloadSample` | `payments.import` | `GET|HEAD api/payments/import/sample`<br>`GET|HEAD payments/import/sample` |
| `Modules\Orders\Controllers\PaymentImportController@preview` | `payments.import` | `POST api/payments/import/preview`<br>`POST payments/import/preview` |
| `Modules\Orders\Controllers\PaymentImportController@process` | `payments.import` | `POST api/payments/import/process`<br>`POST payments/import/process` |
| `Modules\Orders\Controllers\PromotionsController@coupons` | `coupon-view` | `GET|HEAD promotions/coupons` |
| `Modules\Orders\Controllers\PromotionsController@couponsBulk` | `coupon-edit or coupon-delete` | `POST api/promotions/coupons/bulk-action` |
| `Modules\Orders\Controllers\PromotionsController@couponsDestroy` | `coupon-delete` | `DELETE api/promotions/coupons/{coupon}` |
| `Modules\Orders\Controllers\PromotionsController@couponsIndex` | `coupon-view` | `GET|HEAD api/promotions/coupons` |
| `Modules\Orders\Controllers\PromotionsController@couponsStore` | `coupon-create` | `POST api/promotions/coupons` |
| `Modules\Orders\Controllers\PromotionsController@couponsToggle` | `coupon-edit` | `PATCH api/promotions/coupons/{coupon}/toggle` |
| `Modules\Orders\Controllers\PromotionsController@couponsUpdate` | `coupon-edit` | `PATCH api/promotions/coupons/{coupon}` |
| `Modules\Orders\Controllers\PromotionsController@offers` | `promotions-view` | `GET|HEAD promotions/offers` |
| `Modules\Orders\Controllers\PromotionsController@offersBulk` | `promotions-edit or promotions-delete` | `POST api/promotions/offers/bulk-action` |
| `Modules\Orders\Controllers\PromotionsController@offersDestroy` | `promotions-delete` | `DELETE api/promotions/offers/{offer}` |
| `Modules\Orders\Controllers\PromotionsController@offersIndex` | `promotions-view` | `GET|HEAD api/promotions/offers` |
| `Modules\Orders\Controllers\PromotionsController@offersStore` | `promotions-create` | `POST api/promotions/offers` |
| `Modules\Orders\Controllers\PromotionsController@offersToggle` | `promotions-edit` | `PATCH api/promotions/offers/{offer}/toggle` |
| `Modules\Orders\Controllers\PromotionsController@offersUpdate` | `promotions-edit` | `PATCH api/promotions/offers/{offer}` |
| `Modules\Orders\Controllers\RefundController@bulkStatus` | `refunds.edit` | `POST api/refunds/bulk-status`<br>`POST refunds/bulk-status` |
| `Modules\Orders\Controllers\RefundController@index` | `refunds.view` | `GET|HEAD api/refunds`<br>`GET|HEAD refunds` |
| `Modules\Orders\Controllers\RefundController@show` | `refunds.view` | `GET|HEAD api/refunds/{refund}` |
| `Modules\Users\Controllers\AttendanceController@bulkAction` | Inline / self-service / public exception | `POST api/attendances/bulk-action` |
| `Modules\Users\Controllers\AttendanceController@destroy` | `attendance-delete` | `DELETE api/attendances/{attendance}` |
| `Modules\Users\Controllers\AttendanceController@exportDetailed` | `attendance-export` | `GET|HEAD api/attendances/export/detailed` |
| `Modules\Users\Controllers\AttendanceController@exportFiltered` | `attendance-export` | `GET|HEAD api/attendances/export/filtered` |
| `Modules\Users\Controllers\AttendanceController@exportSummary` | `attendance-export` | `GET|HEAD api/attendances/export/summary` |
| `Modules\Users\Controllers\AttendanceController@forceDelete` | `attendance-permanent-delete` | `DELETE api/attendances/{attendance}/force` |
| `Modules\Users\Controllers\AttendanceController@index` | `attendance-view` | `GET|HEAD api/attendances` |
| `Modules\Users\Controllers\AttendanceController@restore` | `attendance-restore` | `PATCH api/attendances/{attendance}/restore` |
| `Modules\Users\Controllers\AttendanceController@show` | `attendance-view` | `GET|HEAD api/attendances/{attendance}` |
| `Modules\Users\Controllers\AttendanceController@store` | `attendance-create` | `POST api/attendances` |
| `Modules\Users\Controllers\AttendanceController@update` | `attendance-edit` | `PUT|PATCH api/attendances/{attendance}` |
| `Modules\Users\Controllers\AttendanceCorrectionController@index` | Inline / self-service / public exception | `GET|HEAD api/attendance-corrections` |
| `Modules\Users\Controllers\AttendanceCorrectionController@review` | Inline / self-service / public exception | `PATCH api/attendance-corrections/{correction}/review` |
| `Modules\Users\Controllers\AttendanceCorrectionController@store` | Inline / self-service / public exception | `POST api/attendance-corrections` |
| `Modules\Users\Controllers\AuthController@login` | Inline / self-service / public exception | `POST api/auth/login`<br>`POST login` |
| `Modules\Users\Controllers\AuthController@logout` | Inline / self-service / public exception | `GET|POST|HEAD logout`<br>`POST api/auth/logout` |
| `Modules\Users\Controllers\AuthController@me` | Inline / self-service / public exception | `GET|HEAD api/auth/me` |
| `Modules\Users\Controllers\AuthController@revokeOtherTokens` | Inline / self-service / public exception | `POST api/auth/revoke-other-tokens` |
| `Modules\Users\Controllers\AuthController@twoFactorChallenge` | Inline / self-service / public exception | `POST api/auth/two-factor/challenge`<br>`POST login/two-factor` |
| `Modules\Users\Controllers\AuthController@twoFactorEmail` | Inline / self-service / public exception | `POST api/auth/two-factor/email`<br>`POST login/two-factor/email` |
| `Modules\Users\Controllers\AuthController@twoFactorEnroll` | Inline / self-service / public exception | `POST api/auth/two-factor/enroll`<br>`POST login/two-factor/enroll` |
| `Modules\Users\Controllers\AuthController@twoFactorForm` | Inline / self-service / public exception | `GET|HEAD login/two-factor` |
| `Modules\Users\Controllers\BulkUserController` | Inline / self-service / public exception | `POST api/users/bulk-action` |
| `Modules\Users\Controllers\DepartmentController@destroy` | `department-delete` | `DELETE api/departments/{department}` |
| `Modules\Users\Controllers\DepartmentController@index` | `department-view` | `GET|HEAD api/departments` |
| `Modules\Users\Controllers\DepartmentController@show` | `department-view` | `GET|HEAD api/departments/{department}` |
| `Modules\Users\Controllers\DepartmentController@store` | `department-create` | `POST api/departments` |
| `Modules\Users\Controllers\DepartmentController@update` | `department-edit` | `PUT api/departments/{department}` |
| `Modules\Users\Controllers\HolidayController@bulkAction` | `holiday-delete` | `POST api/holidays/bulk-action` |
| `Modules\Users\Controllers\HolidayController@destroy` | `holiday-delete` | `DELETE api/holidays/{holiday}` |
| `Modules\Users\Controllers\HolidayController@index` | `holiday-view` | `GET|HEAD api/holidays` |
| `Modules\Users\Controllers\HolidayController@show` | `holiday-view` | `GET|HEAD api/holidays/{holiday}` |
| `Modules\Users\Controllers\HolidayController@store` | `holiday-create` | `POST api/holidays` |
| `Modules\Users\Controllers\HolidayController@update` | `holiday-edit` | `PUT|PATCH api/holidays/{holiday}` |
| `Modules\Users\Controllers\HrSettingController@destroy` | `hrsetting-delete` | `DELETE api/hr-settings/{type}/{id}` |
| `Modules\Users\Controllers\HrSettingController@list` | `hrsetting-view` | `GET|HEAD api/hr-settings/{type}` |
| `Modules\Users\Controllers\HrSettingController@store` | `hrsetting-create` | `POST api/hr-settings/{type}` |
| `Modules\Users\Controllers\HrSettingController@toggleActive` | `hrsetting-edit` | `PATCH api/hr-settings/{type}/{id}/toggle` |
| `Modules\Users\Controllers\HrSettingController@update` | `hrsetting-edit` | `PUT api/hr-settings/{type}/{id}` |
| `Modules\Users\Controllers\LeaveBalanceController@bulkAction` | `leavebalance-delete` | `POST api/leave-balances/bulk-action` |
| `Modules\Users\Controllers\LeaveBalanceController@destroy` | `leavebalance-delete` | `DELETE api/leave-balances/{leave_balance}` |
| `Modules\Users\Controllers\LeaveBalanceController@index` | `leavebalance-view` | `GET|HEAD api/leave-balances` |
| `Modules\Users\Controllers\LeaveBalanceController@show` | `leavebalance-view` | `GET|HEAD api/leave-balances/{leave_balance}` |
| `Modules\Users\Controllers\LeaveBalanceController@store` | `leavebalance-create` | `POST api/leave-balances` |
| `Modules\Users\Controllers\LeaveBalanceController@update` | `leavebalance-edit` | `PUT|PATCH api/leave-balances/{leave_balance}` |
| `Modules\Users\Controllers\LeaveController@bulkAction` | Inline / self-service / public exception | `POST api/leaves/bulk-action` |
| `Modules\Users\Controllers\LeaveController@destroy` | `leave-delete` | `DELETE api/leaves/{leave}` |
| `Modules\Users\Controllers\LeaveController@export` | `leave-export` | `GET|HEAD api/leaves/export` |
| `Modules\Users\Controllers\LeaveController@index` | `leave-view` | `GET|HEAD api/leaves` |
| `Modules\Users\Controllers\LeaveController@show` | `leave-view` | `GET|HEAD api/leaves/{leave}` |
| `Modules\Users\Controllers\LeaveController@store` | `leave-create` | `POST api/leaves` |
| `Modules\Users\Controllers\LeaveController@update` | `leave-edit` | `PUT api/leaves/{leave}` |
| `Modules\Users\Controllers\LeaveController@updateStatus` | Inline / self-service / public exception | `PATCH api/leaves/{leave}/status` |
| `Modules\Users\Controllers\OrgChartController@index` | `department-view` | `GET|HEAD api/org-chart` |
| `Modules\Users\Controllers\PermissionController@destroy` | `permission-delete` | `DELETE api/permissions/{permission}` |
| `Modules\Users\Controllers\PermissionController@forceDelete` | `permission-permanent-delete` | `DELETE api/permissions/{permission}/force` |
| `Modules\Users\Controllers\PermissionController@index` | `permission-view` | `GET|HEAD api/permissions` |
| `Modules\Users\Controllers\PermissionController@options` | Inline / self-service / public exception | `GET|HEAD api/permissions/options` |
| `Modules\Users\Controllers\PermissionController@restore` | `permission-restore` | `PATCH api/permissions/{permission}/restore` |
| `Modules\Users\Controllers\PermissionController@show` | `permission-view` | `GET|HEAD api/permissions/{permission}` |
| `Modules\Users\Controllers\PermissionController@store` | `permission-create` | `POST api/permissions` |
| `Modules\Users\Controllers\PermissionController@update` | `permission-edit` | `PUT|PATCH api/permissions/{permission}` |
| `Modules\Users\Controllers\RoleController@destroy` | `role-delete` | `DELETE api/roles/{role}` |
| `Modules\Users\Controllers\RoleController@forceDelete` | `role-permanent-delete` | `DELETE api/roles/{role}/force` |
| `Modules\Users\Controllers\RoleController@index` | `role-view` | `GET|HEAD api/roles` |
| `Modules\Users\Controllers\RoleController@options` | Inline / self-service / public exception | `GET|HEAD api/roles/options` |
| `Modules\Users\Controllers\RoleController@restore` | `role-restore` | `PATCH api/roles/{role}/restore` |
| `Modules\Users\Controllers\RoleController@show` | `role-view` | `GET|HEAD api/roles/{role}` |
| `Modules\Users\Controllers\RoleController@store` | `role-create` | `POST api/roles` |
| `Modules\Users\Controllers\RoleController@update` | `role-edit` | `PUT|PATCH api/roles/{role}` |
| `Modules\Users\Controllers\RosterController@claim` | Inline / self-service / public exception | `POST api/rosters/co` |
| `Modules\Users\Controllers\RosterController@claims` | Inline / self-service / public exception | `GET|HEAD api/rosters/co` |
| `Modules\Users\Controllers\RosterController@import` | Inline / self-service / public exception | `POST api/rosters/import` |
| `Modules\Users\Controllers\RosterController@index` | Inline / self-service / public exception | `GET|HEAD api/rosters` |
| `Modules\Users\Controllers\RosterController@review` | Inline / self-service / public exception | `PATCH api/rosters/co/{id}/review` |
| `Modules\Users\Controllers\SecurityController@activity` | Inline / self-service / public exception | `GET|HEAD api/security/activity` |
| `Modules\Users\Controllers\SecurityController@cloneRole` | Inline / self-service / public exception | `POST api/roles/{role}/clone` |
| `Modules\Users\Controllers\SecurityController@emergencyLockdown` | Inline / self-service / public exception | `POST api/security/emergency-lockdown` |
| `Modules\Users\Controllers\SecurityController@impersonate` | Inline / self-service / public exception | `POST api/security/impersonate/{user}` |
| `Modules\Users\Controllers\SecurityController@sessions` | Inline / self-service / public exception | `GET|HEAD api/security/sessions` |
| `Modules\Users\Controllers\SecurityController@stopImpersonating` | Inline / self-service / public exception | `POST api/security/stop-impersonate` |
| `Modules\Users\Controllers\SecurityController@terminateOtherSessions` | Inline / self-service / public exception | `POST api/security/sessions/terminate-other` |
| `Modules\Users\Controllers\SecurityController@terminateSession` | Inline / self-service / public exception | `DELETE api/security/sessions/{id}` |
| `Modules\Users\Controllers\SecurityMailController@show` | `security-mail-view` | `GET|HEAD api/security/mail` |
| `Modules\Users\Controllers\SecurityMailController@test` | `security-mail-test` | `POST api/security/mail/test` |
| `Modules\Users\Controllers\SecurityMailController@update` | `security-mail-edit` | `POST api/security/mail` |
| `Modules\Users\Controllers\TeamController@destroy` | `team-delete` | `DELETE api/teams/{team}` |
| `Modules\Users\Controllers\TeamController@index` | `team-view` | `GET|HEAD api/teams` |
| `Modules\Users\Controllers\TeamController@show` | `team-view` | `GET|HEAD api/teams/{team}` |
| `Modules\Users\Controllers\TeamController@store` | `team-create` | `POST api/teams` |
| `Modules\Users\Controllers\TeamController@update` | `team-edit` | `PUT|PATCH api/teams/{team}` |
| `Modules\Users\Controllers\TwoFactorController@email` | Inline / self-service / public exception | `POST api/security/two-factor/email` |
| `Modules\Users\Controllers\TwoFactorController@setup` | Inline / self-service / public exception | `POST api/security/two-factor/setup` |
| `Modules\Users\Controllers\TwoFactorController@status` | Inline / self-service / public exception | `GET|HEAD api/security/two-factor` |
| `Modules\Users\Controllers\TwoFactorController@update` | Inline / self-service / public exception | `POST api/security/two-factor` |
| `Modules\Users\Controllers\UserController@destroy` | `user-delete` | `DELETE api/users/{user}` |
| `Modules\Users\Controllers\UserController@forceDelete` | Inline / self-service / public exception | `DELETE api/users/{user}/force` |
| `Modules\Users\Controllers\UserController@index` | `user-view` | `GET|HEAD api/users` |
| `Modules\Users\Controllers\UserController@loginHistory` | `user-view` | `GET|HEAD api/users/{user}/login-history` |
| `Modules\Users\Controllers\UserController@options` | `user-view` | `GET|HEAD api/users/options` |
| `Modules\Users\Controllers\UserController@restore` | `user-restore` | `PATCH api/users/{user}/restore` |
| `Modules\Users\Controllers\UserController@show` | Inline / self-service / public exception | `GET|HEAD api/users/{user}` |
| `Modules\Users\Controllers\UserController@store` | `user-create` | `POST api/users` |
| `Modules\Users\Controllers\UserController@syncPermissions` | `user-edit` | `POST api/users/{user}/sync-permissions` |
| `Modules\Users\Controllers\UserController@syncRoles` | `user-edit` | `POST api/users/{user}/sync-roles` |
| `Modules\Users\Controllers\UserController@toggleActive` | `user-edit` | `PATCH api/users/{user}/toggle-active` |
| `Modules\Users\Controllers\UserController@unsuspend` | Inline / self-service / public exception | `PATCH api/users/{user}/unsuspend` |
| `Modules\Users\Controllers\UserController@update` | `user-edit` | `PUT|PATCH api/users/{user}` |
| `Modules\Users\Controllers\UserController@updatePhoto` | Inline / self-service / public exception | `POST api/users/{user}/update-photo` |
