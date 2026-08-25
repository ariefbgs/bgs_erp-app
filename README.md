# BGS ERP

BGS ERP adalah aplikasi Laravel untuk alur quotation, PO customer, pengadaan supplier, delivery order, invoice, dan deployment package internal.

## Persyaratan

- PHP 8.1 atau lebih baru
- Composer
- MySQL/MariaDB
- Node.js dan npm
- Ekstensi PHP yang dibutuhkan Laravel serta `zip`

## Instalasi lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Atur koneksi database di `.env`, lalu jalankan migration dan seeder sesuai prosedur deployment environment:

```bash
php artisan migrate --seed
php artisan serve
```

Jangan commit `.env`, dump database, ZIP aplikasi, private deployment package, atau backup lokal.

## Pengujian

Test database wajib terpisah dari database aplikasi. Konfigurasi project menggunakan nama database `erp_app_testing` dan test integrasi menolak `erp_app`.

```bash
php artisan test
```

Baseline verifikasi terakhir: 387 test lulus dengan 1041 assertion.

## Deployment Manager

Deployment Manager menyediakan:

- RBAC untuk view, upload, install, rollback, dan manage;
- upload ZIP ke private storage;
- validasi manifest, path, checksum, ZIP traversal, jumlah entry, dan ukuran ekstraksi;
- registrasi package ke `ApplicationRelease` dengan duplicate protection;
- verifikasi ulang checksum dan identitas manifest saat install;
- backup file, mutasi terkontrol, kompensasi filesystem, migration ledger, lifecycle state, serta history actor;
- cleanup staging setelah setiap attempt.

Endpoint rollback masih sengaja mengembalikan HTTP 501. Rollback tidak boleh diaktifkan sebelum tersedia ledger reversal lengkap untuk file dan migrasi database.

## Otoritas package

- Manifest: `release_id`, `version`, `scope`, `module`, `feature`, daftar file, dan daftar migration.
- Server: UUID release, nama/path fisik private package, SHA-256 persisted package, actor, dan lifecycle state.
- Database menyimpan snapshot identitas manifest dan harus tetap cocok saat instalasi.

## Dokumentasi

Konteks bisnis, invariant, perubahan database, dan catatan UAT tersedia di [handover project](docs/BGS_ERP_CODEX_HANDOVER_2026-08-25.md).
