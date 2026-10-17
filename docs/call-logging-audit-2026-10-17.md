# Call-tag administration and customer call logging audit

Reviewed Call Tags Manager and the customer order-creation page's Log Call & Close Profile flow.

## Changes

- Admin API routes now enforce the same settings-view permission as the existing web admin routes.
- New tags validate parent/level relationships; field definitions validate supported types, unique safe keys and JSON choice arrays. The editor exposes existing product search, agent search, number and datetime field types rather than silently reverting them to Text.
- Omitted field definitions and active status are preserved on update. Explicit field changes retain the existing replacement behavior.
- Tag search retains category/subject context for matching descendants; context-only ancestor checkboxes are disabled, and changing filters clears the previous selection. Bulk actions remain limited to selected records; deleting a parent soft-deletes its branch, as the existing confirmation describes. Operations are transactional, and historical calls retain deleted tag labels.
- Category/subject/field requests ignore stale responses, clear prior dependent fields immediately, display load errors and prevent saving until required configuration has loaded. Search results also ignore stale subjects and queries.
- Product search uses the actual input element for focus; supports both array and paginated search responses. Choice JSON parsing cannot crash the modal. Fields and main selectors have accessible labels.
- Save guards reject repeated in-flight clicks and repeat submissions after success. The modal cannot be dismissed during save. Server validation messages appear inline; failed saves leave the customer profile open. Storage failures cannot prevent a successful save event.
- Server validates active tag ancestry, customer existence, category/subject/outcome relationships, required/configured field rules, types and allowed choices. Crop choices use the same active-crop list in the UI and server. Existing search fields with legacy string rules accept the arrays of names actually produced by the widget.
- Call and metadata persistence remain atomic; relation loading occurs before commit so a later loading error cannot report failure after the record has committed. Agent identity comes from the authenticated user.
- Customer-profile close handling accepts success only for the current customer's saved call; existing cart cleanup and dashboard destination are retained.

## Preserved behavior

Primary category and subject remain required. Outcome remains optional. Existing call direction/status defaults, metadata storage format, target observer, call permissions, order processing and profile-close destination remain unchanged. No new migration, no reseeding and no application call/tag records were modified during verification.

## Verification

`php tests/regression/call_logging_backend.php` passed using disposable MySQL tables and synthetic fixtures. Covers hierarchy, field validation, omitted definitions, required details, customer/agent association, optional outcomes, product arrays, active crop choices, malformed metadata, inactive ancestors, call/meta rollback using a synthetic failure trigger, branch soft-deletion and historical labels. Model events were suppressed in these fixtures; target-achievement recalculation was not changed or exercised.

`node tests/regression/call_logging_ui.cjs` passed for request races, failed loads, required/whitespace details, safe option parsing, duplicate-save guards, errors, storage-independent success events and descendant search/bulk-selection safety. PHP syntax, Blade compilation and whitespace checks passed.

Browser checked customer 82014, category/subject/outcome loading, dynamic product/crop selection, required-field save blocking and cancel leaving the profile open. Admin browser checks confirmed descendant search, disabled context-only checkboxes and editor preservation of Product search / Multi-Select types. No call, order, tag update or deletion was submitted against application data. Successful persistence and rollback were verified only in disposable fixtures.

## Release

Deploy the PHP and Blade changes together, including `app/Services/CallTagFields.php`. Refresh cached views via the normal release process (`php artisan view:clear` then `php artisan view:cache`). No database migration or asset build is required for this change. Production was not deployed.

## Modal theme and search/select follow-up

The call logging modal and tag editor now use readable theme-aware labels, inputs, placeholders, option lists and selected-item controls. Category, subject, optional outcome, dynamic single-choice fields and editor selects use the existing native-backed searchable-select component. Choice lists expand within the modal body rather than being positioned outside a clipping scroll container.

Crop/multiple choices have their own search, selected-count trigger, remove buttons and no-match message. Product/agent lookup shows loading, empty and per-field error states. Starting a query clears prior results, preserves typed spaces, ignores stale responses and compares result IDs consistently. Lookup popovers stay closed after outside click; Escape closes an open list and keyboard navigation moves between results. These changes preserve metadata names, arrays, optional outcomes, validation, save and profile-close logic.

The editor groups label/key and type/required fields at usable widths, stacks them on mobile and keeps header/footer visible with a scrollable body. No settings or call records were saved during preview checks.

Verification: updated `node tests/regression/call_logging_ui.cjs` passes query spacing, request races, lookup states and selection-ID checks alongside prior regressions. Blade compilation and diff checks pass. Browser checked category/subject/outcome searching, crop selection/removal controls, product lookup, no-match feedback, Escape, dark and light field visibility, and the editor at 390px width; no new JavaScript errors appeared. Light theme and normal viewport were restored after testing. This follow-up changes only Blade/UI code and its UI regression test; no migration or asset rebuild is required.

### Create/edit form arrangement

The tag editor now has a descriptive create/edit title, associated control labels, human-readable level names and read-only parent context. Dynamic fields are numbered with removal controls in their headers, paired label/key and type/required rows, plus help for saved keys and option arrays. The Crop options note explains the existing active-crop source. Header/footer remain visible and content scrolls. Existing bindings, field values, add/remove operations, validation and save handlers are unchanged.

Verified create category, edit subject, add/remove transient dynamic fields, and create child subject with parent context in the local browser. Light and dark previews passed without JavaScript errors; no tags were saved. UI regression and Blade compilation passed. Only the admin Blade template changed for this form-arrangement follow-up.
