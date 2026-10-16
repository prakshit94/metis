# K6 performance suite

This suite covers the authenticated web page surface, a staged page-load profile, and an all-read API profile. It is read-only after login: it does not create orders, customers, payments, returns, or inventory changes.

## Requirements

- A running, production-like Metis instance and its database.
- K6 installed on the runner.
- A dedicated load-test user with access to the pages under test. The app invalidates all existing sessions and API tokens when a user logs in, so **do not use a normal employee/admin account**.
- Optionally, a separate dedicated Sanctum token in `K6_API_TOKEN` for the API profile.

The route sweep expects HTTP 200 on each configured page. Use an account with permissions for all listed pages (including audit logs, catalog, HR, inventory, orders, and reports); otherwise permission failures will be reported as failures. Routes with path parameters, downloads, logout, and mutating endpoints are intentionally excluded from the sweep.

## Run

```sh
export K6_BASE_URL=http://127.0.0.1:8000
export K6_EMAIL='load-test-user@example.invalid'
export K6_PASSWORD='provided-outside-the-repository'
k6 run tests/performance/k6/site.js
```

Default `normal` profile peaks at 10 page VUs and 5 API VUs. For a bounded stress ramp:

```sh
K6_PROFILE=stress K6_PEAK_PAGE_VUS=40 K6_PEAK_API_VUS=20 \
  k6 run --summary-export=tests/performance/summary.json tests/performance/k6/site.js
```

On this workspace host (4 CPU cores), the stress defaults cap at 40 page VUs and 20 API VUs. They are a staged capacity probe, not an unlimited test. Review server, database, and queue metrics during the run; stop if error rates, resource use, or latency become unsafe. Do not run the stress profile against production.

`apis.js` sweeps every registered GET API route except the external live-tracking lookup, then runs a load profile across that route set. It can authenticate with `K6_API_TOKEN`, or use the same dedicated test account and first-party session cookie as the page suite. API GETs only are sent; POST, PUT, PATCH, and DELETE routes are not load-tested because they can modify business data. GET exports/downloads are included in the route sweep. The file preview endpoint is probed without a file path and may return a client error.

The API sweep substitutes `1` for resource IDs unless overridden with `K6_API_DEFAULT_ID` or a route-specific variable such as `K6_API_ORDER_ID`. Conversation routes can use `K6_API_CONVERSATION_ID`. Type-based lookups use one valid type per endpoint. Missing fixture IDs can produce expected 404 responses while still checking the route's handling and latency.

The stress API profile is capped at 20 concurrent virtual users by default. It ramps through 2, 5, 10, and 20 VUs, then ramps down. Set `K6_PEAK_API_VUS` to choose a different cap.

```sh
K6_PROFILE=stress K6_PEAK_API_VUS=20 \
  k6 run --summary-export=tests/performance/apis-summary.json tests/performance/k6/apis.js
```

To load-test the customer-prefilled order creation page without submitting the form:

```sh
K6_CUSTOMER_ID=81349 K6_PEAK_VUS=68 \
  k6 run --summary-export=tests/performance/order-create-summary.json \
  tests/performance/k6/order-create-customer.js
```

To include the read-only Order History path, set both `K6_CUSTOMER_ID` and `K6_ORDER_ID` to existing fixture records owned by the test environment. This visits the customer details endpoint, the order creation page preselected for that customer, and the order details endpoint. It does not submit the order form.

## Coverage and limits

- `site.js` performs one complete sweep of the configured 63 static authenticated pages, then exercises a representative page mix under load. The Order History path is conditional on the fixture IDs above.
- `apis.js` covers all 118 registered GET API routes in this checkout except the live-tracking lookup, which can contact an external shipping provider. The inventory includes the API documentation endpoints and the authenticated product search helper registered under web middleware.
- API export/download routes are called once by the route sweep, then excluded from repeated stress traffic. Missing fixture records can cause expected 404 responses. Login storms and all write workflows are excluded; login happens once during K6 setup.
- Complete write-flow tests need a disposable database and purpose-built fixture users/products/orders. They must be run separately from this read-only load profile so inventory, financial, and order lifecycle state cannot be polluted.

The thresholds are initial local-project targets (`p95 < 1.5s`, `p99 < 3s` for normal; `p95 < 2.5s`, `p99 < 5s` for stress). Treat them as provisional until measured against the intended deployment and agreed service-level objectives.

## Disposable local HTTP verification (17 October 2026)

Run the query regression fixtures and then both HTTP profiles against those
synthetic records:

```sh
METIS_K6_ISOLATED=1 php tests/regression/query_performance.php
```

The runner copies table definitions into a uniquely named temporary MySQL database,
adds synthetic users and permissions, starts a loopback server on port 18080,
runs k6, terminates the server process group, and drops the fixture database. It
requires database creation privileges, PHP `posix`, `setsid`, and a free port
18080. No production records or carrier credentials are copied. Do not run two
instances simultaneously on that port. The underlying schema is cloned from the
configured database; use `schema_repairs.php` separately to verify fresh migrations.

Select a single suite with `METIS_K6_PROFILES=site` or `apis`. Use
`K6_SWEEP_ONLY=true METIS_K6_PROFILES=apis` for an API sweep without the load stage.
Use `K6_PROFILE=stress` for the existing bounded ramps. Normal and stress outputs
are saved separately in dated `results/*-isolated` and `results/*-isolated-stress`
folders. A nonzero k6 exit fails the runner.

The fixture deliberately uses separate browser and token users because login
revokes a user's existing tokens. When credentials and a token are both supplied,
`apis.js` also logs in the browser user so web-middleware helpers and documentation
can be exercised. Session-required endpoints still require working browser auth.

Both suites reject redirects back to login. Page requests disable redirect
following; API checks reject authentication failures, redirects, status zero,
and server errors. Missing resource 404/422 responses remain allowed but are
recorded per route; they establish handling of absent fixtures, not success of
the full feature.

The runner writes one-second PHP process-group CPU tick and summed RSS samples.
Summed RSS includes shared pages multiple times. Database and browser CPU are
not included. Generate a per-route status/latency report with:

```sh
python3 tests/performance/tools/summarize.py tests/performance/results/YYYY-MM-DD-isolated
php tests/performance/tools/audit-routes.php
```

`site.js` requests HTML only; it does not execute frontend JavaScript or browser
polling. The HTTP suites remain read-focused. The separate schema/warehouse/query
regressions validate selected write-service invariants, but are not comprehensive
HTTP write-load tests.

The targets create/import paths intentionally return HTTP 302 to `/targets`;
the page suite checks those exact redirects. Documentation HTML/schema endpoints
are swept once and excluded from repeated API traffic, just like exports.
Schema generation can consume seconds of CPU per call and is a separate workload
from the ordinary application read mix.

For a fresh follow-up sweep of both suites without repeated load stages, use:

```sh
METIS_K6_ISOLATED=1 METIS_K6_RUN_LABEL=followup-final K6_SWEEP_ONLY=true \
  php tests/regression/query_performance.php
```

`METIS_K6_RUN_LABEL` accepts lowercase letters, digits and hyphens and keeps
outputs separate from earlier runs. The expanded follow-up fixtures are larger
than the earlier stress fixture; their latency measurements are not direct
before/after comparisons.

API documentation is now included in Laravel's deployment optimization lifecycle:
`php artisan optimize` warms its supported Scramble cache, and
`php artisan optimize:clear` invalidates it. Regenerate it on deployment after
source changes. The documentation routes retain their existing authorization.
