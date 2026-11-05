# Import village.csv into metis.villages with MySQL Workbench

1. Back up `metis.villages` if it already contains data. Matching IDs will be updated.
2. Open a fresh Workbench connection to the intended MySQL server.
3. Choose **File → Open SQL Script** and open `import-villages.sql`.
4. Execute the **entire script** (not only the statement under the cursor).
5. Confirm the final result says `SUCCESS` and `csv_rows_verified = 19708`.

The CSV data is embedded in the SQL file. You do not need the CSV on the database
server, the Table Data Import Wizard, LOCAL INFILE, or a special file path.
The account needs SELECT/INSERT/UPDATE on `metis.villages`, CREATE TEMPORARY TABLES,
and CREATE ROUTINE/ALTER ROUTINE/EXECUTE permissions for the temporary import
procedure. The `metis` database and compatible `villages` table must already exist.

## Data handling

- Contains all 19,708 CSV records and all 15 original columns; IDs and gaps stay intact.
- Updates matching IDs and inserts missing IDs. Does not truncate the table,
  delete/reinsert existing records, or remove rows outside this CSV.
- Converts `DD-MM-YYYY HH:MM` to MySQL timestamps, preserving the supplied minute
  precision (seconds become 00).
- Uses `+05:30`, matching the application's Asia/Kolkata timezone. The CSV has no
  timezone metadata. If these exported clock times represent a different timezone,
  change the `SET SESSION time_zone` line before executing.
- **421 village_name values are the literal text `NULL`**, preserved as text because
  village_name is NOT NULL. No substitute village names are invented.
- `NULL` markers in nullable columns become SQL NULL, including all deleted_at values.
- Embedded strings use UTF-8 hexadecimal literals, preserving apostrophes,
  backslashes and Unicode independently of SQL string-escaping settings.

## Validation and failure handling

Staging loads in 100-row batches. No target rows change until the full 19,708-row
staging table exists. The final merge runs in one transaction and compares every
column of every imported row before committing. Incomplete staging or a failed
merge raises an error and leaves target rows unchanged. If an error occurs, fix
its cause and rerun the complete file on a fresh connection. SQL errors are not
silenced or skipped. The file can be rerun without creating duplicate IDs.

Verified against a disposable copy of the actual local table schema: full import,
second import, updating an existing ID, preserving an unrelated row, literal NULL
names, and incomplete-staging protection. The real metis.villages table was not
modified by this verification.
