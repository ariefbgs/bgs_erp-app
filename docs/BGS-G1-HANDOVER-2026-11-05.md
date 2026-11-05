# ERP-BGS New — G1 handover

Date: 5 November 2026 (WIB).
Status: engineering verification PASS; Draft PR pending review. No merge or deployment authorized.

## Authoritative references

- Repository: ariefbgs/bgs_erp-app.
- Draft PR: https://github.com/ariefbgs/bgs_erp-app/pull/5
- Head branch: feature/bgs-g1-schema-foundation.
- Target: integration/laravel13-main-r1.
- Target SHA at verification: 69a671c37f6cf65e0f90acf59b5b13de56490c70.
- Tested application/workflow baseline: 0c093ef57ce1695b771aae1a94222b9e34a54fd4.
- This handover is documentation added after that tested baseline; it does not represent a new application test run.

## Completed scope

1. Eight schema foundation migrations for master data, quotations, customer/supplier POs, receipt/delivery, invoices, document receipts and support tables.
2. Delivery Order permission migration guard and subsequent permission synchronization.
3. Expenses completion: prior audit recorded 11/11 required columns and unique index PASS.
4. Delivery Order status enforcement: pending / delivered only. Retired or unexpected status rows block migration; no inferred legacy conversion. Unknown column metadata also blocks migration.
5. Migration tests for preservation of rows/metadata, repeatability and legacy-status rejection. Fixtures use uniquely named disposable databases.
6. Standard bootstrap uses Laravel migrations. The historical dump is retained unchanged as database/schema/mysql-schema.sql.reference, outside Laravel automatic schema loading. Blob SHA remains 714da972ee32e815a90fb31464a9749197de12d1.
7. CI checks no active mysql-schema.sql exists, runs migrations in a disposable MySQL service, checks 40 tables, then runs full regression and uploads evidence.
8. No UI/UX files changed in G1.

## Verified evidence — do not repeat without a new reason

| Gate | Actual evidence | Result |
| --- | --- | --- |
| Standard fresh bootstrap | CI 42040789977, MySQL 8.4 service, all migrations complete | PASS |
| Table inventory | CI log: IMPORTED_TABLES=40 | PASS |
| Full regression | OK (440 tests, 1254 assertions) | PASS |
| Job/workflow | Full PHPUnit Regression; completed / success | PASS |
| Evidence artifact | bgs-g1-regression-evidence, ID 13421940747 | Saved |

CI: https://github.com/ariefbgs/bgs_erp-app/actions/runs/42040789977
Artifact retention: workflow configured for 14 days. Preserve evidence before expiry if needed for later release approval. Never commit secrets, production dumps, customer files or raw operational evidence to the repository.

Earlier runs remain historical evidence:
- 42027040865: original regression PASS using imported SQL, not direct migration coverage.
- 42036434024: direct migrations PASS; two new temporary-fixture tests failed.
- 42037070150: fixture correction PASS, 440 tests / 1254 assertions.
- 42040789977: standard installation path and full regression PASS at the baseline above.

## Operational limits and rollback

- CI success proves an empty disposable database bootstrap and the regression suite. It does not prove upgrading an existing populated database.
- Foundation migrations create tables. Do not run them blindly against an existing legacy schema or mark migrations as run without reconciliation and an approved adoption plan.
- partial / shipped DO rows require a reviewed mapping or exception resolution before enforcement. Do not guess their target state.
- Delivery status enforcement deliberately refuses automatic down migration. Existing delivery_by normalization is also not automatically reversible. No generic migrate:rollback safety claim is made.
- Use a reviewed backup/restore or forward correction plan for any future runtime application. No runtime backup or restore was performed in this G1 work.
- No staging/production migration, legacy data conversion, merge, release tag or deployment occurred.

## Continuation

1. Keep PR #5 Draft and preserve the tested baseline. Do not rerun passed tests or change application code unless a concrete new finding requires it.
2. Obtain explicit direction before merge or deployment; repeated requests to continue do not cancel the existing prohibition.
3. Before any existing-database rollout, prepare schema/adoption reconciliation and rollback evidence for the exact target environment.
4. Legacy data migration remains a separate controlled ETL scope: read-only source -> migration staging -> mapping/transform -> validation -> load -> reconciliation -> UAT -> cutover. Use an ID mapping registry and exception register; never assume source IDs equal target IDs.
5. No G2 implementation scope has been established in this handover. Confirm its business outcome and gates before starting implementation.

Future release approval must trace CR -> branch/commit/PR -> automated verification/staging/UAT -> approved version/tag -> Deployment ID -> runtime verification -> closure. G1 engineering PASS is not go-live approval.
