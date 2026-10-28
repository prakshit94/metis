# Teams theme and office address update

Completed 28 October 2026.

- Teams uses the existing theme's page heading, directory header, cards, subtle surfaces, state accents and responsive scrollable office modal.
- Office create/edit now provides Address Details matching Users: address lines 1/2, village search, selected village/post office/taluka/district/state/PIN summary, and manual postal details.
- Uses the existing permission-protected `/api/villages/search` endpoint and existing state scope. Search errors allow manual entry; permissions were not expanded.
- Search results are bounded and scroll within the form, without card-clipped overlays. Late responses cannot populate a different office modal.
- Additive nullable office columns preserve existing data. Legacy `address` remains supported and synchronized with address line 1 on writes. Editing descriptions or other unrelated fields preserves address data.
- Postal state is descriptive address data; it does not change the parent team, access mapping, state-wide visibility, office assignments, order attribution or numbering.

Verification: all 44 isolated state/office integration checks passed, including six address persistence/compatibility/validation checks. Frontend checks cover autofill, legacy address loading, request ordering, reset, payloads, manual fallback and parent-state preservation. PHP/Blade compilation, route/permission/response contracts and whitespace checks passed. Browser preview verified theme, scrollable form, village search and postal detail selection using sample data; no sample offices were added to the application.

Migration `2026_10_28_170000_add_office_address_details` applied locally; compiled views cleared. Only Blade inline markup/scripts and PHP changed, so bundled frontend assets did not require rebuilding.
