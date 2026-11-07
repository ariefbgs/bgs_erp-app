import contextlib
import importlib.util
import io
import json
from pathlib import Path
import stat
import subprocess
import tempfile
import unittest
from unittest.mock import patch

module_spec = importlib.util.spec_from_file_location("legacy_snapshot", Path(__file__).resolve().parents[2] / "tools" / "legacy_snapshot.py")
exporter = importlib.util.module_from_spec(module_spec)
module_spec.loader.exec_module(exporter)


class SnapshotExportTest(unittest.TestCase):
    def manifest(self):
        return {"version": 1, "entities": [{"name": "orders", "source_table": "old_orders", "target_table": "po_customers",
            "source_id": "old_id", "target_id": "id", "fields": {"po_number": "number", "amount": "total"},
            "key": ["po_number"], "dependencies": {"customer_id": {"entity": "customers", "source_field": "old_customer"}}}]}

    def fake_mysql(self, records, returncode=0):
        def run(command, **kwargs):
            self.assertNotIn("hidden-password", " ".join(command))
            self.assertEqual("hidden-password", kwargs["env"]["MYSQL_PWD"])
            self.assertNotIn("--no-login-paths", command)
            self.assertEqual(Path(kwargs["env"]["HOME"]), Path(kwargs["env"]["MYSQL_TEST_LOGIN_FILE"]).parent)
            self.assertFalse(Path(kwargs["env"]["MYSQL_TEST_LOGIN_FILE"]).exists())
            sql = kwargs["input"].decode()
            self.assertIn("START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY;", sql)
            self.assertTrue(sql.endswith("ROLLBACK;\n"))
            self.assertNotRegex(sql, r"(?i)\b(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|LOCK)\b")
            kwargs["stdout"].write("\n".join(json.dumps(row) for row in records).encode())
            return subprocess.CompletedProcess(command, returncode)
        return run

    def records(self):
        return [{"kind": "identity", "database": "expected_db", "server_version": "8.4", "snapshot_utc": "2026-11-06 00:00:00"},
            {"kind": "column", "table": "customers", "column": "id", "type": "bigint"},
            {"kind": "column", "table": "customers", "column": "code", "type": "varchar(50)"},
            {"kind": "column", "table": "not_exported", "column": "id", "type": "bigint"},
            {"kind": "row", "table": "customers", "value": {"id": "7", "code": "0000123.4500"}}]

    def test_scopes_only_declared_columns_and_dependencies(self):
        manifest = self.manifest()
        self.assertEqual({"old_orders": ["number", "old_customer", "old_id", "total"]}, exporter.scope_from_manifest(manifest, "source"))
        self.assertEqual({"po_customers": ["amount", "customer_id", "id", "po_number"]}, exporter.scope_from_manifest(manifest, "target"))
        sql = exporter.sql_for({"old_orders": ["total"]})
        self.assertIn("CAST(`total` AS CHAR)", sql)
        self.assertNotIn("SELECT *", sql)

    def test_invalid_identifiers_and_credentials_columns_are_rejected(self):
        for name in ["x`; DROP TABLE x", "x y", None, "x.y"]:
            with self.assertRaises(ValueError): exporter.identifier(name)
        manifest = self.manifest(); manifest["entities"][0]["fields"]["password"] = "password"
        with self.assertRaises(ValueError): exporter.scope_from_manifest(manifest, "source")

    def test_export_preserves_values_and_unexported_inventory(self):
        with patch.object(exporter.subprocess, "run", side_effect=self.fake_mysql(self.records())):
            report = exporter.collect("expected_db", {"host": "127.0.0.1", "user": "reader", "password": "hidden-password"}, 3306,
                {"customers": ["id", "code"]})
        self.assertEqual("0000123.4500", report["tables"]["customers"][0]["code"])
        self.assertEqual(["not_exported"], report["unexported_tables"])
        self.assertFalse(report["scope_complete"])

    def test_schema_only_has_no_data_queries(self):
        sql = exporter.sql_for({})
        self.assertNotIn("'kind','row'", sql)
        with patch.object(exporter.subprocess, "run", side_effect=self.fake_mysql(self.records()[:-1])):
            report = exporter.collect("expected_db", {"host": "h", "user": "u", "password": "hidden-password"}, 3306, {})
        self.assertEqual({}, report["tables"])
        self.assertEqual("SCHEMA_ONLY", report["mode"])

    def test_identity_mismatch_and_mysql_errors_never_return_snapshot(self):
        connection = {"host": "h", "user": "u", "password": "hidden-password"}
        with patch.object(exporter.subprocess, "run", side_effect=self.fake_mysql(self.records())):
            with self.assertRaises(ValueError): exporter.collect("different_db", connection, 3306, {})
        with patch.object(exporter.subprocess, "run", side_effect=self.fake_mysql([], 1)):
            with self.assertRaises(ValueError): exporter.collect("expected_db", connection, 3306, {})

    def test_private_output_refuses_overwrite(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "snapshot.json"
            exporter.publish(path, {"tables": {}})
            self.assertEqual(0, stat.S_IMODE(path.stat().st_mode) & 0o077)
            before = path.read_bytes()
            with self.assertRaises(FileExistsError): exporter.publish(path, {"changed": True})
            self.assertEqual(before, path.read_bytes())

    def test_public_credentials_and_client_init_commands_are_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "client.cnf"
            path.write_text("[client]\nhost=h\nuser=u\npassword=hidden-password\n")
            path.chmod(0o644)
            with self.assertRaises(ValueError): exporter.credentials(path)
            path.chmod(0o600)
            self.assertEqual(3306, exporter.credentials(path)[1])
            path.write_text(path.read_text() + "init-command=DELETE FROM customers\n")
            with self.assertRaises(ValueError): exporter.credentials(path)

    def test_failed_collection_does_not_publish_or_echo_secrets(self):
        with tempfile.TemporaryDirectory() as directory:
            config = Path(directory) / "client.cnf"
            config.write_text("[client]\nhost=h\nuser=u\npassword=hidden-password\n"); config.chmod(0o600)
            output = Path(directory) / "snapshot.json"
            printed = io.StringIO()
            with contextlib.redirect_stdout(printed), patch.object(exporter.subprocess, "run", side_effect=self.fake_mysql([], 1)):
                result = exporter.main(["--database", "expected_db", "--credentials", str(config), "--output", str(output)])
            self.assertEqual(1, result)
            self.assertFalse(output.exists())
            self.assertNotIn("hidden-password", printed.getvalue())


if __name__ == "__main__":
    unittest.main()
