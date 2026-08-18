# Metis Enterprise API — Postman Collection Guide

**File:** `Metis-API-Collection.json`  
**Total Requests:** 201 requests across 30 folders  
**Base URL:** `http://127.0.0.1:8000`  
**Auth:** Laravel Sanctum (Bearer Token)

---

## 🚀 Quick Setup (2 Minutes)

### Step 1 — Import Collection
1. Open **Postman**
2. Click **Import** (top-left)
3. Select file: `postman/Metis-API-Collection.json`
4. Click **Import**

### Step 2 — Create Environment
1. Click **Environments** → **New Environment** → Name it `Metis Local`
2. Add these variables:

| Variable | Initial Value | Notes |
|---|---|---|
| `base_url` | `http://127.0.0.1:8000` | Your local server URL |
| `token` | *(empty)* | Filled after login |
| `order_id` | `1` | Update after creating an order |
| `product_id` | `1` | Update after creating a product |
| `customer_id` | `1` | Update after creating a customer |
| `invoice_id` | `1` | Update as needed |
| `payment_id` | `1` | Update as needed |
| `refund_id` | `1` | Update as needed |
| `return_id` | `1` | Update as needed |
| `shipment_id` | `1` | Update as needed |
| `warehouse_id` | `1` | Update to your warehouse ID |
| `transfer_id` | `1` | Update as needed |
| `adjustment_id` | `1` | Update as needed |
| `user_id` | `1` | Update as needed |
| `role_id` | `1` | Update as needed |
| `village_id` | `1` | Update as needed |
| `conversation_id` | `1` | Update as needed |
| `coupon_id` | `1` | Update as needed |
| `offer_id` | `1` | Update as needed |
| `category_id` | `1` | Update as needed |
| `brand_id` | `1` | Update as needed |
| `service_id` | `1` | Update as needed |

3. Select `Metis Local` as your active environment

### Step 3 — Login & Get Token
1. Open folder **🔐 Auth** → **Login**
2. The body already has `admin@example.com` / `password` — update if needed
3. Click **Send**
4. From the response, copy the value of `token`
5. Paste it into your environment's `token` variable
6. **All other requests will now authenticate automatically**

---

## 📂 Folder Structure & Usage Guide

### 🔐 Auth
| Request | Method | Description |
|---|---|---|
| Login | POST | Get Bearer token. **Run this first.** |
| Logout | POST | Invalidate current token |
| Revoke Other Tokens | POST | Invalidate all other sessions |

---

### 📊 Dashboard & Reports
| Request | Method | Description |
|---|---|---|
| Get Dashboard Metrics | GET | Revenue, orders, customer totals (role-filtered) |
| Get Reports | GET | Aggregated business reports |

---

### 📦 Orders
**Flow:** `Create → Confirm → Process → Ready to Ship → Dispatch → Deliver`

| Request | Method | Description |
|---|---|---|
| List Orders | GET | Filterable by status, search, date |
| Create Order | POST | Creates a new sale order with line items |
| Get Order | GET | Full order details with items, shipments |
| Update Order | PATCH | Edit order fields |
| Update Order Status | POST | Move order through the status lifecycle |
| Delete Order | DELETE | Soft-delete an order |

**Status values for Update Order Status:**
`pending` → `confirmed` → `processing` → `ready_to_ship` → `dispatched` → `delivered`
Also: `cancelled`, `returned`

---

### 🧾 Invoices
| Request | Method | Description |
|---|---|---|
| List Invoices | GET | Filter by status (unpaid/paid/cancelled) |
| Get Invoice | GET | Invoice with line items and payments |
| Download Invoice PDF | GET | Returns PDF file |
| Bulk Update Status | POST | Mark multiple invoices as paid/unpaid |

---

### 💳 Payments
| Request | Method | Description |
|---|---|---|
| List Payments | GET | All payments with stats |
| Get Payment | GET | Single payment detail |
| Create Payment | POST | Record a payment against an invoice |
| Bulk Update Status | POST | Batch update to completed/failed |

**Status values:** `pending`, `authorized`, `completed`, `failed`, `refunded`

---

### 💸 Refunds
| Request | Method | Description |
|---|---|---|
| List Refunds | GET | All refunds with stats |
| Get Refund | GET | Single refund detail |
| Bulk Update Status | POST | Update to `processed` or `failed` |

---

### ↩️ Returns
| Request | Method | Description |
|---|---|---|
| List Returns | GET | All returns filterable by status |
| Get Return | GET | Return detail with items |
| Create Return | POST | Initiate a return for an order |
| Process Return | POST | Approve/reject with refund details |

**Process actions:** `approve`, `reject`, `partial_approve`

---

### 🏭 Inventory — Stocks
| Request | Method | Description |
|---|---|---|
| List Stocks | GET | Filter by `warehouse_id`, `stock_level` |
| Get Stock for Product | GET | Stock for a specific product+warehouse |
| Set Stock Level | POST | Manually set stock quantity |
| Warehouse Options | GET | Dropdown list of warehouses |

**stock_level filter values:** `in_stock`, `low_stock`, `out_of_stock`

---

### 🔄 Inventory — Transfers
**Flow:** `Create (draft) → Send → Receive` or `Cancel`

| Request | Method | Description |
|---|---|---|
| List Transfers | GET | Filter by status |
| Create Transfer | POST | Create a draft stock transfer |
| Send Transfer | POST | Mark as dispatched from source |
| Receive Transfer | POST | Confirm arrival at destination |
| Cancel Transfer | POST | Cancel a sent transfer |
| Bulk Action | POST | Batch send or receive |

---

### 📋 Inventory — Adjustments
**Flow:** `Create (pending) → Approve` or `Reject`

| Request | Method | Description |
|---|---|---|
| List Adjustments | GET | Filter by status |
| Create Adjustment | POST | Audit-based stock count correction |
| Approve Adjustment | POST | Apply the stock change |
| Reject Adjustment | POST | Discard the adjustment |
| Bulk Action | POST | Batch approve/reject |

---

### 🚢 Shipping — Services
| Request | Method | Description |
|---|---|---|
| List Services | GET | All carriers/couriers |
| Create Service | POST | Add a new carrier |
| Update Service | PATCH | Edit carrier details |
| Toggle Service | POST | Enable/disable a carrier |
| Delete Service | DELETE | Remove a carrier |
| Bulk Action | POST | activate/deactivate multiple |

---

### 🚚 Shipping — Shipments
**Flow:** `pending → shipped (in_transit) → delivered` or `returned`

| Request | Method | Description |
|---|---|---|
| List Shipments | GET | Filter by status, warehouse |
| Update Shipment Status | POST | Move through delivery lifecycle |
| Get Tracking Events | GET | All tracking events for a shipment |
| Add Tracking Event | POST | Custom tracking milestone |
| Bulk Action | POST | mark_in_transit / mark_delivered / mark_returned |

**Shipment status values:** `pending`, `shipped`, `in_transit`, `delivered`, `failed`, `returned`

---

### 🛍️ Products
| Request | Method | Description |
|---|---|---|
| List Products | GET | Filter by status, category, search |
| Create Product | POST | Full product with pricing and stock config |
| Update Product | PATCH | Edit any product field |
| Duplicate Product | POST | Clone an existing product |
| Bulk Status Update | POST | active / inactive / archived |
| Export Products | GET | CSV/Excel export |
| Import Products | POST | Bulk CSV import |

---

### 👥 Customers
| Request | Method | Description |
|---|---|---|
| List Customers | GET | Filter by type (customer/supplier) |
| Create Customer | POST | New customer/B2B party with GST |
| Add Address | POST | Shipping or billing address |
| Bulk Action | POST | activate / deactivate / delete |

---

### 🎯 Promotions — Coupons
| Request | Method | Description |
|---|---|---|
| Create Coupon | POST | Percentage or flat discount code |
| Toggle Coupon | PATCH | Enable/disable coupon |
| Bulk Action | POST | Batch activate/deactivate |

**Coupon types:** `percentage`, `flat`

---

### 🎁 Promotions — Offers
| Request | Method | Description |
|---|---|---|
| Create Offer | POST | BOGO or bundle rules |
| Toggle Offer | PATCH | Enable/disable offer |
| Bulk Action | POST | Batch activate/deactivate |

---

### 👤 Users
| Request | Method | Description |
|---|---|---|
| Create User | POST | New user with role assignment |
| Sync Roles | POST | Assign/change roles |
| Sync Permissions | POST | Override specific permissions |
| Login History | GET | See user's session activity |
| Bulk Action | POST | activate / deactivate / delete |

---

### 🔑 Roles & Permissions
| Request | Method | Description |
|---|---|---|
| List Roles / Options | GET | All roles or dropdown list |
| Create Role | POST | New role with permissions |
| List Permissions / Options | GET | All permissions |

---

### 🏘️ Villages
| Request | Method | Description |
|---|---|---|
| Search Villages | GET | Autocomplete by name |
| Import | POST | Bulk CSV import |
| Import Template | GET | Download CSV template |
| Bulk Action | POST | delete / export |

---

### 📝 Order Reasons
Three reason types: `return`, `reschedule`, `failure`

| Request | Method | Description |
|---|---|---|
| List Reasons | GET | `GET /api/order-reasons/{type}` |
| Create Reason | POST | Add a new reason option |
| Update Reason | PUT | Edit reason text |
| Toggle Reason | PATCH | Enable/disable a reason |
| Delete Reason | DELETE | Remove a reason |

---

### 💬 Chat
| Request | Method | Description |
|---|---|---|
| Start Direct Chat | POST | Open 1-on-1 conversation |
| Send Message | POST | Text message with optional attachment |
| Mark As Read | POST | Mark conversation as read |
| Edit Message | POST | Edit sent message |
| Delete Message | POST | Soft-delete a message |
| Forward Message | POST | Forward to other conversations |
| Create Group | POST | New group with members |
| Add/Remove Members | POST | Manage group membership |
| Transfer Ownership | POST | Change group owner |

---

### 🔔 Activities & Notifications
| Request | Method | Description |
|---|---|---|
| Recent Activities | GET | Last N activity log entries |
| Mark Activity Read | POST | Dismiss a notification |

---

## 🔧 Testing Tips

### Test the Complete Order Lifecycle
Run these in order:
1. `Orders → Create Order` → copy `id` to `{{order_id}}`
2. `Orders → Update Order Status` with `{"status":"confirmed"}`
3. `Orders → Update Order Status` with `{"status":"processing"}`
4. `Shipments → Update Shipment Status` with `{"status":"shipped"}`
5. `Shipments → Update Shipment Status` with `{"status":"delivered"}`
6. Check `Dashboard → Get Dashboard Metrics` to see updated stats

### Test the Return + Refund Flow
1. Complete an order (steps above)
2. `Returns → Create Return` → copy `id` to `{{return_id}}`
3. `Returns → Process Return` with `{"action":"approve","refund_amount":150}`
4. `Refunds → List Refunds` — your refund appears
5. `Refunds → Bulk Update Status` with `{"status":"processed"}`

### Test Inventory Flow
1. `Stocks → Set Stock Level` for a product
2. `Transfers → Create Transfer` between warehouses
3. `Transfers → Send Transfer` (deducts from source)
4. `Transfers → Receive Transfer` (adds to destination)
5. `Stocks → List Stocks` to verify quantities

---

## ⚠️ Common Errors

| Status | Meaning | Fix |
|---|---|---|
| `401 Unauthorized` | Token missing or expired | Re-run Login, update `{{token}}` |
| `403 Forbidden` | Role lacks permission | Use Super Admin account |
| `404 Not Found` | Wrong ID in variable | Update `{{order_id}}` etc. to valid IDs |
| `422 Unprocessable` | Validation failed | Check request body against error response |
| `400 Bad Request` | Business rule violation | e.g. wrong status transition |

---

## 📞 API Base URLs

| Environment | Base URL |
|---|---|
| Local | `http://127.0.0.1:8000` |
| Production | `https://your-domain.com` |

**API Docs (Swagger UI):** `http://127.0.0.1:8000/docs/api`  
**API Schema (JSON):** `http://127.0.0.1:8000/docs/api.json`
