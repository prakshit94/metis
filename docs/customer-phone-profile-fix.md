# Customer phone profile loading

Searching an existing primary or alternate mobile number finds the customer globally, but the order creation workspace previously reapplied directory ownership and LOB/state filters while loading that customer. This could open the correct customer_id URL with an empty workspace (reproduced on the live admin with customer 76481).

The workspace now loads an existing, non-deleted customer independently of directory ownership/state. Customer profile history and its count now include that customer’s orders across owners, states and statuses, including after profile refresh. The existing latest-ten history limit and exclusion of deleted orders remain. Directory queries, order detail/action permissions, checkout validations and mutation endpoints retain their existing rules. Inactive or blacklisted status does not hide the profile; their transaction restrictions remain in place. Search input accepts pasted country prefixes and formatting using the same last-ten-digits normalization as the backend.

Deploy these files through the normal release process:

- app/Modules/Customers/Controllers/CustomerController.php
- app/Modules/Orders/Controllers/OrderController.php
- resources/views/dashboard.blade.php

Refresh compiled views and PHP OPcache through the normal deployment procedure. No migration or frontend asset build is needed. This local patch has not been deployed.

Validation:

```sh
node tests/regression/customer_phone_search.mjs
METIS_TEST_MYSQL_SOCKET=/path/to/isolated/mysql.sock php tests/regression/customer_phone_profile.php
php tests/regression/route_contracts.php
php tests/regression/permission_contracts.php
```

The database regression creates and drops its own disposable schema. Verify the affected number after deployment using an agent account with order creation permission, including a customer belonging to another owner/state. This patch does not grant new role/action permissions.

Follow-up reproduced on the live admin: phone 9928205109 opens customer 46650 with an empty Order History. Its profile history was being filtered through RecordVisibility::orders, which applies order-directory ownership, LOB and status permissions. The fix removes those directory constraints only from customer-profile history and counts.
