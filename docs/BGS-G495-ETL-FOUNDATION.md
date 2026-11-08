# BGS G4.95 — ETL Master Foundation (candidate; NOT DEPLOYED)

Scope: company, customer, supplier, product master **only**. The legacy database remains read-only and is never connected to by this command. Input is a private, approved JSON snapshot exported separately under read-only access; **do not commit data snapshots or `.env` to GitHub**.

## Files
- Migration adds `legacy_migration_ids` and `legacy_migration_exceptions`, keeping migration pipeline separate from data ETL.
- `legacy:load-masters <snapshot>` performs rollback-only dry run, returns aggregate counts.
- `legacy:load-masters <snapshot> --apply` is **blocked outside `APP_ENV=testing`**. This batch is deliberately NOT a production loader.
- Any rejected row prevents commit of the entire batch, even when `--apply` is allowed in isolated testing.

## Snapshot contract

```json
{
  "database": "smartlo1_erp_app",
  "tables": {
    "companies": [{"id": 1, "company_code": "SYNTHETIC", "name": "Synthetic Ltd", "is_active": 1}],
    "customers": [], "suppliers": [], "products": []
  }
}
```

The example is synthetic, not a source record. Each row must supply fields needed by the actual target schema. No legacy PK is reused; the target assigns IDs, and `legacy_migration_ids` tracks the mapping. Conflicting natural keys, changed source hashes and missing attachments are not guessed and must be reviewed. An exception table is reserved for the later transactional loader; this command currently reports aggregate exceptions but does not persist individual exceptions.

## Validation gate (not yet run)

1. Create a temporary checkout from an exact GitHub SHA in a private VPS workspace.
2. Run PHP lint and targeted test `LegacyMasterImportSafetyTest` with verified isolated `APP_ENV=testing` and MySQL test database. No production tests with `RefreshDatabase`.
3. Run a synthetic dry-run and a test-only apply; check ID mappings and idempotency, then discard the disposable DB.
4. Only after evidence PASS: extend this batch to transactional loaders with explicit exception handling and formal production import approval. Do not migrate this registry to production or run this command there yet.

Known unresolved exceptions: orphan `invoice_customer_details.id=30` references missing invoice id=6; `document_receipts.id=2` refers to missing invoice number. Both remain quarantined/review; no candidate invoices are auto-chosen. Source-only tables (`customers2`, `products6`, `products_6`, `invoice_customers_backup`) and DPP need separate classification, not automatic inclusion.
