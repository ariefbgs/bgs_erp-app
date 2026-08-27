# BGS ERP - CODEX HANDOVER MODERNISASI

**Tanggal:** 27 Agustus 2026

**Repository lokal:** `D:\projects\bgs_erp-app`

**Branch modernisasi:** `codex/upgrade-laravel-13`
**GitHub:** `https://github.com/ariefbgs/bgs_erp-app`

## Status saat ini

Modernisasi lokal dari Laravel 10 ke Laravel 13 sudah diterapkan bertahap dan lulus regression test pada setiap tahap. Alur bisnis ERP tidak diubah dan tidak ada field database wajib baru yang dibuat.

Versi utama:

- PHP lokal: 8.4.24
- Laravel Framework: 13.29.0
- PHPUnit: 12.5.33
- Yajra DataTables: 13.x
- Vite: 8.2.2
- Bootstrap: 5.3.8
- Node.js lokal: 24.18.0

Verifikasi terakhir:

```text
397 tests passed
1086 assertions
Composer security advisories: 0
npm vulnerabilities: 0
Production frontend build: PASS
```

## Isolasi database

- database aplikasi asal: `erp_app` — tidak diubah;
- staging lama: `erp_app_staging` — tidak diubah oleh modernisasi;
- automated test: `erp_app_testing` — wajib untuk semua test;
- staging modernisasi: `erp_app_staging_upgrade` — salinan terisolasi untuk UAT Laravel 13.

Live preview modernisasi dijalankan dengan environment override, bukan dengan mengubah `.env`:

```text
http://127.0.0.1:8082/login
DB_DATABASE=erp_app_staging_upgrade
```

## Penyesuaian modernisasi

- constraint PHP dinaikkan menjadi `^8.3` sesuai Laravel 13;
- Laravel, Tinker, PHPUnit, dan Yajra DataTables dinaikkan ke generasi kompatibel;
- middleware CSRF memakai basis `PreventRequestForgery` Laravel 13;
- cache menolak deserialisasi object secara default;
- session memakai serialisasi JSON; pengguna perlu login ulang satu kali setelah deployment;
- metadata test PHPUnit lama dikonversi menjadi PHP attributes;
- file lama `InvoiceCustomerController copy.php` tetap dipertahankan, tetapi dikeluarkan dari Composer classmap agar tidak berkonflik;
- frontend dinaikkan ke Vite 8 dan paket stabil terbaru yang kompatibel.

Peringatan deprecation Sass pada build berasal terutama dari SCSS internal Bootstrap 5.3.8. Build berhasil. Jangan melakukan rewrite styling secara spekulatif hanya untuk menghilangkan warning tersebut.

## Keamanan dan kontrak yang tetap hijau

- ZIP traversal/zip-slip protection;
- batas jumlah entry dan uncompressed size;
- SHA-256 package dan payload;
- private package storage;
- RBAC Deployment Manager;
- duplicate release protection;
- metadata/package tamper detection;
- canonical `ApplicationRelease` registration;
- rollback/failure tetap fail-closed;
- Upload ke ApplicationRelease serta install melalui HTTP boundary.

## Perubahan lama yang wajib dipertahankan

Empat deletion di `audit-output/BGS-REM-R1A*.txt` dan folder untracked `output/` sudah ada sebelum modernisasi. Jangan restore, hapus, atau commit sebagai bagian modernisasi tanpa keputusan eksplisit user. Backup SQL staging juga harus tetap lokal dan diabaikan Git.

## Langkah berikutnya

1. UAT singkat melalui live preview Laravel 13 di port 8082.
2. Pastikan server tujuan menyediakan PHP 8.3/8.4 beserta extension yang dibutuhkan sebelum deployment.
3. Backup database dan konfigurasi server sebelum staging online.
4. Push branch modernisasi ke GitHub setelah user menyetujui UAT lokal.
5. Jangan merge ke `main` atau deploy ke VPS tanpa persetujuan eksplisit user.

## Prompt untuk chat baru

> Lanjutkan BGS ERP dari `docs/BGS_ERP_CODEX_HANDOVER_2026-08-27.md` di `D:\projects\bgs_erp-app`. Pertahankan seluruh perubahan lama yang belum di-commit. Branch modernisasi adalah `codex/upgrade-laravel-13`; Laravel 13.29.0, Vite 8.2.2, dan full suite 397 test/1086 assertion sudah hijau. Automated test wajib memakai `erp_app_testing`; UAT modernisasi memakai `erp_app_staging_upgrade`; jangan menyentuh `erp_app`. Pertahankan ZIP security, checksum, private storage, RBAC, duplicate protection, dan canonical Upload → ApplicationRelease. Mulai dengan inspeksi read-only status/diff dan jangan merge/deploy tanpa persetujuan user.
