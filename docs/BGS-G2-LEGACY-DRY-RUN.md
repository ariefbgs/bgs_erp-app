# G2 — Legacy migration planning foundation

Scope: explicit field/dependency mapping, an in-memory ID registry, an exception register and snapshot reconciliation. No loader, migration ledger database, operational database access or UI change is included.

Base: G1 branch at b415c83; tested application baseline 0c093ef. G1 PR #5 remains separate and unmerged. This G2 work is not a deployment or approval to convert live data.

## Snapshot contract

Run `php artisan legacy:plan manifest.json source.json target.json` with three readable UTF-8 JSON files, at most 16 MiB each. The command prints JSON to stdout and does not write reports or mutate inputs. Keep snapshots and reports in a private evidence directory outside version control. Even reports contain identifiers and must not be committed. Input SHA-256 hashes bind a report to the exact three supplied files; they do not establish export freshness or provenance.

Source and target each contain a `tables` object; every scoped table must exist as a row list, including empty tables. Missing tables are rejected, not assumed empty. Unscoped source tables are listed and cause exit code 2. A partial manifest is never proof of full application coverage.

For input collection see [read-only snapshot collection](BGS-G2-SNAPSHOT-COLLECTION.md). Optional source `unexported_tables` metadata is preserved as an explicit scope gap; an exported table cannot simultaneously be listed as unexported. Credentials and operational exports are never committed.

Example manifest using synthetic source names, not a verified production mapping:

```json
{
  "version": 1,
  "entities": [
    {
      "name": "customers",
      "source_table": "old_customers",
      "target_table": "customers",
      "source_id": "id",
      "target_id": "id",
      "fields": {"customer_code": "code"},
      "key": ["customer_code"]
    },
    {
      "name": "orders",
      "source_table": "old_orders",
      "target_table": "po_customers",
      "source_id": "id",
      "target_id": "id",
      "fields": {"po_number": "number", "status": "status"},
      "key": ["po_number"],
      "allowed": {"status": ["received"]},
      "dependencies": {"customer_id": {"entity": "customers", "source_field": "customer_id", "nullable": false}}
    }
  ]
}
```

`fields` maps target column names to source column names. Dependencies reference earlier entities; forward references, cycles and copying into target identity columns are rejected. Nullable dependencies require the source column to exist and explicitly contain null. Keys and values use exact JSON scalar types and values: no trimming, case folding or fuzzy matching is inferred. Field coercion and monetary/date normalization require separately reviewed transformation rules and are not implemented here.

## Registry and classifications

Registry entries contain entity, source row position/ID, target ID, classification, reason and resolved/planned parent references. They omit business field payloads. A target ID is returned only for one exact business-key match whose mapped fields also agree. Source and target IDs are never presumed equal. Target identity duplicates or malformed target keys reject the snapshot.

| State | Meaning in this planner |
| --- | --- |
| AUTO-MAPPED | Deterministic field mapping; existing exact target match or new candidate with target ID null |
| TRANSFORMED | Explicit foreign-ID remapping or planned new-parent reference; no new ID is allocated |
| LEGACY-EXCEPTION | A mapped value falls outside manifest-approved allowed values |
| REQUIRES-REVIEW | Ambiguous key, missing/blocked parent, unresolved key, or target-field conflict |
| REJECTED/INVALID | Invalid/duplicate source identity or missing/invalid required mapped field |

New candidates have null target IDs. Parent references with null target IDs remain symbolic `(entity, source_id)` references; they are not loadable foreign keys. Child business keys requiring an unallocated parent ID require review. All source rows, including exceptions and duplicates, are counted once in reconciliation. No output claims load readiness; `load_implemented` is always false.

Exit codes: 0 = scoped planning completed without row exceptions or unscoped tables; 2 = review needed; 1 = invalid manifest/input. Exit 0 is not evidence that an import would succeed: only explicitly mapped fields and declared rules have been checked. Required target fields, all business rules and snapshot authenticity still need audited coverage.

## Domain dependency inventory for the next mapping review

Based on current target migrations and the approved migration sequence; actual legacy columns and business keys must be verified from a read-only export before a production manifest is approved.

| Stage | Domains / dependencies |
| --- | --- |
| Master | Company, Customer, Supplier, Product |
| Sales origin | Quotation + details -> Customer / Product |
| Customer order | PO Customer + details -> Customer / optional Quotation / Product; multiple POs per quotation are allowed |
| Procurement | PO Supplier + details -> one PO Customer, Supplier, Product, canonical PO Customer Detail |
| Receipt | Goods Receipt + details -> PO Supplier / PO Supplier Detail / Product |
| Invoice | Sales Invoice + details -> PO Customer / canonical detail; parent invoice dependencies must be ordered |
| Delivery | DO + details -> PO Customer / canonical detail / Product; allowed DO statuses pending / delivered |
| Documents | Document Receipt + pivots -> DO / Invoice; attachment/supporting-document mapping follows separately |

## Remaining gates

1. Obtain and verify read-only source and target exports, export time/scope and schema metadata. No operational snapshot has been accessed by this implementation.
2. Approve complete domain manifests, business keys, required fields, typed transformations and legacy exception resolutions. Unmapped fields/rules remain outside validation.
3. Reconcile actual snapshots and retain reports privately. Synthetic automated test success is not real-data reconciliation PASS.
4. Persistent ID registry, resumable loader, idempotency, rollback, monetary reconciliation and cutover/UAT are separate future scope. No source or target schema is weakened to accommodate invalid legacy data.
