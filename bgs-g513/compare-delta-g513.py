#!/usr/bin/env python3
"""G5.13: compare two immutable G5.00 raw snapshots, never connect to DB.

Output is an aggregate manifest (no customer data) and a PRIVATE delta payload.
All three groups must have matching identity and exact table scope.
This does not apply any changes to ERP New; it is a cutover preparation aid.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import sys

GROUPS = {
    'masters': ('companies', 'customers', 'suppliers', 'products'),
    'orders': ('quotations', 'quotation_details', 'po_customers', 'po_customer_details', 'po_suppliers', 'po_supplier_details'),
    'fulfilment': ('goods_receipts', 'goods_receipt_details', 'invoice_customers', 'invoice_customer_details', 'delivery_orders', 'delivery_order_details', 'document_receipts'),
}
SOURCE = 'smartlo1_erp_app'

def read_snapshot(root):
    result = {}
    manifest_path = root / 'manifest.json'
    if manifest_path.is_file():
        manifest = json.loads(manifest_path.read_text())
        if manifest.get('source') != SOURCE or manifest.get('report') != 'BGS_G500_READ_ONLY_SOURCE_SNAPSHOT':
            raise ValueError('G500 manifest identity mismatch')
    else:
        raise ValueError('Raw G500 manifest missing; refuse candidate or partial snapshots')
    for group, expected in GROUPS.items():
        path = root / (group + '.json')
        if path.is_symlink() or not path.is_file():
            raise ValueError('Missing or symlink snapshot group: ' + group)
        blob = path.read_bytes()
        meta = manifest['files'][group + '.json']
        if hashlib.sha256(blob).hexdigest() != meta['sha256'] or len(blob) != meta['bytes']:
            raise ValueError('G500 checksum mismatch: ' + group)
        doc = json.loads(blob)
        if doc.get('database') != SOURCE or set(doc.get('tables', {})) != set(expected):
            raise ValueError('Group scope mismatch: ' + group)
        for table in expected:
            rows = doc['tables'][table]
            if not isinstance(rows, list):
                raise ValueError('Invalid table rows: ' + table)
            indexed = {}
            for row in rows:
                if not isinstance(row, dict) or not str(row.get('id', '')).isdigit() or int(row['id']) < 1:
                    raise ValueError('Invalid row identity: ' + table)
                rid = str(int(row['id']))
                if rid in indexed:
                    raise ValueError('Duplicate source id: ' + table)
                indexed[rid] = row
            if len(rows) != manifest['tables'][table]['count']:
                raise ValueError('Manifest row-count mismatch: ' + table)
            result[table] = indexed
    return result

def digest(row):
    # Canonical serialization avoids reporting false changes due to field order.
    return hashlib.sha256(json.dumps(row, ensure_ascii=False, sort_keys=True, separators=(',', ':'), default=str).encode('utf-8')).hexdigest()

def compare(old, new):
    aggregate = {}
    delta = {'source_database': SOURCE, 'mode': 'DELTA_CANDIDATE_ONLY', 'tables': {}}
    totals = {'created': 0, 'updated': 0, 'missing_from_new_snapshot': 0, 'unchanged': 0}
    for table in old:
        before, after = old[table], new[table]
        created = sorted(set(after) - set(before), key=int)
        removed = sorted(set(before) - set(after), key=int)
        common = set(before) & set(after)
        changed = sorted((x for x in common if digest(before[x]) != digest(after[x])), key=int)
        unchanged = len(common) - len(changed)
        aggregate[table] = {'created': len(created), 'updated': len(changed), 'missing_from_new_snapshot': len(removed), 'unchanged': unchanged}
        for k, v in aggregate[table].items():
            totals[k] += v
        delta['tables'][table] = {
            'created': [after[x] for x in created],
            'updated': [{'before_hash': digest(before[x]), 'after': after[x]} for x in changed],
            'missing_source_ids': removed,
        }
    return aggregate, totals, delta

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('baseline', type=Path, help='G500 RAW snapshot directory (not g505 candidate)')
    ap.add_argument('latest', type=Path, help='New G500 RAW snapshot directory')
    ap.add_argument('output', type=Path, help='New private output directory')
    args = ap.parse_args()
    if os.name != 'posix':
        raise ValueError('Private POSIX filesystem required')
    baseline, latest = args.baseline.resolve(strict=True), args.latest.resolve(strict=True)
    if baseline == latest:
        raise ValueError('Must compare different snapshot directories')
    if args.output.exists() or args.output.is_symlink():
        raise ValueError('Output exists; refusing overwrite')
    # Ownership and file permissions are intentionally left to caller's private umask.
    old, new = read_snapshot(baseline), read_snapshot(latest)
    aggregate, totals, delta = compare(old, new)
    args.output.mkdir(mode=0o700, parents=False)
    payload = args.output / 'delta-private.json'
    payload.write_text(json.dumps(delta, ensure_ascii=False, separators=(',', ':'), default=str))
    payload.chmod(0o600)
    report = {
        'report': 'BGS_G513_DELTA_MANIFEST',
        'baseline': baseline.name,
        'latest': latest.name,
        'source': SOURCE,
        'tables': aggregate,
        'totals': totals,
        'delta_payload_sha256': hashlib.sha256(payload.read_bytes()).hexdigest(),
        'missing_records_policy': 'HOLD_FOR_REVIEW_NO_AUTOMATIC_DELETE',
        'updates_policy': 'HOLD_UNTIL_TARGET_ID_REGISTRY_AND_TRANSFORM_VERIFIED',
        'known_exceptions': [{'table': 'invoice_customer_details', 'source_id': 30, 'decision': 'QUARANTINE'}],
        'source_database_writes': 0,
        'target_database_writes': 0,
        'production_import_approved': False,
    }
    manifest = args.output / 'delta-manifest.json'
    manifest.write_text(json.dumps(report, indent=2))
    manifest.chmod(0o600)
    print('G513_DELTA_COMPARE=PASS')
    print('TABLES=' + str(len(old)))
    for key, val in totals.items():
        print(key.upper() + '=' + str(val))
    print('MISSING_POLICY=NO_AUTOMATIC_DELETE')
    print('DATABASE_WRITES=0')
    print('PRODUCTION_IMPORT_APPROVED=NO')
    print('PRIVATE_OUTPUT=' + str(args.output))

if __name__ == '__main__':
    try:
        main()
    except Exception as exc:
        print('G513_DELTA_COMPARE=BLOCKED ' + type(exc).__name__ + ': ' + str(exc), file=sys.stderr)
        sys.exit(1)
