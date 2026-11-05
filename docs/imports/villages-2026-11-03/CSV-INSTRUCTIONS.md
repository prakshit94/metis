# Direct CSV import in MySQL Workbench

Use `village-workbench.csv` with Table Data Import Wizard on the existing
`metis.villages` table. Select UTF-8, comma delimiter, double-quote text qualifier,
and the first row as column names. Map columns to the same names. Keep ID mapped
to preserve source IDs. Date/time values now use YYYY-MM-DD HH:MM:SS.

All 19,708 records are retained. Only deleted_at is omitted from this direct-import
file: every source value is NULL and the target column defaults to SQL NULL.
This avoids trying to import the text 'NULL' into a timestamp column.
The all-columns version is provided separately if you need the full original
15-column layout; do not map its deleted_at text values as timestamps.

The 421 village names written as NULL remain literal text NULL; do not replace
them with SQL NULL because village_name is NOT NULL. All non-date text, flags,
IDs and pincodes are preserved from the CSV. No database data has been changed.

Import into an empty table or one without overlapping source IDs. The CSV wizard
is an insert workflow: it does not provide the SQL script's matching-ID update,
transactional staging or verification safeguards. If the IDs already exist,
use the previously supplied SQL merge script. Do not truncate the table merely
to fix duplicate IDs because other records reference these village IDs.

Avoid reopening and saving the converted CSV in Excel before import, since Excel
may reformat timestamps again. Use the file directly from Workbench.
