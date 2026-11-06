# ERP-BGS New — Hostinger production candidate

## Decision and present gate

Prepare one new production installation, independently of legacy. No permanent staging instance is required on the VPS. CI uses disposable services; initial runtime verification will be access-restricted on the new installation before business use. This document does not authorize merging, tagging, deployment or opening transactions.

G1 PR #5 and G2 PR #6 retain their PASS evidence and remain Draft. This readiness change stacks on G2 commit b4b0c30400c53e292fedb2d616d9650b16b5af3b. Production readiness is still BLOCKED until the target environment and opening-data decision below are known. A successful candidate build is not an operational PASS.

## Inputs still required

- Confirmed Hostinger SSH host/port/account and existing secure access method; no passwords in Git or chat.
- New application domain, installation root, FPM PHP 8.4 binary/socket, web-service account and web server type. Use a separate vhost/FPM configuration; do not change global PHP or other projects.
- New MySQL 8.4 database and restricted application account, distinct from legacy; confirm CLI and FPM both meet locked Composer requirements.
- Choose opening with empty transactional data plus approved real masters/opening balances, or controlled import of legacy data. Installing an empty schema does not decide this business question.
- Approved initial admin identity; password is entered privately in a terminal. Confirm timezone and mail requirements; the current application timezone remains UTC.

## Candidate build and contents

GitHub Actions `hostinger-candidate.yml` runs targeted production-access/bootstrap tests, Vite build, locked production Composer install/platform checks, cache compilation and a production preflight against disposable MySQL. It publishes a tar.gz, SHA256SUMS and readiness evidence. No SSH step, remote secret or deployment action exists in this workflow.

`release.json` identifies source SHA, candidate version 0.1.0-rc.1 and workflow run. This version is a proposal, not an approved release/tag. `release-files.json` records payload hashes. The archive includes runtime code, locked vendor dependencies and built assets. It excludes .env, cached environment configuration, logs, uploads, reference schema dump, public/test.php and historical public/biaya files. Neither Node nor Composer installation is needed on the VPS for this artifact; `composer check-platform-reqs --no-dev` remains a useful host check when Composer is available.

## Installation sequence after deployment approval

1. Resolve the PR chain through review, fix the exact approved commit and semantic version/tag, and retain CI artifacts outside Git before their 14-day expiry. Link the Change Request, PRs, tag, source SHA and unique Deployment ID. Do not deploy a moving branch or retag a different commit as the tested release.
2. Configure the separate HTTPS vhost with document root `<root>/current/public`. Deny access to dotfiles, runtime/private files and non-public application directories. Restrict initial access to authorized verification users. Confirm DNS, certificate, PHP-FPM and disk capacity without altering legacy.
3. Verify SHA256SUMS and source SHA, inspect the archive, and extract to a new, empty `<root>/releases/<source-sha>`. Do not extract over a live release. Keep `<root>/shared/.env` and `<root>/shared/storage` outside the public root. Symlink the release .env and storage to these shared locations. Preserve legacy-style public upload directories (including `public/biaya`) separately under shared and link them into releases if required; do not overwrite or copy legacy files automatically.
4. Copy `.env.hostinger.example` privately into shared .env; replace every placeholder, supply a new APP_KEY and separate DB credentials, set mode 600 and correct owner. Do not rotate the key on subsequent releases. Set `BGS_RESTRICTED_FULL_USER_IDS=""` only for the new identity space. This prevents newly assigned user 4 from inheriting the legacy special-access rule. Existing installations retain the default ID policy until reviewed.
5. Verify the **exact new database identity and zero existing tables** with the dedicated DB account before first migration. Stop on any mismatch or nonempty schema. Never run foundation migrations against legacy, use migrate:fresh, fake the ledger, or import the reference dump. Then run the approved PHP 8.4 binary with `artisan migrate --force --no-interaction` in this release.
6. Run only `artisan db:seed --class=DeploymentPermissionSeeder --force --no-interaction`. Do not run DatabaseSeeder or UserRoleSeeder: they contain example business records/default-password accounts. No demo records belong in production.
7. Run `artisan bgs:bootstrap-admin 'approved-email' 'Approved Name'` in a private interactive terminal. It accepts hidden password and confirmation prompts (minimum 8 characters); there is no password argument/default. It refuses existing users, noninteractive execution, overlapping bootstrap or a reserved legacy ID. It creates one admin only, and does not reset accounts. Public GET/POST /register are absent; later users are managed by authorized admins.
8. Assign write permission only to shared storage, needed upload directories and bootstrap/cache. Create public/storage using `artisan storage:link`; keep code and vendor non-writable by PHP where practical. Verify configured shared paths and backup inclusion before accepting uploads.
9. Run `artisan config:cache`, `artisan route:cache`, `artisan view:cache`, then `artisan bgs:production-check <approved-source-sha>`. Exit 1 = blocked; exit 0 = local checks pass, **not go-live approval**. The command reports booleans and source identity, performs no database mutations, and does not expose connection errors or secrets. Check both CLI and FPM; CLI PASS does not prove FPM configuration.
10. Atomically select the release via current only after pre-activation checks. Verify HTTPS login, admin access, role restrictions, assets, storage/upload persistence and relevant transaction/print flows. Record actual outcomes; do not substitute historical UAT for the new runtime. Keep entry restricted until the approved opening-data scenario and these checks pass.
11. Configure the scheduler using the exact PHP binary/user and absolute current path. The current scheduled DO task is hourly; an hourly `artisan schedule:run` entry is sufficient for the present schedule. Revisit if new jobs are added. Verify timezone, logs and one controlled run against the intended database. Do not schedule CI/test/legacy paths.
12. Record backup/restore verification, release identity, Deployment ID, runtime validation and closure before normal business use. Mail/password-reset delivery and approved real master data must be verified where used.

## Storage, backup and rollback

Retain one active and one previous release. Shared .env, uploads, storage and database outlive releases. Keep bounded encrypted/off-host backups with an explicitly chosen retention policy; avoid redundant application copies and production dumps on the VPS. Storage savings must not remove the current recovery point or legacy before cutover is complete.

Before changing an existing runtime, retain an independently verified DB/file backup and a restore plan for the exact source SHA. Test restore into a disposable database only when needed; remove that temporary environment after verification. A first empty installation has no previous application release; recovery before opening is to withdraw the new vhost/current and correct the failed install without touching legacy.

After opening transactions, switching current back only rolls back code. It does not reverse data, uploads, migrations or sent documents. Do not use generic migrate:rollback: G1 includes irreversible enforcement. Choose compatible forward correction or a reviewed restore/reconciliation plan; account for transactions since backup. Never silently discard new transactions.

## Data gate

Empty start requires approved real company/customer/supplier/product masters, numbering, roles, opening balances and first-transaction checks. Legacy import requires source identity/freshness, reviewed mapping, persistent ID registry, exception resolution, idempotent load, reconciliation and UAT. G2 currently plans snapshots only; it is not a loader. Historical audits and synthetic CI cannot close these real-data gates.

## Completion record

Record exact candidate CI run, payload SHA-256, approved source/tag, target runtime inventory, actual preflight output, backup/restore evidence, UAT/opening decision and Deployment ID outside Git where operational details are confidential. Until these exist, report engineering/build readiness separately from VPS readiness and go-live readiness.
