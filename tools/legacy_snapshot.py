#!/usr/bin/env python3
"""Export schema or manifest-scoped MySQL snapshots. Never execute a data loader."""
import argparse
import configparser
import datetime
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import tempfile

LIMIT = 16 * 1024 * 1024


def identifier(value):
    if not isinstance(value, str) or not re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*", value):
        raise ValueError("Database/table/column identifiers must be explicit simple names.")
    return value


def scope_from_manifest(manifest, role):
    if manifest.get("version") != 1 or not isinstance(manifest.get("entities"), list) or not manifest["entities"]:
        raise ValueError("A version 1 manifest with entities is required.")
    scope = {}
    for entity in manifest["entities"]:
        if not isinstance(entity, dict) or not isinstance(entity.get("fields"), dict):
            raise ValueError("Every entity needs an explicit field map.")
        dependencies = entity.get("dependencies", {})
        if not isinstance(dependencies, dict):
            raise ValueError("Invalid dependencies.")
        table = identifier(entity.get(role + "_table"))
        columns = [identifier(entity.get(role + "_id"))]
        columns += list(entity["fields"].values() if role == "source" else entity["fields"].keys())
        for target_field, dependency in dependencies.items():
            if not isinstance(dependency, dict):
                raise ValueError("Invalid dependency specification.")
            columns.append(dependency.get("source_field") if role == "source" else target_field)
        columns = sorted(set(identifier(column) for column in columns))
        if table in scope:
            raise ValueError("A table must occur once in the selected scope.")
        if any(re.search(r"password|remember_token|api_key|secret|access_token|refresh_token", column, re.I) for column in columns):
            raise ValueError("Credential columns are not migration-planning inputs.")
        scope[table] = columns
    return scope


def sql_for(scope):
    # SET changes this connection only; all persistent operations are SELECT.
    lines = ["SET SESSION time_zone = '+00:00';", "START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY;",
        "SELECT JSON_OBJECT('kind','identity','database',DATABASE(),'server_hostname',@@hostname,'server_version',VERSION(),'snapshot_utc',UTC_TIMESTAMP());",
        "SELECT JSON_OBJECT('kind','table','table',TABLE_NAME,'engine',ENGINE,'table_type',TABLE_TYPE) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME;",
        "SELECT JSON_OBJECT('kind','column','table',TABLE_NAME,'column',COLUMN_NAME,'type',COLUMN_TYPE,'nullable',IS_NULLABLE,'key',COLUMN_KEY) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION;"]
    for table, columns in scope.items():
        pairs = ",".join("'%s',CAST(`%s` AS CHAR)" % (column, column) for column in columns)
        # JSON_OBJECT keeps NULL as null; numeric/date values become lossless strings.
        lines.append("SELECT JSON_OBJECT('kind','row','table','%s','value',JSON_OBJECT(%s)) FROM `%s`;" % (table, pairs, table))
    return "\n".join(lines + ["ROLLBACK;"]) + "\n"


def credentials(path):
    if stat.S_IMODE(path.stat().st_mode) & 0o077:
        raise ValueError("Credential file must be private (chmod 600).")
    parser = configparser.RawConfigParser()
    with path.open(encoding="utf-8") as stream:
        parser.read_file(stream)
    if parser.sections() != ["client"] or set(parser["client"]) - {"host", "port", "user", "password", "ssl-ca"}:
        raise ValueError("Only host, port, user, password and optional ssl-ca are supported in [client].")
    section = parser["client"]
    if not all(section.get(key) for key in ["host", "user", "password"]):
        raise ValueError("Credential file needs explicit host, user and password.")
    port = int(section.get("port", "3306"))
    if not 1 <= port <= 65535:
        raise ValueError("Invalid MySQL port.")
    return dict(section), port


def collect(database, connection, port, scope):
    command = ["mysql", "--no-defaults", "--no-login-paths", "--batch", "--raw", "--skip-column-names", "--default-character-set=utf8mb4",
        "--host=" + connection["host"], "--port=" + str(port), "--user=" + connection["user"], "--database=" + database]
    if connection.get("ssl-ca"):
        command += ["--ssl-ca=" + connection["ssl-ca"]]
    environment = os.environ.copy()
    environment["MYSQL_PWD"] = connection["password"]
    with tempfile.TemporaryFile() as output, tempfile.TemporaryFile() as errors:
        result = subprocess.run(command, input=sql_for(scope).encode(), stdout=output, stderr=errors,
            env=environment, timeout=120, check=False)
        if result.returncode:
            raise ValueError("MySQL rejected collection; no snapshot published. Check access, identifiers and MySQL JSON support.")
        if output.tell() > LIMIT:
            raise ValueError("Snapshot exceeds the 16 MiB planning limit; narrow the manifest scope.")
        output.seek(0)
        identity = None
        schema = []
        table_metadata = []
        tables = {table: [] for table in scope}
        for line in output:
            item = json.loads(line)
            if item.get("kind") == "identity" and identity is None:
                identity = {key: value for key, value in item.items() if key != "kind"}
            elif item.get("kind") == "column":
                schema.append({key: value for key, value in item.items() if key != "kind"})
            elif item.get("kind") == "table":
                table_metadata.append({key: value for key, value in item.items() if key != "kind"})
            elif item.get("kind") == "row" and item.get("table") in tables and isinstance(item.get("value"), dict):
                tables[item["table"]].append(item["value"])
            else:
                raise ValueError("Unexpected collection output; no snapshot published.")
    if not identity or identity.get("database") != database:
        raise ValueError("Database identity mismatch; no snapshot published.")
    available = {(column["table"], column["column"]) for column in schema}
    if any((table, column) not in available for table, columns in scope.items() for column in columns):
        raise ValueError("Scoped columns missing from schema metadata.")
    unexported = sorted({column["table"] for column in schema} - set(scope))
    return {"version": 1, "mode": "SCOPED_ROWS" if scope else "SCHEMA_ONLY", "identity": identity,
        "schema": schema, "table_metadata": table_metadata, "tables": tables,
        "unexported_tables": unexported,
        "scope_complete": False, "note": "Only manifest scope exported; not a full database backup."}


def publish(path, report):
    data = (json.dumps(report, ensure_ascii=False, indent=2) + "\n").encode()
    if len(data) > LIMIT:
        raise ValueError("Serialized snapshot exceeds the 16 MiB planner limit.")
    # Exclusive creation refuses overwrites, including existing symlinks.
    descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    try:
        with os.fdopen(descriptor, "wb") as output:
            output.write(data)
    except BaseException:
        path.unlink(missing_ok=True)
        raise


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--database", required=True)
    parser.add_argument("--credentials", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--manifest", type=Path)
    parser.add_argument("--role", choices=["source", "target"])
    args = parser.parse_args(argv)
    try:
        database = identifier(args.database)
        if args.output.exists() or args.output.is_symlink():
            raise ValueError("Output already exists; choose a new filename.")
        if bool(args.manifest) != bool(args.role):
            raise ValueError("Manifest and role must be supplied together.")
        scope = {}
        manifest_hash = None
        if args.manifest:
            raw = args.manifest.read_bytes()
            if len(raw) > LIMIT:
                raise ValueError("Manifest too large.")
            manifest = json.loads(raw)
            if not isinstance(manifest, dict):
                raise ValueError("Manifest must be an object.")
            scope = scope_from_manifest(manifest, args.role)
            manifest_hash = hashlib.sha256(raw).hexdigest()
        connection, port = credentials(args.credentials)
        report = collect(database, connection, port, scope)
        report["export_role"] = args.role
        report["manifest_sha256"] = manifest_hash
        report["collector_completed_utc"] = datetime.datetime.now(datetime.timezone.utc).isoformat()
        publish(args.output, report)
        print("Snapshot saved: %s; %d scoped rows. No database writes." % (report["mode"], sum(map(len, report["tables"].values()))))
        return 0
    except (ValueError, OSError, configparser.Error, subprocess.SubprocessError):
        # Do not echo connection errors, secrets, row contents or private paths.
        print("Snapshot not published. Check private credentials, database identity, manifest scope, output path and collection limits.")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
