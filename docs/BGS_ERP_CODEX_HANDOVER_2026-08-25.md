# ERP BGS — CODEX DEVELOPMENT HANDOVER
**Handover date:** 25 August 2026  
**Repository:** `D:\projects\bgs_erp-app`  
**Primary evidence folder:** `D:\projects\bgs_erp-app\audit-output`  
**Immediate continuation point:** Deployment Manager Foundation — `FND-003Z3T1C5D3O0` completed; next work must resolve manifest/registration authority before implementing Upload → ApplicationRelease registration.

---

## 1. PURPOSE OF THIS HANDOVER

This document is the operational handover for continuing ERP BGS development in Codex. It consolidates the business baseline, UI/UX rules, engineering discipline, deployment-manager state, completed evidence, known defects/risks, and the exact next decision point.

Codex should treat the repository as the source of truth for current code and should verify any detail that can drift by inspecting the repository and `audit-output`. Do not redesign established business rules or UI patterns without an explicit Change Request from the user.

The user expects a senior full-stack / ERP engineering workflow: inspect first, change minimally, protect business invariants, test, produce evidence, then request manual functional confirmation where appropriate.

---

## 2. PROJECT IDENTITY AND OPERATING MODEL

ERP BGS belongs to PT Bagas Gemilang Satwika (BGS). The application is an order-driven ERP. The audited transaction flow is:

`Quotation → PO Customer → PO Supplier → Goods Receipt → Delivery Order → Sales Invoice / Proforma`

Important domain constraints:

- Procurement is **PO-for-order**, not procurement for warehouse stock.
- There is **no inventory/warehouse subsystem requirement** in the target flow.
- There is **no Purchase Return or Sales Return subsystem requirement**.
- Cross-document linkage, remaining quantity, amount carry-forward, lifecycle status, edit/delete/cancel protection, and downstream consistency are critical invariants.
- A PO Customer may be created **without a Quotation**.
- Database/schema changes are allowed when justified, but every migration/index/constraint/seed/data-fix must be documented for final deployment.

---

## 3. AUTHORITATIVE BUSINESS LIFECYCLE

### 3.1 Quotation
Canonical states:
- Draft — initial state.
- Approved — after explicit approval.
- Sent — only when an **Approved** quotation is printed/sent.
- Received PO Customer — when PO Customer is created from the quotation.

Rules:
- Printing a Draft quotation must **not** change it to Sent.
- Normal edit must preserve lifecycle state.
- Quotation can be edited until related Sales Invoice reaches `Paid`, subject to downstream protections.
- Create Quotation includes **Copy from previous quotation**:
  - user chooses an older quotation;
  - relevant header/detail data is loaded as a new draft;
  - the new quotation receives a new quotation number;
  - source number/status/downstream identity must never be reused or mutated.
- Copy feature was UAT-tested successfully; qty × unit price and unit-price formatting were corrected.

### 3.2 PO Customer
Canonical main states:
- Received PO
- Proceed
- Delivered
- Cancel

Separate procurement state:
- pending
- partial
- fully_procured

Rules:
- PO Customer can exist without Quotation.
- Creation of PO Supplier advances procurement status appropriately.
- Delivery Order advances main status to Delivered according to existing business logic.
- Edit is allowed until Sales Invoice is Paid, subject to downstream protection.
- Delete/cancel must respect downstream dependencies.

### 3.3 PO Supplier
Canonical states:
- Draft
- Approved
- Sent
- Receipt (after Goods Receipt)

Hard business rules:
- One PO Supplier can originate from **one PO Customer only**. Never mix multiple PO Customers into one PO Supplier.
- Create PO Supplier customer dropdown must show only customers that still have at least one eligible PO Customer:
  - PO Customer is not cancelled; and
  - at least one detail still has remaining procurement quantity.
- User may select only some remaining PO Customer detail rows for a PO Supplier.
- Unselected rows may later be procured using another PO Supplier and/or another supplier.
- UI pattern: remaining PO Customer detail rows are selectable by checkbox; quantity is editable but cannot exceed remaining quantity; Save sends only selected rows.
- Remaining Qty must be visible during selection.
- Purchase-price difference from product master must trigger an explicit decision:
  - Keep Existing
  - Update Master
  - Cancel
  Never silently update master price.
- Edit PO Supplier:
  - editable: Qty, Purchase Price, Discount, Add Product (only eligible remaining product), Remarks;
  - read-only: Supplier, PO number, date, status, VAT;
  - editing is blocked once a Goods Receipt exists.
- Add Product cannot allocate beyond eligible/remaining quantity.
- Discount fields `discount_percent` and `discount_amount` were added and recalculation was UAT-tested.
- PO Supplier Index/Show and PDF print were aligned and tested; logo/signature/layout fixes were completed. Multipage PDF still deserves a dummy-data test if not subsequently done.

### 3.4 Goods Receipt
- Initial/canonical state: Receipt.
- Goods Receipt is a downstream lock for PO Supplier edit.
- Flow is order-based, not warehouse-stock based.

### 3.5 Delivery Order
- Drives PO Customer toward Delivered.
- Quantity/linkage must remain consistent with upstream PO Customer and downstream invoice flow.

### 3.6 Sales Invoice / Proforma
Remaining amount rule:
- internally calculate/store:
  `PO Customer Amount − cumulative active Sales Invoice Amount`
- `remaining_amount` is an internal control value.
- It must **not be printed** on the customer-facing Sales Invoice / Invoice Customer printout.

Additional requirement:
- Delivery cost exists in Quotation and Sales Invoice where defined by current implementation/baseline.

---

## 4. UI/UX BASELINE — DO NOT REDESIGN CASUALLY

The **PO Customer module** is the locked baseline for transaction-module UI/UX and coding style, including Index, Create, Show, and Edit.

Other transaction modules should follow it for:
- page/layout structure;
- card/header structure;
- spacing;
- form and filter pattern;
- table architecture;
- typography;
- alerts;
- status badges;
- pagination;
- action button size/spacing;
- sticky/frozen action column;
- general visual consistency.

Module-specific fields and business rules remain intact.

Known baseline details:
- Index pagination: maximum 8 rows/page.
- PO Customer columns Customer and PO # were widened.
- Attachment has its own column next to PO #:
  - attachment exists → icon;
  - missing → red `Belum Ada` badge.
- Quotation and PO Customer action column: frozen/sticky on the **left**.
- PO Supplier action column: frozen/sticky on the **right**.
- Action buttons are standardized across transaction indexes.
- PO Supplier Index/Show was already aligned to this baseline.

Do not “modernize” the UI by introducing a new design language unless explicitly requested.

---

## 5. ENGINEERING AND EVIDENCE DISCIPLINE

Repository:
`D:\projects\bgs_erp-app`

Evidence:
`D:\projects\bgs_erp-app\audit-output`

Expected working style:
1. Inspect current code and evidence.
2. Establish/confirm the invariant.
3. Prefer RED contract/regression proof before production fix when practical.
4. Make the smallest safe production change.
5. Run targeted tests.
6. Run related regression.
7. Run full regression before closure of a meaningful foundation step.
8. Record evidence in `audit-output`.
9. Keep development and testing databases isolated.
10. Do not mutate production/dev DB while a step is explicitly read-only.
11. Do not classify a failure as valid RED if it is caused by syntax, bootstrap, environment, fixture, or test-harness errors.
12. Where UI/export behavior is affected, manual functional/UAT confirmation remains important.

Testing database safety used in recent work:
- Testing DB: `erp_app_testing`
- Development DB: `erp_app`
- They must remain different.

A previous full-regression closure reached:
- **336 passed**
- **790 assertions**
- duration around **6.75 s**
at that checkpoint. This is a historical checkpoint, not a substitute for rerunning the current suite after new changes.

PowerShell note:
- PHP test files generated with Windows PowerShell `Set-Content -Encoding utf8` may receive UTF-8 BOM.
- This already caused `Namespace declaration statement has to be the very first statement`.
- For generated PHP, write UTF-8 **without BOM**.
- Some captured PHPUnit output also contained NUL/UTF-16-like characters, which broke regex classifiers even though the semantic failure was visible. Do not trust a classifier blindly; inspect raw test output.

---

## 6. DEPLOYMENT MANAGER — CURRENT FOUNDATION STATE

The current workstream is the Deployment Manager/Foundation, with step IDs under `FND-003...`.

Already established in the repository/evidence:
- deployment permissions exist:
  - `deployment_view`
  - `deployment_upload`
  - `deployment_install`
  - `deployment_rollback`
  - `deployment_manage`
- canonical permission seeding exists.
- deployment runtime composition/container bindings have been regression-tested.
- persistent end-to-end deployment tests exist.
- private deployment-package persistence exists.
- secure ZIP extraction/manifest processing and deployment orchestration foundations exist.
- persistent database tables/repositories exist for releases/history/files/migrations.
- `ApplicationRelease` is the canonical persisted release record.

Relevant current production files include:
- `app\Http\Controllers\DeploymentController.php`
- `app\Services\Deployment\DeploymentPackagePersistenceInterface.php`
- `app\Services\Deployment\LocalDeploymentPackagePersistence.php`
- `app\Services\Deployment\DeploymentPackageManifest.php`
- `app\Services\Deployment\DeploymentPackageProcessor.php`
- `app\Services\Deployment\DeploymentPackageValidator.php`
- `app\Services\Deployment\DeploymentPackageExtractor.php`
- `app\Services\Deployment\DeploymentPackageRegistrationService.php`
- `app\Models\ApplicationRelease.php`
- deployment repositories/context/orchestrator classes under `app\Services\Deployment\`
- migrations beginning around `2026_08_24_021057_create_application_releases_table.php` and related deployment tables.

Codex must inspect the actual tree before editing because this handover is not a substitute for current repository contents.

---

## 7. MOST RECENT PROVEN PROBLEM: UPLOAD DOES NOT REGISTER APPLICATION RELEASE

### 7.1 Current controller behavior

At the latest recon, `DeploymentController` constructor receives only:

`DeploymentPackagePersistenceInterface $packagePersistence`

and `upload()` does:

1. `$this->packagePersistence->persist($request->file('package'));`
2. returns HTTP 204.

It does **not** register an `ApplicationRelease`.

### 7.2 Semantic RED proof

`tests\Feature\Deployment\DeploymentUploadRegistrationIntegrationTest.php` was introduced to prove:

> A successful upload must register exactly one canonical ApplicationRelease.

Initial run was invalid because the test file had UTF-8 BOM. After BOM repair:
- database isolation PASS;
- PHP syntax PASS;
- targeted test actually ran;
- HTTP upload path succeeded far enough to reach the assertion;
- `application_releases` count remained 0 instead of becoming 1;
- test failed at the intended integration invariant;
- production source/database were not mutated by the RED step.

Therefore the semantic RED is valid:
**successful upload currently persists the package but does not create the canonical release registration.**

Do not delete this test. Adapt it only if the canonical manifest contract is intentionally changed and the test must be brought into alignment.

---

## 8. LATEST RECON: FND-003Z3T1C5D3O0 — MANIFEST AUTHORITY

`FND-003Z3T1C5D3O0` completed read-only with:
- `RECON_COMPLETE=YES`
- `PRODUCTION_SOURCE_MUTATION=NONE`
- `PRODUCTION_DATABASE_MUTATION=NONE`
- `FND_003Z3T1C5D3O0_RESULT=RECON_COMPLETE`

### 8.1 Current `DeploymentPackageManifest` contract

The manifest value object currently carries:
- `release_id`
- `version`
- `scope`
- optional `module`
- optional `feature`
- `files`
- `migrations`

It does **not** carry:
- `release_uuid`
- `name`
- `previous_version`
- `release_notes`

### 8.2 Current package persistence contract

`DeploymentPackagePersistenceInterface::persist(UploadedFile $package)` returns:
- `path`
- `filename`
- `sha256`

`LocalDeploymentPackagePersistence`:
- generates a random UUID-based stored filename (`<uuid>.zip`);
- moves the uploaded ZIP into configured private package storage;
- calculates SHA-256;
- returns the generated stored filename, path, and lowercase SHA-256.

Important mismatch:
`DeploymentPackageRegistrationService` expects `original_filename`, but persistence currently returns the **generated stored filename** as `filename`. The original client upload name is available from the `UploadedFile` before/while persisting but is not part of the current persistence result.

### 8.3 Current registration service contract

`DeploymentPackageRegistrationService::register(array $package)` requires non-empty strings:
- `release_uuid`
- `release_id`
- `name`
- `scope`
- `version`
- `original_filename`
- `sha256`

It also supports optional:
- `module`
- `feature`
- `previous_version`
- `release_notes`
- `created_by`

Validation includes:
- `release_uuid` must be a valid UUID;
- scope ∈ `application`, `module`, `feature`, `hotfix`;
- SHA-256 must be 64 lowercase/hex-compatible chars;
- max lengths for release_id/name/version/original filename/module/feature.

Registration:
- checks duplicate by `release_uuid` OR `release_id`;
- inserts `ApplicationRelease`;
- sets canonical status `uploaded`;
- stores `package_filename = original_filename`;
- stores `package_sha256`;
- returns a success result containing the DB release ID;
- duplicate/persistence failures are fail-closed result objects.

### 8.4 Authority gap discovered

Repository search showed `release_uuid` authority only in registration/tests/schema; it is not currently part of `DeploymentPackageManifest`.

Repository search showed `name` for deployment release only in registration/tests/schema; it is not currently part of `DeploymentPackageManifest`.

Existing processor/orchestrator tests construct manifests using the established minimal shape:
- `release_id`
- `version`
- `scope`
- `files`
- `migrations`
(and optional module/feature where relevant)

Therefore **do not immediately patch the controller by inventing `release_uuid` and `name`**. First reconcile the canonical package/manifest contract.

---

## 9. EXACT NEXT ENGINEERING DECISION

The next task should be treated as the continuation of `FND-003Z3T1C5D3O` (or a narrowly scoped sub-step if Codex prefers).

Before implementing GREEN, decide and contract-test the source of these registration fields:

| Registration field | Current proven source | Gap |
|---|---|---|
| release_id | manifest | none |
| version | manifest | none |
| scope | manifest | none |
| module | manifest optional | none |
| feature | manifest optional | none |
| sha256 | package persistence | none |
| original_filename | UploadedFile/client name | not propagated by persistence result |
| release_uuid | no canonical manifest authority found | unresolved |
| name | no canonical manifest authority found | unresolved |
| previous_version | registration optional only | optional/unresolved |
| release_notes | registration optional only | optional/unresolved |
| created_by | authenticated request/user could supply | optional; confirm intended policy |

### Preferred engineering direction to evaluate — NOT yet frozen

Codex should inspect existing deployment architecture and tests and choose the smallest coherent contract. Two broad possibilities:

**A. Package identity belongs in manifest**
- extend manifest schema/value object/validator to include `release_uuid` and `name` (and optionally metadata);
- update package producers/tests/fixtures;
- upload processor reads canonical manifest;
- registration consumes manifest identity + upload metadata.

**B. Server assigns selected metadata**
- server generates `release_uuid`;
- server derives or supplies `name` under a formally defined rule;
- manifest remains minimal.

Do not choose B merely because it is easy. The earlier engineering direction explicitly avoided speculative/random identity generation until authority was proven. If server-generated UUID is selected, document why UUID is server identity rather than package identity and add tests for idempotency/duplicate behavior. If manifest identity is selected, update validation and package contract consistently.

A likely clean pipeline is:

`UploadedFile`
→ capture original client filename
→ persist private ZIP and calculate SHA-256
→ process/read/validate manifest
→ construct canonical registration DTO/array
→ `DeploymentPackageRegistrationService`
→ `ApplicationRelease(status=uploaded)`
→ return successful HTTP response

But inspect existing processor behavior carefully before wiring this to avoid duplicate extraction/validation or bypassing security boundaries.

---

## 10. CRITICAL FAILURE/ATOMICITY QUESTIONS CODEX MUST ADDRESS

When implementing Upload → Registration, explicitly define/test:

1. **Invalid manifest after persistence**
   - Is persisted ZIP deleted on failure?
   - Or retained as quarantined/unregistered artifact?
   - Avoid orphaned package files without an intentional policy.

2. **Registration failure after persistence**
   - duplicate release identity;
   - DB failure;
   - validation failure.
   Decide cleanup/retention behavior.

3. **Duplicate upload**
   - release_id and release_uuid duplicate handling must be deterministic.
   - Never create two canonical releases for the same identity.

4. **Original filename vs stored filename**
   - DB field is currently named `package_filename` but registration writes `original_filename`.
   - Persistence uses a generated UUID filename.
   Verify whether DB is intended to store original display filename, stored filename, or whether schema needs both. Do not silently conflate them.

5. **SHA-256**
   - must correspond to the exact persisted ZIP used later for install.
   - Installation should be able to verify package integrity against registered checksum.

6. **Authentication / created_by**
   - confirm whether upload should persist authenticated user ID.
   - Do not assume if current authorization architecture has a different authority.

7. **HTTP failure semantics**
   - current success is 204.
   - define safe response on invalid package, duplicate identity, and persistence/registration failure without leaking internals.

8. **Transaction boundary**
   - DB transaction cannot atomically roll back filesystem move.
   - use compensating cleanup or explicit staged/quarantine semantics.

9. **Security**
   - do not weaken ZIP security, path traversal protection, manifest validation, checksum validation, permission middleware, or private storage rules while integrating registration.

---

## 11. RECOMMENDED CODEX STARTUP CHECKLIST

When Codex opens the repository:

1. `cd D:\projects\bgs_erp-app`
2. Read repository guidance (`AGENTS.md`, README, project docs if present).
3. `git status --short`
4. Record current branch and HEAD.
5. Do **not** discard uncommitted user work.
6. Inspect latest evidence under `audit-output`, especially filenames containing:
   - `C4L4`
   - `C5D3M`
   - `C5D3N`
   - `C5D3N1`
   - `C5D3O0`
7. Inspect current deployment production files and tests listed in this handover.
8. Confirm `.env.testing` / PHPUnit still resolves `erp_app_testing`, not `erp_app`.
9. Run the targeted upload-registration test and confirm current RED before changing production code.
10. Run relevant existing deployment tests to establish current baseline.
11. Decide/contract the missing manifest/registration identity authority.
12. Implement the smallest coherent GREEN.
13. Run targeted tests, deployment regression, then full suite.
14. Write evidence to `audit-output`.
15. Summarize production files changed, DB/schema changes (if any), tests, and any manual UAT required.

---

## 12. TESTS OF PARTICULAR INTEREST

At minimum inspect/run the current equivalents of:

- `tests\Feature\Deployment\DeploymentUploadRegistrationIntegrationTest.php`
- `tests\Feature\Deployment\DeploymentPackageRegistrationPersistenceTest.php`
- `tests\Feature\Deployment\DeploymentPersistentEndToEndTest.php`
- `tests\Feature\Deployment\DeploymentPermissionProvisioningContractTest.php`
- `tests\Feature\Deployment\DeploymentRuntimeCompositionContractTest.php`
- `tests\Unit\DeploymentPackageContractTest.php`
- `tests\Unit\DeploymentPackageProcessorContractTest.php`
- `tests\Unit\DeploymentPackageSecurityContractTest.php`
- `tests\Unit\DeploymentPackageZipSecurityContractTest.php`
- `tests\Unit\Deployment\DeploymentOrchestratorBehaviorTest.php`
- deployment execution-context, release-state, migration-state and history repository tests.

Do not assume filenames are unchanged; locate equivalents if the repository has moved them.

---

## 13. COMPLETED ERP BUSINESS WORK THAT MUST NOT REGRESS

The following areas had already received implementation/UAT attention and must be protected by regression:

- Quotation copy-from-previous.
- Quotation lifecycle Draft → Approved → Sent rules.
- PO Customer status/procurement-status behavior.
- PO Customer UI baseline.
- PO Supplier eligible-customer and remaining-procurement selection.
- Partial line procurement across suppliers/PO Suppliers.
- One PO Supplier ↔ one PO Customer invariant.
- Purchase-price decision Keep Existing / Update Master / Cancel.
- PO Supplier edit restrictions and GR lock.
- PO Supplier add-product remaining allocation validation.
- PO Supplier discount/recalculation.
- PO Supplier Index/Show/PDF alignment.
- Attachment 404 fixes.
- Duplicate PO Customer number fixes.
- Sales Invoice internal remaining_amount not printed.
- Pagination and action-column UI conventions.

Any full regression failure in these areas is a release blocker unless proven unrelated and explicitly accepted.

---

## 14. DATABASE / DEPLOYMENT DOCUMENTATION OBLIGATION

The user explicitly requested that all DB changes be collected for final implementation/deployment. Codex must maintain a cumulative record of:
- new/changed tables;
- columns;
- indexes;
- foreign keys;
- unique constraints;
- migrations;
- seeds/permissions;
- data fixes/backfills;
- one-off SQL, if any;
- deployment order and rollback considerations.

Never make an ad-hoc production DB edit that exists only in a terminal history.

---

## 15. CHANGE CONTROL

Treat established ERP BGS rules in this handover as baseline. If repository code conflicts with a stated baseline:
- do not silently redefine the business rule;
- identify the mismatch;
- determine whether it is an implementation defect, an obsolete artifact, or a later approved change;
- ask the user when the correct authority cannot be established.

Large redesigns, new subsystems, or changed business semantics should be handled as explicit Change Requests.

---

## 16. CURRENT HANDOVER STATUS

**Last completed evidence step:**  
`FND-003Z3T1C5D3O0 — Manifest Authority Recon`

**Result:**  
`RECON_COMPLETE`

**Current blocker:**  
Upload persistence and release registration contracts are mismatched:
- manifest lacks `release_uuid` and `name`;
- registration requires them;
- original upload filename is not part of persistence result;
- controller only persists and returns 204.

**Next objective:**  
Resolve the canonical identity/metadata authority, then make the existing semantic RED test GREEN through a minimal, secure, regression-safe Upload → Registration integration.

**Do not start by blindly injecting `DeploymentPackageRegistrationService` into the controller and fabricating required fields.**

---

## 17. HANDOVER PROMPT FOR CODEX

Use the following as the working instruction:

> Continue ERP BGS from the existing repository at `D:\projects\bgs_erp-app`. Treat the current repository and `audit-output` evidence as authoritative implementation state, and this handover as the consolidated business/engineering baseline. Do not discard uncommitted work. First inspect `AGENTS.md`, git status, the latest deployment evidence, current DeploymentController, package persistence/processor/manifest/validator/registration services, ApplicationRelease schema/model, and deployment tests. Reconfirm testing DB isolation (`erp_app_testing` vs `erp_app`) and reproduce the semantic RED in `DeploymentUploadRegistrationIntegrationTest`. The latest completed step is `FND-003Z3T1C5D3O0`, which proved that the manifest currently owns release_id/version/scope/module/feature/files/migrations, persistence owns path/generated filename/SHA-256, while registration additionally requires release_uuid/name/original_filename. Do not fabricate those fields. Determine and test the canonical authority first, then implement the smallest secure Upload → ApplicationRelease registration GREEN. Preserve ZIP security, private storage, permission/RBAC, duplicate protection, checksum integrity, and all existing ERP business/UI baselines. Run targeted tests, deployment regression, then full regression; save evidence under `audit-output`; document every source/schema/seed/data change and any manual UAT needed.

---

## 18. SOURCE NOTE

The most recent technical facts in Sections 6–10 are grounded in the supplied `FND-003Z3T1C5D3O0` recon evidence dated 25 August 2026. Older business/progress items are consolidated project baseline/context and should be verified against repository/evidence when they affect a new code change.
