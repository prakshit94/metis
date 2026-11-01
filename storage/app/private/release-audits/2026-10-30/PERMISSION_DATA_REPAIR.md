# Permission data audit and repair — 30 October 2026

## Findings

The active database already had all 425 permission names in the current application catalog. No missing definitions, soft-deleted catalog entries, duplicate name/guard groups or orphan role-permission links were found. Route/template permission contracts covered 591 gates across the 425 definitions.

The issue was incomplete role grants for newer actions: 91 mapped actions had gaps against the existing `FeaturePermissions::legacy()` policy. There were 93 source/action gap groups; some overlapped. The repair added 295 distinct role-permission links and removed none. The final audit found no remaining compatibility gaps.

## Product-specific result

Four roles (Verification, FC Head, Team Leader and Agri Doctor) had `product-view` but lacked the corresponding `product-export` grant defined by the existing compatibility policy. Those four grants were restored. Product create, edit, delete, restore, permanent-delete and import grants were unchanged. Manager remained a product read/export role; Director and Super Admin retained their existing product permissions.

All product permission definitions already existed, and product API checks match the page controls. Product options load through the existing product endpoint; no additional brand/category/warehouse write privileges were added. If a role should newly create/edit/import products, that is a role-policy decision to make explicitly in the role editor, rather than treating every absent permission as a defect.

## Scope and preservation

Used the application's existing PermissionCatalogSync service. No controllers, product calculations, stock rules, prices, storefront behavior, business data, roles or user-role memberships were changed. Direct-user permission assignments were unchanged in this database. The helper preserves individual/global team scopes when direct legacy grants exist. Existing custom grants and permission definitions were preserved.

Added `Database\Seeders\LegacyPermissionGrantsSeeder` as an explicit, repeatable post-import repair. It is deliberately excluded from routine DatabaseSeeder runs. Ordinary `FeaturePermissionsSeeder` remains catalog-only, preserving intentional fine-grained revocations. The explicit repair copies documented compatibility mappings and can restore a revoked mapped action when its legacy source permission remains assigned; use it only when that compatibility repair is intended.

Private snapshots and the exact added-row list are saved under:

`storage/app/private/permission-repairs/20261030-114051-cd138b/`

Files: `before.json`, `after.json`, `changes.json`, with restrictive filesystem permissions. Permission cache was reset after applying the repair. Refresh already-open pages before testing button visibility.

## Validation

- 16 isolated permission-catalog checks passed, including product export restoration, preservation of null/individual team scopes, unchanged product write privileges, custom grants, roles/users, and repeated-run idempotence.
- Route contracts: 622 handlers passed.
- Permission contracts: 425 permissions and 591 gates passed.
- Before/after verification confirmed every one of the 295 added rows has an existing corresponding legacy role grant; only four product-export rows changed in the product permission family.
- PHP syntax and diff checks passed.

No external API calls or production product operations were performed.

## Audited compatibility gap groups

| Action | Existing source permission | Missing links before repair |
| --- | --- | ---: |
| `office-view` | `team-view` | 3 |
| `office-create` | `team-create` | 3 |
| `office-edit` | `team-edit` | 3 |
| `office-delete` | `team-delete` | 1 |
| `product-export` | `product-view` | 4 |
| `orders.view.unfulfillable` | `orders.view.pending` | 6 |
| `orders.view.delivery_attempted` | `orders.view.dispatched` | 6 |
| `orders.shipping_label` | `orders.ship` | 4 |
| `orders.shipping_label` | `orders.bulk_print` | 5 |
| `reports-export` | `reports-view` | 4 |
| `target-import` | `target-create` | 4 |
| `files-view` | `settings-view` | 1 |
| `files-create` | `settings-view` | 1 |
| `files-edit` | `settings-view` | 1 |
| `files-delete` | `settings-view` | 2 |
| `files-download` | `settings-view` | 3 |
| `calltag-view` | `settings-view` | 3 |
| `calltag-create` | `settings-view` | 3 |
| `calltag-edit` | `settings-view` | 3 |
| `calltag-delete` | `settings-view` | 3 |
| `calllog-create` | `customer-view` | 7 |
| `calllog-create` | `orders.create` | 7 |
| `hrsetting-view` | `department-view` | 2 |
| `hrsetting-create` | `department-create` | 2 |
| `hrsetting-edit` | `department-edit` | 2 |
| `hrsetting-delete` | `department-delete` | 1 |
| `holiday-view` | `leave-view` | 2 |
| `holiday-create` | `leave-create` | 2 |
| `holiday-edit` | `leave-edit` | 2 |
| `holiday-delete` | `leave-delete` | 1 |
| `leavebalance-view` | `leave-view` | 2 |
| `leavebalance-create` | `leave-create` | 2 |
| `leavebalance-edit` | `leave-edit` | 2 |
| `leavebalance-delete` | `leave-delete` | 1 |
| `purchaseorder-invoice-upload` | `purchaseorder-create` | 2 |
| `purchaseorder-payment-create` | `purchaseorder-create` | 2 |
| `inventoryadjustment-approve` | `inventoryadjustment-edit` | 2 |
| `stocktransfer-send` | `stocktransfer-edit` | 2 |
| `stocktransfer-receive` | `stocktransfer-edit` | 2 |
| `stocktransfer-cancel` | `stocktransfer-edit` | 2 |
| `invoices.view` | `orders.view` | 3 |
| `invoices.edit` | `orders.receipt` | 1 |
| `invoices.export` | `orders.view` | 7 |
| `invoices.pdf` | `orders.view` | 7 |
| `invoices.record-payment` | `orders.receipt` | 4 |
| `payments.view` | `orders.view` | 3 |
| `payments.create` | `orders.receipt` | 1 |
| `payments.edit` | `orders.receipt` | 1 |
| `payments.delete` | `orders.receipt` | 3 |
| `payments.import` | `orders.receipt` | 4 |
| `payments.export` | `orders.view` | 7 |
| `refunds.view` | `orders.view` | 3 |
| `refunds.edit` | `orders.edit` | 4 |
| `credit-notes.view` | `orders.view` | 3 |
| `credit-notes.create` | `orders.receipt` | 1 |
| `credit-notes.edit` | `orders.receipt` | 1 |
| `credit-notes.delete` | `orders.receipt` | 3 |
| `returns.view` | `orders.view` | 3 |
| `returns.create` | `orders.return` | 1 |
| `returns.process` | `orders.return` | 4 |
| `returns.qc` | `orders.return` | 4 |
| `returns.finance` | `orders.return` | 4 |
| `returns.approve` | `orders.return` | 4 |
| `returns.cancel` | `orders.return` | 4 |
| `user-security-requirement` | `settings-edit` | 2 |
| `security-mail-view` | `settings-edit` | 2 |
| `security-mail-edit` | `settings-edit` | 2 |
| `security-mail-test` | `settings-edit` | 2 |
| `orders.bulk_status.pending` | `orders.bulk_status` | 5 |
| `orders.bulk_status.confirmed` | `orders.bulk_status` | 5 |
| `orders.bulk_status.processing` | `orders.bulk_status` | 5 |
| `orders.bulk_status.ready_to_ship` | `orders.bulk_status` | 5 |
| `orders.bulk_status.dispatched` | `orders.bulk_status` | 5 |
| `orders.bulk_status.delivered` | `orders.bulk_status` | 5 |
| `orders.bulk_status.cancelled` | `orders.bulk_status` | 5 |
| `orders.bulk_status.returned` | `orders.bulk_status` | 5 |
| `reports.export.sales_overview` | `reports-view` | 4 |
| `reports.export.india_post` | `reports-view` | 4 |
| `reports.export.product_sales` | `reports-view` | 4 |
| `reports.export.sales_region` | `reports-view` | 4 |
| `reports.export.payment_reconciliation` | `reports-view` | 4 |
| `reports.export.stock_valuation` | `reports-view` | 4 |
| `reports.export.stock_ledger` | `reports-view` | 4 |
| `reports.export.low_stock` | `reports-view` | 4 |
| `reports.export.po_fulfillment` | `reports-view` | 4 |
| `reports.export.grn_discrepancy` | `reports-view` | 4 |
| `reports.export.call_performance` | `reports-view` | 4 |
| `reports.export.call_tagging` | `reports-view` | 4 |
| `reports.export.customer_retention` | `reports-view` | 4 |
| `reports.export.return_analysis` | `reports-view` | 4 |
| `reports.export.audit_trail` | `reports-view` | 4 |
| `reports.export.suppliers_report` | `reports-view` | 4 |
| `reports.export.attendance_report` | `reports-view` | 4 |
