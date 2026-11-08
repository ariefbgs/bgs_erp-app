#!/usr/bin/env python3
"""Offline raw-snapshot integrity verification; no DB connections or data mutation."""
import hashlib, json, pathlib, sys

if len(sys.argv) != 2:
    raise SystemExit('USAGE: python3 verify-snapshot-g500.py /private/run-directory')
root = pathlib.Path(sys.argv[1]).resolve(strict=True)
if not root.is_dir():
    raise SystemExit('BLOCKED: not a directory')
manifest = json.loads((root/'manifest.json').read_text())
if manifest.get('report') != 'BGS_G500_READ_ONLY_SOURCE_SNAPSHOT' or manifest.get('source') != 'smartlo1_erp_app':
    raise SystemExit('BLOCKED: manifest identity')
expected = {'masters': ('companies','customers','suppliers','products'),
 'orders': ('quotations','quotation_details','po_customers','po_customer_details','po_suppliers','po_supplier_details'),
 'fulfilment': ('goods_receipts','goods_receipt_details','invoice_customers','invoice_customer_details','delivery_orders','delivery_order_details','document_receipts')}
verified = 0
for group, tables in expected.items():
    name = group+'.json'
    path = root/name
    if path.is_symlink() or not path.is_file():
        raise SystemExit('BLOCKED: missing/symlink '+name)
    data = path.read_bytes()
    meta = manifest['files'][name]
    if hashlib.sha256(data).hexdigest() != meta['sha256'] or len(data) != meta['bytes']:
        raise SystemExit('BLOCKED: checksum '+name)
    doc = json.loads(data)
    if doc.get('database') != 'smartlo1_erp_app' or set(doc.get('tables',{})) != set(tables):
        raise SystemExit('BLOCKED: snapshot identity/scope '+name)
    for table, rows in doc['tables'].items():
        ids = [int(row['id']) for row in rows]
        if ids != sorted(set(ids)) or len(rows) != manifest['tables'][table]['count']:
            raise SystemExit('BLOCKED: row count/id '+table)
        verified += len(rows)
print('G500_SNAPSHOT_VERIFY=PASS')
print('TABLES_VERIFIED=17')
print('ROWS_VERIFIED='+str(verified))
print('SOURCE_DB_WRITES=0')
print('IMPORT_APPROVAL=NO')
