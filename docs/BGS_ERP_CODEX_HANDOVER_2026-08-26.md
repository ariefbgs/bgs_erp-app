# BGS ERP - CODEX HANDOVER

**Tanggal handover:** 26 Agustus 2026

**Repository kerja:** `D:\projects\bgs_erp-app`

**Staging laptop:** `D:\projects\bgs_erp-app-staging`

**GitHub:** `https://github.com/ariefbgs/bgs_erp-app`

**Branch aktif:** `codex/bgs-erp-finalization`

**HEAD saat handover:** `0aa1e38` (`fix: align Vite with Laravel plugin for staging build`)

**Status:** pekerjaan terbaru sudah diterapkan dan diuji di staging, tetapi masih ada perubahan yang belum di-commit.

---

## 1. Instruksi wajib untuk chat/Codex berikutnya

1. Jangan menghapus, me-reset, atau menimpa perubahan yang belum di-commit.
2. Mulai dengan inspeksi read-only:
   - `git status --short`
   - `git diff --stat`
   - branch, HEAD, dan remote
   - dokumen handover ini
3. Repository aktual adalah sumber kebenaran implementasi. Handover ini adalah konteks dan titik lanjut.
4. Database harus tetap terisolasi:
   - aplikasi asal/local: `erp_app`
   - staging laptop: `erp_app_staging`
   - automated test: `erp_app_testing`
5. Jangan pernah menjalankan test terhadap `erp_app` atau `erp_app_staging`.
6. Jangan mengubah `erp_app` ketika hanya diperlukan verifikasi/read-only. Perubahan UAT dilakukan pada `erp_app_staging`.
7. Gunakan perubahan paling kecil dan aman. Jangan menambah field wajib secara spekulatif.
8. Pertahankan keamanan Deployment Manager: ZIP security, checksum, private storage, RBAC, duplicate protection, serta fail-closed behavior.

---

## 2. Kondisi Git saat handover

Remote:

```text
origin https://github.com/ariefbgs/bgs_erp-app.git
```

Perubahan belum di-commit yang terdeteksi:

```text
M  app/Http/Controllers/DeliveryOrderController.php
M  app/Http/Controllers/GoodsReceiptController.php
M  app/Http/Controllers/InvoiceCustomerController.php
M  app/Http/Controllers/PoCustomerController.php
M  app/Models/PoCustomer.php
M  resources/views/delivery_orders/create.blade.php
M  resources/views/goods_receipts/edit.blade.php
M  resources/views/invoice_customers/create.blade.php
M  resources/views/invoice_customers/print.blade.php
M  resources/views/invoice_customers/print_invoice.blade.php
M  resources/views/invoice_customers/show.blade.php
M  tests/Feature/BgsInvoiceAggregateStateRegressionTest.php
?? database/migrations/2026_08_25_220000_add_remaining_amount_to_po_customers_table.php
?? tests/Feature/BgsFulfillmentInvoiceWorkflowContractTest.php
?? output/
```

Empat file evidence lama juga berstatus deleted dan harus dipertahankan apa adanya sampai user memutuskan commit:

```text
D audit-output/BGS-REM-R1A1-safety-blocker-resolution.txt
D audit-output/BGS-REM-R1A2-test-isolation-repository-foundation.txt
D audit-output/BGS-REM-R1A3-secure-git-initial-baseline.txt
D audit-output/BGS-REM-R1A4-initial-baseline-commit-finalization.txt
```

Status di atas adalah snapshot. Selalu ulangi `git status --short` karena dapat berubah setelah handover dibuat.

---

## 3. Environment dan cara menjalankan

### 3.1 Database

```text
D:\projects\bgs_erp-app\.env             -> erp_app
D:\projects\bgs_erp-app\.env.testing     -> erp_app_testing
D:\projects\bgs_erp-app-staging\.env     -> erp_app_staging
```

Semua memakai MySQL lokal `127.0.0.1:3306`. Jangan menaruh password atau secret di handover/chat.

### 3.2 Staging laptop

URL yang dipakai untuk UAT:

```text
http://127.0.0.1:8081
```

Jika live preview mati:

1. Pastikan MySQL hidup.
2. Jalankan dari `D:\projects\bgs_erp-app-staging`:

```powershell
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8081
```

3. Pastikan database yang terbaca adalah `erp_app_staging`, bukan `erp_app`.

Setelah perubahan Blade:

```powershell
php artisan view:clear
php artisan view:cache
```

### 3.3 Test

Jalankan dari repository kerja:

```powershell
php artisan test
```

Test memiliki safety guard `APP_ENV=testing` dan database `erp_app_testing`.

---

## 4. Deployment Manager - sudah selesai, jangan diulang sebagai RED lama

Handover 25 Agustus menyatakan Upload belum mendaftarkan `ApplicationRelease`. Kondisi itu sudah tidak berlaku.

Integrasi minimal dan aman Upload -> ApplicationRelease sekarang sudah selesai. Test berikut hijau:

```text
tests/Feature/Deployment/DeploymentUploadRegistrationIntegrationTest.php
```

Coverage penting yang sudah ada:

- successful upload registers canonical `ApplicationRelease`;
- duplicate upload gagal tertutup tanpa orphan package;
- invalid package dibersihkan setelah secure processing gagal;
- release terdaftar dapat di-install melalui HTTP boundary;
- ZIP yang berubah setelah registration ditolak;
- metadata terdaftar yang berubah ditolak;
- migration tervalidasi dieksekusi melalui runtime binding.

Otoritas canonical yang dipertahankan:

- manifest/package memiliki identitas dan metadata release;
- private persistence memiliki stored package + SHA-256;
- `ApplicationRelease` adalah record canonical database;
- package tidak ditempatkan di public storage;
- install memverifikasi checksum/metadata kembali.

Jangan melemahkan alur ini ketika mengerjakan modul ERP lain.

---

## 5. Alur bisnis UAT yang sedang dikerjakan

Alur yang telah dicoba user:

1. Customer - PASS
2. Supplier - PASS
3. Product - PASS
4. Quotation - PASS
5. PO Customer - PASS
6. PO Supplier partial quantity - PASS
7. Remaining quantity dan status - PASS
8. Goods Receipt - PASS setelah perbaikan dropdown/back button
9. Delivery Order - PASS setelah perbaikan syarat GR/deliverable quantity
10. Sales Invoice partial - PASS setelah koreksi formula dan validasi
11. Print PDF/Print Invoice - dikoreksi dan diverifikasi

Flow bisnis yang disepakati:

```text
Quotation
-> PO Customer
-> PO Supplier (dapat partial)
-> Goods Receipt berdasarkan PO Supplier
-> Delivery Order berdasarkan barang yang sudah diterima
-> Sales Invoice dapat diterbitkan sebelum/atau setelah delivery sesuai payment terms
```

Sales Invoice dapat dibuat untuk DP maupun payment after delivery. Invoice tidak menjadi syarat wajib pembuatan DO pada implementasi saat ini.

---

## 6. Keputusan canonical Sales Invoice partial

### 6.1 Dasar alokasi

Alokasi invoice dihitung dari **PO Amount sebelum diskon dan pajak**, yaitu `po_customers.subtotal`.

```text
Remaining PO Amount = PO subtotal - total allocation seluruh invoice aktif
```

Allocation invoice aktif:

```text
Jika dp_amount > 0 -> gunakan dp_amount
Jika tidak          -> gunakan invoice subtotal
```

Invoice berstatus `cancelled` tidak ikut mengurangi remaining.

### 6.2 Contoh UAT canonical

```text
PO Amount                Rp 78.750.000
Invoice 1 / DP           Rp 19.687.500
Remaining                Rp 59.062.500
Recommended percentage   75%
Invoice 2 Payment Amount Rp 59.062.500
```

Diskon, PPN, dan PPh dihitung setelah Payment Amount ditentukan. Validasi `melebihi sisa` membandingkan Payment Amount dengan remaining PO Amount sebelum pajak menggunakan pembulatan rupiah yang sama seperti UI.

### 6.3 Parent invoice

- User tidak memilih parent secara manual.
- Invoice aktif pertama untuk PO Customer menjadi root/parent.
- Invoice kedua dan seterusnya otomatis menunjuk invoice root yang sama.
- Tidak ada pertanyaan “invoice mana menjadi parent” pada invoice ketiga dan seterusnya.

### 6.4 Rekomendasi UX berikutnya

Keputusan UX yang direkomendasikan, tetapi belum seluruhnya diimplementasikan:

- Payment Amount sebaiknya input utama.
- Payment Percentage dihitung otomatis sebagai informasi.
- Jika salah satu diubah, nilai lainnya tersinkronisasi.
- Validasi utama tetap Amount <= remaining PO sebelum pajak.

Saat ini form masih menggunakan `dp_percent` sebagai input dan menampilkan nilai rupiah hasil hitung di kolom kanan. Angka `75` berarti 75%, bukan Rp75 juta.

### 6.5 Bug PPh yang sudah diperbaiki

Sebelumnya PPh hasil derivasi dapat menjadi `2.500000668...`, ditolak browser karena step `0.5`, dan Save tidak pernah mencapai server. Sekarang:

- persentase PPh dibulatkan empat desimal di server;
- checkbox PPh dan baris PPh konsisten;
- UAT form menunjukkan nilai valid `2.5`;
- invoice kedua dapat disimpan.

---

## 7. Delivery Order - syarat aktual

PO Customer muncul pada Create DO apabila:

1. PO Customer tidak `cancelled`.
2. Memiliki PO Supplier aktif/tidak `cancelled`.
3. Memiliki Goods Receipt aktif/tidak `cancelled`.
4. Minimal satu detail GR memiliki `quantity_received > 0`.
5. Masih ada deliverable quantity.

Rumus:

```text
Deliverable quantity per product
= total quantity Goods Receipt aktif
- total quantity Delivery Order yang sudah dibuat
```

Saat menyimpan:

- PO Customer wajib;
- delivery date wajib;
- minimal satu item quantity > 0;
- quantity DO tidak boleh melebihi deliverable quantity;
- nomor DO dibuat otomatis;
- attachment opsional: JPG/JPEG/PNG/PDF, maksimum 5 MB;
- invoice number dan tax invoice number saat ini opsional.

Catatan teknis: perhitungan `delivered` saat ini menjumlahkan semua DO yang tersimpan. Sebelum menambah lifecycle cancel DO di masa depan, audit apakah DO cancelled harus dikecualikan secara eksplisit.

---

## 8. Goods Receipt

Perbaikan yang sudah diterapkan:

- dropdown PO Supplier tidak lagi dibatasi hanya status `confirmed`;
- PO Supplier cancelled dikecualikan;
- remaining/received quantity tetap menjadi guard;
- tombol Back pada Edit Goods Receipt kembali ke index Goods Receipt, bukan tujuan yang salah.

Goods Receipt aktif adalah prasyarat quantity untuk DO.

---

## 9. Print Sales Invoice

Dua route/template cetak memiliki perilaku yang sengaja berbeda:

### Print PDF (`invoice_customers.print`)

- logo perusahaan tampil;
- data perusahaan canonical tampil;
- memakai image signature;
- signature dipilih berdasarkan nilai invoice (`ttd_inv` / `ttd_inv2`);
- remaining amount tidak dicetak.

### Print Invoice (`invoice_customers.print_invoice`)

- logo perusahaan tampil;
- data perusahaan canonical tampil;
- **tidak memakai image signature**;
- menyediakan ruang kosong, garis, dan nama PIC untuk tanda tangan manual;
- remaining amount tidak dicetak.

DomPDF menerima logo/signature sebagai base64 data URI. Path image dibatasi agar tetap berada di bawah `public_path`, sehingga path traversal dari metadata database tidak diterima.

Fallback asset yang digunakan bila path database tidak tersedia:

```text
public/uploads/companies/logo/LogoBGS.png
public/uploads/ttd/logo baru BGS-FInal.png
public/uploads/ttd/ttd_inv.jpg
public/uploads/ttd/ttd_inv2.jpg
```

Data company staging sebelumnya dummy/tidak lengkap. Record aktif `erp_app_staging.companies` telah disinkronkan dari record aktif `erp_app.companies` secara read-only terhadap database asal. Ini adalah data-fix staging manual, bukan migration production.

File hasil inspeksi visual berada di `output/pdf/`. Folder ini adalah artifact QA dan masih untracked saat handover.

---

## 10. Schema/data change terbaru

Migration baru yang belum di-commit:

```text
database/migrations/2026_08_25_220000_add_remaining_amount_to_po_customers_table.php
```

Tujuan:

- menambah `po_customers.remaining_amount` (`decimal(15,2)`);
- menyimpan remaining invoice canonical pada header PO Customer;
- model `PoCustomer` telah menambahkan fillable/cast dan sinkronisasi status invoice.

Sebelum deployment production:

1. Review migration.
2. Backup database.
3. Jalankan migration sesuai prosedur deployment.
4. Tentukan apakah existing PO memerlukan backfill eksplisit.
5. Jangan mengandalkan data-fix staging company sebagai pengganti migration/seed production.

---

## 11. Test dan bukti verifikasi terakhir

Full suite terakhir setelah seluruh implementasi staging dan koreksi Print Invoice tanpa signature image:

```text
397 passed
1086 assertions
```

Targeted fulfillment suite:

```text
tests/Feature/BgsFulfillmentInvoiceWorkflowContractTest.php
10 passed
45 assertions
```

Full suite sudah dijalankan kembali setelah koreksi terakhir dan semuanya hijau.

### Checkpoint staging complete - 26 Agustus 2026

- seluruh 12 file implementasi/migration yang relevan memiliki SHA-256 identik antara repository kerja dan clone staging;
- database staging terverifikasi `erp_app_staging`;
- migration `2026_08_25_220000_add_remaining_amount_to_po_customers_table` sudah `Ran` (batch 27);
- MySQL UAT memakai MySQL 8.4 Laragon dengan data directory `C:\laragon\data\mysql-8-daebaek`;
- live preview aktif di `http://127.0.0.1:8081`;
- invoice PO `YYYY`: 25% + 75%, remaining 0, status completed, parent otomatis benar;
- invoice PO `xxxx`: 30% + 30% + 40%, remaining 0, status completed, semua invoice lanjutan menunjuk root pertama;
- Create DO read-only menampilkan PO `xxxx` dengan deliverable Laptop 1 dan PO `YYYY` dengan deliverable Laptop 5;
- belum ada record Delivery Order tersimpan saat checkpoint; jangan menganggap UAT read-only ini membuat DO baru.

Targeted test penting:

```text
tests/Feature/BgsFulfillmentInvoiceWorkflowContractTest.php
tests/Feature/BgsInvoiceAggregateStateRegressionTest.php
tests/Feature/BgsInvoiceAggregateMutationRegressionTest.php
tests/Feature/BgsInvoiceCustomerRuntimeAtomicityTest.php
tests/Feature/BgsInvoiceCustomerTransactionBoundaryTest.php
tests/Feature/Deployment/DeploymentUploadRegistrationIntegrationTest.php
```

---

## 12. Langkah lanjutan yang disarankan

1. Inspeksi semua diff yang belum di-commit dan pastikan tidak ada perubahan user yang terlewat.
2. Jalankan full test suite lagi.
3. UAT invoice kedua yang sudah tersimpan:
   - Show
   - Print PDF dengan signature image
   - Print Invoice tanpa signature image
4. UAT DO partial kedua untuk memastikan `GR - DO sebelumnya` benar.
5. Pertimbangkan implementasi UX Payment Amount sebagai input utama dengan percentage dua arah.
6. Review migration `remaining_amount`, termasuk strategi backfill.
7. Putuskan apakah artifact `output/` masuk `.gitignore` atau tidak sebelum commit.
8. Buat commit terstruktur tanpa menghapus perubahan lama.
9. Push branch `codex/bgs-erp-finalization` ke GitHub setelah user menyetujui hasil UAT.

Jangan langsung merge ke `main` tanpa konfirmasi user.

---

## 13. Prompt siap pakai untuk chat baru

Salin prompt berikut ke chat/Codex baru:

> Lanjutkan project ERP BGS dari handover `docs/BGS_ERP_CODEX_HANDOVER_2026-08-26.md` di repository `D:\projects\bgs_erp-app`. Pertahankan seluruh perubahan yang belum di-commit dan jangan reset/delete pekerjaan lama. Mulai dengan inspeksi read-only terhadap handover, `git status --short`, diff, branch/HEAD/remote, serta konfigurasi isolasi database. Database asal adalah `erp_app`, staging laptop `erp_app_staging`, dan automated test wajib `erp_app_testing`. Deployment Upload -> ApplicationRelease sudah selesai dan test integrasinya hijau; jangan kembali menganggapnya sebagai RED lama. Status terakhir adalah UAT fulfillment: partial Sales Invoice menggunakan PO subtotal sebelum pajak sebagai basis allocation, parent invoice ditentukan otomatis, DO dibatasi Goods Receipt aktif dikurangi DO sebelumnya, Print PDF memakai signature image, sedangkan Print Invoice tidak memakai signature image. Jalankan targeted test yang relevan lalu full suite sebelum commit. Pertahankan ZIP security, checksum, private storage, RBAC, duplicate protection, serta semua perubahan user yang belum di-commit. Jangan membuat field wajib atau mengubah business rule secara spekulatif.

---

## 14. Definisi hasil akhir project

Target project adalah ERP BGS yang dapat menjalankan alur order-to-fulfillment secara konsisten:

```text
Master Data
-> Quotation
-> PO Customer
-> PO Supplier partial/full
-> Goods Receipt
-> Delivery Order partial/full
-> Proforma/Sales Invoice partial/full
-> customer-facing PDF/print
```

Setiap dokumen harus menjaga linkage, remaining quantity/amount, lifecycle status, keamanan edit/delete/cancel, auditability, dan konsistensi downstream. Deployment Manager menyediakan mekanisme update aplikasi yang private, tervalidasi, checksum-protected, RBAC-controlled, dan fail-closed.
