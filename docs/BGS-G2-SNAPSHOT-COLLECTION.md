# G2 — Read-only snapshot collection

The collector prepares inputs for `legacy:plan`; it does not load data, run application migrations or connect automatically. Requirements: Python 3.8+ and a MySQL command-line client/server with JSON_OBJECT support (MySQL 5.7+ or a compatible MariaDB version). Use an account already restricted to read-only access. Actual source/target identity and export freshness still require operator verification.

## Two phases

1. Collect schema metadata for the explicitly named source and target databases. Without a manifest, the collector executes no business-row SELECTs and returns `SCHEMA_ONLY` with an empty tables object. This is not a usable data snapshot for a nonempty planning scope.
2. Review source-to-target columns and business rules, then supply the approved version 1 manifest and role `source` or `target`. Only declared identity, mapped and dependency fields are exported. Data values are lossless strings or null; numeric/date conversion remains a reviewed transformation step. Full column metadata is included separately.

The historical 12 October audit of `erp_app` does not establish the current identity/schema of `smartlo1_erp_app`. Never substitute it for a fresh confirmed source export.

## Private client file

Create the file privately on the machine running the collector; do not send its contents or commit it. Only this subset is supported, with optional `ssl-ca`:

```ini
[client]
host=YOUR_CONFIRMED_HOST
port=3306
user=YOUR_EXISTING_READ_ONLY_USER
password=YOUR_PASSWORD
```

Set permissions to 600. Global client defaults and init commands are disabled; unrecognized options are rejected. Passwords are passed to the child client via its environment, never command arguments or reports. Connection error text is not echoed. The file is not copied into the snapshot.

## Collection commands

These are operator examples; replace private paths and the target database name with confirmed values. The source name below is the previously supplied legacy name, not a verified current connection.

```bash
python3 tools/legacy_snapshot.py --database smartlo1_erp_app --credentials /private/bgs-source.cnf --output /private/bgs-source-schema.json
python3 tools/legacy_snapshot.py --database TARGET_DATABASE_NAME --credentials /private/bgs-target.cnf --output /private/bgs-target-schema.json
```

After manifest review:

```bash
python3 tools/legacy_snapshot.py --database smartlo1_erp_app --credentials /private/bgs-source.cnf --manifest /private/manifest.json --role source --output /private/bgs-source-rows.json
python3 tools/legacy_snapshot.py --database TARGET_DATABASE_NAME --credentials /private/bgs-target.cnf --manifest /private/manifest.json --role target --output /private/bgs-target-rows.json
php artisan legacy:plan /private/manifest.json /private/bgs-source-rows.json /private/bgs-target-rows.json
```

The collector runs SET SESSION time_zone, START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY, SELECT and ROLLBACK on one client connection. No INSERT/UPDATE/DELETE/DDL or explicit table locking is issued. Consistent-snapshot guarantees apply to transactional tables; storage-engine review and concurrent DDL coordination are still required for operational exports. It is not a backup utility.

The requested database is compared against SELECT DATABASE(). Unexpected client output, nonzero client status, missing scoped columns, identity mismatch or oversized output prevents publication. Output files are created privately and exclusively; existing files are never overwritten. Maximum input manifest and serialized output are 16 MiB each. Narrow the manifest for larger domains; chunk orchestration is future scope.

`unexported_tables` lists schema tables absent from the row export, including empty tables that were not selected. The planner preserves this inventory in `unscoped_source_tables`; such gaps return review exit code 2. No full-database or load-readiness PASS is implied by a scoped export.

## Evidence boundaries

Local unit tests mock the MySQL client and verify scope, read-only SQL, identity guards, private/exclusive output, failure behavior and secret handling. CI also uses an actual disposable MySQL service for a synthetic export and reconciliation report. These prove collector behavior, not access to an operational database or real-data migration readiness.

Operational credentials, snapshots and reports stay outside Git. No operational collection has been executed as part of preparing this tool. Before real-data planning, provide the confirmed database identities and privately retained schema/row snapshots; do not paste passwords into chat.
