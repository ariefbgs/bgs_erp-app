# ERP-BGS New — G5.13 Raw snapshot delta comparator

**Purpose:** detect added, changed, and missing source records across two independent G5.00 *raw* read-only snapshots. This is not a production sync engine.

- Source database is still authoritative until approved final cutover.
- Run only on private copies (e.g. Jagoan under `/home/smartlo1/bgs-legacy-audit-g480/private-snapshots`). No DB connection, no SQL writes.
- First perform G500 export a SECOND time only when a useful delta window exists (before cutover or when data changes). Do not repeatedly export merely to check readiness.
- Usage: `umask 077; python3 compare-delta-g513.py /path/g500-baseline /path/g500-new /path/g513-output-new`.
- Private `delta-private.json` contains business data: never paste into chat or commit to GitHub. `delta-manifest.json` has aggregates only.
- Changed rows require verified transformation and target ID registry updates; missing rows are **not** deletion approval. Invoices, payment records, and tables outside the 17-table scope need separate treatment.
- G505's 1-row exception quarantine does not alter the immutable G500 baseline. Source `invoice_customer_details.id=30` stays explicitly quarantined and must not be imported blindly from either raw snapshot.
- This script is a candidate to commit to GitHub; no code is deployed or applied to target. Test with `python3 -m unittest discover -s bgs-g513 -p 'test-*.py'`.
- The next major gate remains actual-data import against isolated MySQL test, reconciliation, attachments, payment/other domain scope, and release safety. Not a shortcut to go-live.
