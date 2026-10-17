# Orders page audit — 17 October 2026

## Scope and verification limits

Reviewed the orders list Blade template, shared detail modal, shared clipboard helper, Alpine orders component, list-controller filtering/sorting/pagination, permission scope, and frontend asset loading. Ran the existing orders UI regressions and added focused clipboard regressions. Authenticated read-only browser testing was completed on both local and production orders pages using the user-authorized account. This is a source audit plus the specific browser checks recorded below, not an exhaustive acceptance test across browsers or roles. No production code, database records, order states, permissions, or inventory were changed.

Requested list: pending, unfulfillable, pending_confirmation, confirmed, processing, ready_to_ship; rolling 30 days; 25 rows; ID descending.

## Copy failure: evidence and diagnosis

Production URL provided: http://15.206.138.177/login. Browser navigation remained on that HTTP URL. Local development uses http://127.0.0.1:8000.

Browsers treat loopback HTTP origins as potentially trustworthy, while ordinary remote HTTP origins do not qualify as secure contexts. The modern navigator.clipboard API requires a secure context. Thus local copying may use the modern API while this production deployment must rely on the legacy fallback. Inspection of the deployed inline script confirmed production serves the earlier helper with body-level fallback and no failure feedback. In the tested in-app browser, production list and modal copying both succeeded. The user-reported failure was not reproduced in this browser; their browser/version and settings remain unknown.

References:
- https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts
- https://developer.mozilla.org/en-US/docs/Web/API/Clipboard_API

The source already had a synchronous document.execCommand('copy') fallback. Consequently, simply saying “HTTP causes the bug” would overlook existing fallback behavior. A failed list-row copy can also mean deployed Blade differs from this checkout, browser policy denies copying, the global helper did not load, or legacy copying is unsupported. These are diagnostic possibilities, not confirmed production findings.

### Source defects and compatibility risks addressed

1. **Fallback focus inside modals.** The helper appended its temporary textarea directly to document.body. Bootstrap modal focus traps can redirect focus into the modal, defeating selection in the fallback textarea. The helper now places the textarea inside the active/open modal or native dialog, falling back to body for the list page. This specifically addresses modal copy controls; it does not prove the cause of a list-row failure.
2. **Unhandled native rejection.** The helper launched navigator.clipboard.writeText and returned immediately if legacy copying succeeded, without attaching a rejection handler to the native promise. A denied native write could then produce an unhandled rejection despite successful fallback copying. The native operation now receives success/failure handlers immediately.
3. **Silent failure.** Both methods failing previously returned false with no explanation to the user. The helper now gives a manual-copy instruction and still returns false, so a success checkmark is not shown.
4. **Mouse-only list control.** The list used a clickable i element with no keyboard semantics. It is now a type=button control with a dynamic accessible label, success title, and decorative icon. Enter/Space work through native button behavior.

Preserved: helper signature, Promise<boolean> result, immediate invocation during the click, temporary textarea cleanup, focus/range restoration, existing success checkmarks, and all order business rules.

## Additional source audit findings (not changed)

| Priority | Finding | Evidence and impact | Suggested follow-up |
| --- | --- | --- | --- |
| P2 | Selections survive replacing the current result set | loadOrders replaces this.orders without reconciling selectedOrders. Bulk action availability and preliminary stock/service checks inspect current rows, while bulk submissions send all selected IDs. Previously selected invisible orders can remain targets. | Decide explicitly between page-only and cross-page selection. Clear/reconcile selections on data changes or display and validate all selected targets. Verify server authorization/transition checks independently. |
| P2 | Invalid date parameters can become server errors | index passes from_date, to_date and dispatch dates directly to Carbon::parse without request validation in the method. Malformed input can throw rather than return a clear validation response. | Validate date format/range and array/string filter inputs before constructing the query. |
| P2 | Expired sessions may surface as JSON parsing errors | apiFetch parses response text as JSON before checking status or content type. An authentication redirect returning the HTML login page can throw a SyntaxError. | Handle redirects/401/419 and non-JSON responses before parsing. |
| P2 | Rolling date preset overrides explicit URL dates | loadOrders recalculates the date range when date=30d is present, even if the URL includes from_date/to_date. The supplied URL matches 17 October, but reopening it later changes its range. | Clarify whether shared links represent a rolling window or fixed dates, then make precedence explicit. |
| P3 | Forbidden actions redirect away from the orders page | apiFetch redirects to / on any 403 or message containing “authoriz”/“forbidden”, then resolves undefined. Callers expecting data may subsequently throw. | Return a consistent error and show the permission failure, preserving page context. |
| P3 | Detail-modal copy controls still use clickable spans/icons | Shared modal contains numerous mouse-only copy spans. This patch changes the list copy button only. | Convert remaining copy triggers to keyboard-accessible controls without changing their text/layout. |

The invisible-selection behavior was also reproduced in production: select a page-1 order, move to page 2, and the toolbar still reports one selected order while no visible row is checked. The other additional findings are from source inspection. Bulk selection behavior and rolling-link semantics require product intent to choose the correct change; they were not modified as part of the copy fix.

## Existing protections observed

- Controller list endpoint requires orders.view.
- List query applies existing role, LOB/state, ownership and permitted-status scopes.
- Sort columns are allowlisted; direction is normalized.
- Server page size is bounded from 1 to 200.
- Frontend initialization is guarded against duplicate setup.
- List requests cancel previous requests and reject stale responses by request ID.
- Toast message content is assigned through textContent.
- Copy checkmarks depend on a true result.

These observations are not a security certification of every mutation endpoint.

## Verification

Passed:
- node --unhandled-rejections=strict tests/regression/clipboard.cjs
- node tests/regression/orders_ui.cjs
- node --check resources/js/components/orders.js
- git diff --check

Clipboard regression cases: native success; HTTP/no-native fallback; modal host selection; native promise rejection with successful fallback; synchronous native exception; both methods denied; legacy exception; focus restoration; textarea cleanup. Tests use a simulated DOM and browser APIs, so they do not establish real-browser clipboard support.

Authenticated browser results:

| Check | Result |
| --- | --- |
| Production initial requested filters | Six statuses, last 30 days, 25 rows and ID descending loaded; 369 matching orders at test time. |
| Production list copy | Copied ORD-17102026-129; clipboard contained the exact ID. |
| Production modal phone copy | Clipboard matched the displayed phone number. No customer data is reproduced in this report. |
| Production console | No captured warnings/errors during the tested copy actions. |
| Production deployed helper | Earlier helper confirmed by reading the inline script in the DOM. Local fixes are not deployed. |
| Production pagination | Page 2 showed 26–50 of 369. |
| Production selection | Reproduced retained invisible selection after changing page; cleared it after the test. No bulk mutation was submitted. |
| Production search | Exact order search returned the matching order and reset to page 1. |
| Production empty state | A deliberately unmatched search showed “No orders found matching current criteria.” |
| Production sort | Order-number header set sort_field=order_no; data reloaded. This sorts identifiers lexically, so historical formats need not match chronological order. |
| Production page size | 50 rows selected; summary showed 1–50 of 369. |
| Production advanced filters | Product, fulfillment, carrier, warehouse, date, dispatch-date, state, district, taluka and village controls rendered. Individual combinations were not exhaustively executed. |
| Local patch served | Inline script contained the updated promise handling, modal host and failure feedback. |
| Local keyboard copy | Enter on the accessible copy button copied RJ171020263 exactly. |
| Local mouse and modal copy | Both copied RJ171020263 exactly. No captured warnings/errors. |

Local verification screenshot: screenshots/orders-copy-verified-2026-10-17.png.

Not verified: the reporting user's browser/version and permission state, HTTPS behavior in production, forced real-browser denial, every location/carrier filter combination, export/import contents, non-admin role behavior, and order mutations. Denial behavior is covered by simulated regressions. Order mutations were deliberately excluded from this read-only audit. A passing copy result in this browser does not invalidate a failure in another browser or session.

## Deployment notes

The copy changes are in resources/views/layouts/app.blade.php and resources/views/orders/index.blade.php. Deploy both Blade files through the normal release process and refresh compiled views with php artisan view:clear (or regenerate the view cache as required by that deployment). These particular changes do not require a Vite rebuild because no bundled JavaScript was changed. Verify that the release actually serves the new helper.

Configure a domain and valid HTTPS certificate for reliable modern Clipboard API support; HTTP fallback behavior is browser dependent. Do not disable browser security to make copying work. This audit did not change server TLS configuration or deploy the patch.
