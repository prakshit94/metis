#!/usr/bin/env python3
"""Read only DDL from the supplied dump; never include private row data in artifacts."""
import pathlib, re, json, hashlib
root=pathlib.Path(__file__).resolve().parents[3]
backup=pathlib.Path('/home/user/dumps/metis120261029.sql')
out=pathlib.Path(__file__).resolve().parent
source={};active=None; block=[]
for line in backup.open():
    m=re.match(r'CREATE TABLE `([^`]+)`',line)
    if m: active=m[1];block=[line];continue
    if active:
        block.append(line)
        if line.startswith(') ENGINE='):source[active]=''.join(block);active=None
# Schema-only snapshot audited against the PHP migrations; not a Laravel schema dump.
current=json.loads((out/'audited-target-schema.json').read_text())
def columns(ddl):return dict(re.findall(r'^  `([^`]+)` (.*?)(?:,)?$',ddl,re.M))
def normalized(s):return re.sub(r' CHARACTER SET utf8mb4(?= COLLATE utf8mb4_)','',s)
assert not source.keys()-current.keys(), 'Backup-only tables need explicit review'
changes={}
for t,ddl in source.items():
    a,b=columns(ddl),columns(current[t]);assert not a.keys()-b.keys(),f'Unmapped columns: {t}'
    diff={k:[a[k],b[k]] for k in a.keys()&b.keys() if normalized(a[k])!=normalized(b[k])}
    assert not diff or (t=='order_items' and list(diff)==['unit_price']),diff
    added={k:b[k] for k in b.keys()-a.keys()}
    assert all('DEFAULT' in d or 'NOT NULL' not in d for d in added.values())
    if added or diff:changes[t]={'new_columns':added,'type_changes':diff}
report={'backup_tables':len(source),'project_tables':len(current),'new_tables':sorted(current.keys()-source.keys()),'changes':changes,'backup_sha256':hashlib.file_digest(backup.open('rb'),'sha256').hexdigest()}
report['migration_hashes']={p.name:hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted((root/'database/migrations').glob('*.php'))}
report['generated_columns']={'leave_balances.balance':'total_leaves - used_leaves; unchanged, computed by MySQL'}
audited=json.loads((out/'audited-target-schema.json').read_text())
report['index_changes']={}
for table,ddl in source.items():
    definitions=lambda d:{line.rstrip(',') for line in re.findall(r'^  (?:(?:UNIQUE )?KEY|PRIMARY KEY|CONSTRAINT).*',d,re.M)}
    before,after=definitions(ddl),definitions(audited[table])
    if before!=after:report['index_changes'][table]={'backup_only_definitions':sorted(before-after),'current_only_definitions':sorted(after-before),'transition_action':'retain all current definitions unchanged'}
(out/'schema-audit.json').write_text(json.dumps(report,indent=2)+'\n')
# No cross-schema source qualifiers are accepted in identifiers.
assert all(re.fullmatch(r'[a-z_0-9]+',t) for t in current)
q=lambda x:'`'+x+'`'
sql=['''-- DATA REPLACEMENT. MySQL 8 Workbench or mysql CLI; stop execution on errors.
-- Source must be an immutable restored staging DB named metis_transition_backup_20261029.
-- Target is deliberately fixed to metis. Both applications/workers must be stopped.
USE `metis`;
DELIMITER $$
CREATE PROCEDURE `metis_replace_backup_20261029`()
BEGIN
  DECLARE transition_step VARCHAR(100) DEFAULT 'preflight';
  DECLARE old_safe_updates INT DEFAULT @@SESSION.sql_safe_updates;
  DECLARE old_fk INT DEFAULT @@SESSION.foreign_key_checks;
  DECLARE old_mode TEXT DEFAULT @@SESSION.sql_mode;
  DECLARE old_tz VARCHAR(64) DEFAULT @@SESSION.time_zone;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SELECT transition_step AS failed_transition_step;
    ROLLBACK;
    SET SESSION sql_safe_updates=old_safe_updates;
    SET SESSION foreign_key_checks=old_fk;
    SET SESSION sql_mode=old_mode;
    SET SESSION time_zone=old_tz;
    RESIGNAL;
  END;
  IF DATABASE() <> 'metis' OR COALESCE(@METIS_REPLACE_CONFIRMED, '') <> 'REPLACE ALL METIS DATA FROM 20261029' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Read README; set explicit confirmation variable in this same connection';
  END IF;
  IF @@SESSION.autocommit <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Use a fresh connection with autocommit enabled';
  END IF;
  SET SESSION time_zone='+00:00';
  SET SESSION sql_mode='STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
  IF EXISTS (SELECT 1 FROM information_schema.triggers WHERE trigger_schema='metis') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Unexpected target triggers: review before replacement';
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='metis' AND (table_type <> 'BASE TABLE' OR engine <> 'InnoDB')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only transactional InnoDB base tables are supported';
  END IF;
''']
def fail(condition,message):sql.append(f"  IF {condition} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='{message}'; END IF;\n")
fail("(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='metis' AND table_type='BASE TABLE') <> 119",'Target table inventory differs: re-audit required')
fail("(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='metis_transition_backup_20261029' AND table_type='BASE TABLE') <> 109",'Staging inventory differs from audited backup')
for t in sorted(current):
    for c in columns(current[t]):fail(f"NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='metis' AND table_name='{t}' AND column_name='{c}')",f'Missing target field {t}.{c}')
# Compare every target field type against the audited live schema.
live=json.loads((out/'audited-target-schema.json').read_text())
assert set(live)==set(current)
for t in sorted(live):
    for c,d in columns(live[t]).items():
        dtype=re.split(r' (?:CHARACTER SET|COLLATE|NOT NULL|NULL|DEFAULT|AUTO_INCREMENT|GENERATED|COMMENT)',d,1)[0]
        # Numeric type names without a following qualifier are already complete.
        dtype=dtype.replace("'", "''")
        fail(f"NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='metis' AND table_name='{t}' AND column_name='{c}' AND column_type='{dtype}')",f'Target field type changed: {t}.{c}')
for t in sorted(source):
    for c in columns(source[t]):fail(f"NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='metis_transition_backup_20261029' AND table_name='{t}' AND column_name='{c}')",f'Missing backup field {t}.{c}')
sql.append("  SET transition_step='validate backup row counts';\n")
# Refuse incomplete staging restores before deleting target rows.
data_audit=json.loads((out/'backup-data-audit.json').read_text())
assert data_audit['source_rows']==sum(data_audit['table_counts'].values())
assert set(data_audit['table_counts'])==set(source)
assert data_audit['nonzero_orphan_groups']==[]
for t,count in sorted(data_audit['table_counts'].items()):
    fail(f'(SELECT COUNT(*) FROM `metis_transition_backup_20261029`.{q(t)}) <> {count}',f'Backup row count differs: {t}')
sql.append("  SET transition_step='validate PHP migration history';\n")
# Deployment metadata must reflect the current PHP-only migration history.
paths=sorted((root/'database/migrations').glob('*.php'))
fail(f'(SELECT COUNT(*) FROM `migrations`) <> {len(paths)}','Run all current PHP migrations before importing data')
for path in paths:
    fail(f"NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration='{path.stem}')",'Current PHP migration is missing: '+path.stem[:85])
# Constraint checks refer to the final target, after copy, before commit. No automatic repair.
sql.append('  SET SESSION sql_safe_updates=0;\n  SET SESSION foreign_key_checks=0;\n  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;\n  START TRANSACTION;\n  SET transition_step=\'delete existing data\';\n')
for t in sorted(current):
    if t!='migrations':sql.append(f'  DELETE FROM {q(t)};\n')
for t in sorted(source):
    if t=='migrations':continue
    cols=', '.join(q(c) for c,d in columns(source[t]).items() if 'GENERATED ALWAYS' not in d)
    sql.append(f"  SET transition_step='copy {t}';\n")
    sql.append(f'  INSERT INTO {q(t)} ({cols}) SELECT {cols} FROM `metis_transition_backup_20261029`.{q(t)};\n')
    fail(f'(SELECT COUNT(*) FROM {q(t)}) <> (SELECT COUNT(*) FROM `metis_transition_backup_20261029`.{q(t)})',f'Row count mismatch: {t}')
sql.append("  SET transition_step='integrity checks';\n")
for t in sorted(current.keys()-source.keys()):fail(f'EXISTS (SELECT 1 FROM {q(t)})',f'Expected empty new table: {t}')
for t,ddl in sorted(current.items()):
    for name,cs,parent,ps in re.findall(r'CONSTRAINT `([^`]+)` FOREIGN KEY \(([^)]+)\) REFERENCES `([^`]+)` \(([^)]+)\)',ddl):
        children=re.findall(r'`([^`]+)`',cs);parents=re.findall(r'`([^`]+)`',ps)
        nonnull=' AND '.join(f'c.{q(c)} IS NOT NULL' for c in children)
        match=' AND '.join(f'p.{q(p)}=c.{q(c)}' for c,p in zip(children,parents))
        fail(f'EXISTS (SELECT 1 FROM {q(t)} c WHERE {nonnull} AND NOT EXISTS (SELECT 1 FROM {q(parent)} p WHERE {match}))',f'Orphan foreign key: {name}'[:128])
sql.append('''  COMMIT;
  SET SESSION sql_safe_updates=old_safe_updates;
  SET SESSION foreign_key_checks=old_fk;
  SET SESSION sql_mode=old_mode;
  SET SESSION time_zone=old_tz;
END$$
DELIMITER ;
CALL `metis_replace_backup_20261029`();
DROP PROCEDURE `metis_replace_backup_20261029`;
-- Existing migrations metadata and all table/column/index definitions were retained.
-- AUTO_INCREMENT counters intentionally remain monotonic; no DDL reset is necessary.
''')
(out/'replace-data.sql').write_text(''.join(sql))
# Row-level verification of every copied source column (binary strings, NULL-safe), independent of IDs.
# Group count comparison handles tables with composite/no primary keys and identical duplicate rows.
verify=["USE `metis`;\nSET @METIS_VERIFICATION_PREVIOUS_TIME_ZONE=@@SESSION.time_zone;\nSET SESSION time_zone='+00:00';\n"]
for t in sorted(source):
    if t=='migrations':continue
    if t in ['model_has_permissions','model_has_roles']:
        # Nullable team identifiers permit duplicate unique-key tuples in MySQL.
        # Compare full-row multiplicity too; these columns fit within max_sort_length.
        cols=list(columns(source[t])); select=', '.join(f'BINARY {q(c)} AS {q(c)}' for c in cols); group=', '.join(map(q,cols))
        verify.append(f"SELECT '{t}' AS table_name, (SELECT COUNT(*) FROM `metis`.{q(t)}) AS target_rows, (SELECT COUNT(*) FROM `metis_transition_backup_20261029`.{q(t)}) AS source_rows, (SELECT COUNT(*) FROM (SELECT {group}, SUM(n) AS delta FROM (SELECT {select}, 1 AS n FROM `metis`.{q(t)} UNION ALL SELECT {select}, -1 AS n FROM `metis_transition_backup_20261029`.{q(t)}) row_values GROUP BY {group} HAVING SUM(n) <> 0) differences) AS mismatched_source_rows;\n")
        continue
    cols=columns(source[t]); pk=re.search(r'PRIMARY KEY \(([^)]+)\)',source[t])
    if not pk: pk=re.search(r'UNIQUE KEY `[^`]+` \(([^)]+)\)',source[t])
    assert pk, f'No unique row identifier for {t}'
    keys=re.findall(r'`([^`]+)`',pk[1])
    join=' AND '.join(f'a.{q(c)} <=> b.{q(c)}' for c in keys)
    same=[]
    for c,d in cols.items():
        text=bool(re.match(r'(?:var)?char|(?:tiny|medium|long)?text|enum|set|json|(?:var)?binary|(?:tiny|medium|long)?blob',d))
        same.append(f'{"BINARY " if text else ""}a.{q(c)} <=> {"BINARY " if text else ""}b.{q(c)}')
    mismatch=' AND '.join('('+x+')' for x in same)
    verify.append(f"SELECT '{t}' AS table_name, (SELECT COUNT(*) FROM `metis`.{q(t)}) AS target_rows, (SELECT COUNT(*) FROM `metis_transition_backup_20261029`.{q(t)}) AS source_rows, (SELECT COUNT(*) FROM `metis_transition_backup_20261029`.{q(t)} b WHERE NOT EXISTS (SELECT 1 FROM `metis`.{q(t)} a WHERE {join} AND {mismatch})) AS mismatched_source_rows;\n")
verify.append('SET SESSION time_zone=@METIS_VERIFICATION_PREVIOUS_TIME_ZONE;\n')
(out/'verify-data.sql').write_text(''.join(verify))
print(json.dumps(report,indent=2))

audit=[]
for t in sorted(source):audit.append(f"SELECT 'rows' AS kind, '{t}' AS object_name, COUNT(*) AS total FROM `metis_transition_backup_20261029`.{q(t)};\n")
for t,ddl in sorted(current.items()):
    if t not in source:continue
    for name,cs,parent,ps in re.findall(r'CONSTRAINT `([^`]+)` FOREIGN KEY \(([^)]+)\) REFERENCES `([^`]+)` \(([^)]+)\)',ddl):
        children=re.findall(r'`([^`]+)`',cs);parents=re.findall(r'`([^`]+)`',ps)
        if parent not in source or any(c not in columns(source[t]) for c in children):continue
        nonnull=' AND '.join(f'c.{q(c)} IS NOT NULL' for c in children)
        match=' AND '.join(f'p.{q(p)}=c.{q(c)}' for c,p in zip(children,parents))
        audit.append(f"SELECT 'orphans' AS kind, '{name}' AS object_name, COUNT(*) AS total FROM `metis_transition_backup_20261029`.{q(t)} c WHERE {nonnull} AND NOT EXISTS (SELECT 1 FROM `metis_transition_backup_20261029`.{q(parent)} p WHERE {match});\n")
(out/'audit-backup.sql').write_text(''.join(audit))

# Always rebuild the actual delivered file together with its component scripts.
header="""-- COMPLETE METIS DATA IMPORT FOR MYSQL 8: WORKBENCH OR MYSQL CLI.
-- 1. Run the current PHP migrations/seeding on the target before this import.
-- 2. Restore /home/user/dumps/metis120261029.sql into metis_transition_backup_20261029 first.
-- 3. Stop both apps and workers, save a recovery backup, use a fresh connection with autocommit ON.
-- Workbench: enable Stop Script Execution On Errors and increase the query read timeout.
-- Execute this ENTIRE file. Do not remove DELIMITER directives. CLI: never use --force.
-- Preserves schema and current migration history; replaces ALL other target data.
-- New storefront/office/mail/2FA tables absent from the backup become EMPTY.
-- SQL_SAFE_UPDATES is disabled only inside the procedure and restored on success/failure.
SET @METIS_REPLACE_CONFIRMED='REPLACE ALL METIS DATA FROM 20261029';
"""
(out/'import-data-full.sql').write_text(header+''.join(sql)+'\n-- Post-commit full copied-field verification: counts must match; mismatches must be zero.\n'+''.join(verify))
