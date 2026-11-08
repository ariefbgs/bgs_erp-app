# G4.98 — Transaction ETL Phase A (Quotations / Customer PO / Supplier PO)

**Candidate, not deployed, not approved for operational import.** Source branch `feature/bgs-g4-legacy-etl-r1` baseline commit `8e6b0de`. Do not run ETL on production.

Adds `legacy:load-orders <private-json> [--apply]`. Six tables in parent-before-child order: quotations, quotation_details, po_customers, po_customer_details, po_suppliers, po_supplier_details. All foreign IDs resolve only via `legacy_migration_ids` from earlier imported masters; legacy IDs are never reused. One quotation may have multiple customer POs. Supplier PO detail lineage resolves via the *single* matching customer PO detail with same product; 0 or 2+ candidates fail closed. Do not guess ambiguous relations.

**Execution rule:** `--apply` allowed ONLY with `APP_ENV=testing`, SQLite `:memory:`. Ordinary invocation is a transactional dry-run and rolls back. Production loader remains deliberately disabled until end-to-end reconciliation and separate release approval. Production source and legacy are untouched.

**Snapshot input** (private, do not commit): JSON object `{"database":"smartlo1_erp_app","tables":{...}}` containing exactly six table arrays. Each row contains legacy `id`, required source columns for the corresponding target table, and legacy FK values (e.g. `customer_id`, `quotation_id`, `po_customer_id`, `supplier_id`, `product_id`, `po_supplier_id`). Source extraction must be read-only and performed separately; this batch does not connect to legacy hosting. Private attachments cannot be loaded until file migration is verified.

**Current limitation:** This phase covers only first six transaction tables and synthetic tests. It is **not** a complete legacy ETL. Remaining goods receipts, invoice customers/details, delivery orders/details, document receipt pivots, payment data, storage/attachments, per-domain transform rules, alternate tables and reconciliation are still required. Two known legacy exceptions remain in register. Do not confuse PHPUnit PASS with real data reconciliation PASS.

Run only targeted new test using an explicit temporary PHPUnit XML with SQLite `:memory:` and PHP8.4. Existing G4.96/G4.97 tests already PASS on earlier baseline; do not repeat without relevant change. After test PASS, commit both PHP files and this doc on the same branch. Do not deploy.
