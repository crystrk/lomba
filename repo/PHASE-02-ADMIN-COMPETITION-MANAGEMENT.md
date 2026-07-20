# Phase 02 — Pengelolaan Lomba oleh Admin

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6.1, 7.1–7.2, 8.1–8.2, 9.2–9.3, 13.2–13.4, 14.2, 15.1, serta AC-01 sampai AC-03.

Jika ada perilaku yang tidak dijelaskan dokumen ini, gunakan [PRD](./PRD.md). Perubahan format, aturan poin, atau lifecycle harus diperbarui di PRD sebelum diimplementasikan.

| Metadata        | Nilai                                                                                 |
| --------------- | ------------------------------------------------------------------------------------- |
| Status          | DONE                                                                                 |
| Prasyarat       | [Phase 01](./PHASE-01-FOUNDATION-ACCESS-DOMAIN.md) `DONE`                             |
| Hasil akhir     | Admin dapat mengelola draft lomba, peserta, dan operator assignment secara end-to-end |
| Fase berikutnya | [Phase 03](./PHASE-03-DRAW-MATCH-GENERATION-LOCKING.md)                               |

## Traceability PRD

| Referensi PRD                | Target fase                                                 |
| ---------------------------- | ----------------------------------------------------------- |
| FR-COMP-01 sampai FR-COMP-05 | CRUD lomba dengan guard status dan aturan poin.             |
| FR-COMP-06                   | Banyak operator dapat ditugaskan ke banyak lomba.           |
| FR-COMP-07                   | Dashboard menampilkan status dan progres dasar.             |
| FR-TEAM-01 sampai FR-TEAM-05 | CRUD peserta, nama unik case-insensitive, logo tervalidasi. |
| AC-01 sampai AC-03           | Format dan conditional scoring tervalidasi backend dan UI.  |
| PRD bagian 7                 | Draft/drawn dapat diedit; locked dan setelahnya dilindungi. |

## Batas Fase

### Termasuk

- dashboard admin dasar;
- daftar/filter lomba;
- create/show/edit/delete lomba draft/drawn;
- slug publik unik;
- conditional scoring rules;
- participant CRUD;
- logo opsional;
- operator assignment;
- guard perubahan berdasarkan status;
- UI Inertia/shadcn-vue dan Wayfinder;
- feature test otorisasi dan validasi.

### Tidak termasuk

- shuffle dan generation match;
- tombol lock yang aktif;
- skor pertandingan;
- klasemen;
- detail publik lomba.

UI boleh menampilkan langkah berikutnya sebagai disabled state, tetapi tidak boleh mensimulasikan hasil shuffle atau lock.

## Urutan Checkpoint

## C1 — CRUD lomba dan aturan format/poin

### Backend

- [x] Buat Form Request create/update dengan policy authorization.
- [x] Validasi nama, format, deskripsi, tanggal, dan slug.
- [x] Generate slug unik di backend; jangan mempercayai slug dari frontend.
- [x] Terapkan conditional validation poin:
    - knockout: ketiga field poin harus `null`/diabaikan;
    - full/half competition: ketiganya required integer dan boleh negatif.
- [x] Simpan status awal `draft` dari backend, bukan request.
- [x] Batasi update/delete pada `draft` atau `drawn`.
- [x] Setelah locked, hanya metadata non-struktural yang kelak boleh diubah; test guard sekarang.
- [x] Buat controller resource tipis dan query daftar dengan sort eksplisit.

### Test lebih dahulu

- [x] Admin dapat membuat setiap format.
- [x] Operator/guest tidak dapat create/update/delete.
- [x] Knockout tidak menyimpan poin aktif.
- [x] Format klasemen menolak poin kosong/non-integer dan menerima negatif.
- [x] Slug unik untuk nama sama/serupa.
- [x] Locked competition menolak perubahan struktural dan delete.

### Verifikasi

```bash
php artisan test --compact --filter="competition management"
```

## C2 — Halaman daftar, create, edit, dan show admin

### Frontend

- [x] Tambahkan named routes/controller routes admin.
- [x] Generate Wayfinder setelah route stabil.
- [x] Buat daftar lomba dengan status, format, jumlah peserta, dan progres dasar.
- [x] Buat form lomba menggunakan `useForm` sesuai interaksi.
- [x] Field poin muncul hanya untuk full/half competition.
- [x] Field poin dikosongkan ketika user mengganti format ke knockout.
- [x] Tampilkan error backend per field, processing state, dan toast sukses/gagal.
- [ ] Gunakan dialog konfirmasi untuk delete.
- [x] Jangan jadikan kondisi frontend satu-satunya guard status.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
php artisan test --compact --filter="competition pages"
```

### Debug checkpoint

- `422`: cocokkan nama input Vue dengan Form Request.
- Link salah: periksa import Wayfinder dan route model binding.
- Poin knockout tersimpan: normalisasi payload di backend, bukan hanya hide field.

## C3 — CRUD peserta dan invalidation contract

### Backend

- [x] Buat nested scoped routes `competition -> participant`.
- [x] Form Request memastikan participant benar-benar milik competition route.
- [x] Normalisasi nama secara konsisten untuk `normalized_name`.
- [x] Terapkan unique case-insensitive per competition.
- [x] Validasi `name`, `short_name`, dan logo.
- [x] Simpan file dengan generated filename pada disk public.
- [x] Hapus file lama setelah update/delete yang berhasil tanpa meninggalkan data DB inkonsisten.
- [x] Izinkan mutation pada `draft`/`drawn`, tolak pada locked dan setelahnya.
- [x] Siapkan satu action mutation participant yang kelak dapat memanggil invalidasi draw di Fase 3.

### Frontend

- [x] Daftar peserta memiliki empty state dan jumlah peserta.
- [x] Form tambah/edit menampilkan preview logo bila diperlukan.
- [ ] Delete menggunakan dialog konfirmasi.
- [x] Kontrol edit tidak ditampilkan jika policy melarang, tetapi backend tetap memeriksa.

### Test

- [x] Nama sama beda huruf/spasi ditolak dalam lomba yang sama.
- [x] Nama sama boleh digunakan pada lomba berbeda.
- [x] Upload invalid ditolak dan upload valid tersimpan menggunakan fake storage.
- [x] Nested resource dari lomba lain tidak dapat diakses.
- [x] Locked competition menolak seluruh mutation participant.

### Verifikasi

```bash
php artisan test --compact --filter="participant management"
npm run types:check
```

## C4 — Assignment operator

### Implementasi

- [x] Tampilkan hanya user role operator sebagai pilihan.
- [x] Admin dapat sync satu atau lebih operator.
- [x] Operator nonaktif boleh terlihat sebagai assignment lama tetapi tidak dapat dipilih untuk assignment baru.
- [x] Assignment dapat diubah meskipun competition sudah locked sesuai PRD.
- [x] Simpan `assigned_by` dan waktu assignment.
- [x] Cegah pivot duplikat.
- [x] Perbarui policy test agar assigned operator dapat view internal dan unassigned operator forbidden.
- [x] Buat UI multi-select/checklist yang dapat digunakan keyboard.

### Verifikasi

```bash
php artisan test --compact --filter="competition operator assignment"
php artisan test --compact --filter="competition policy"
npm run types:check
```

## C5 — Dashboard dan navigasi admin

### Implementasi

- [x] Dashboard menampilkan count lomba per status.
- [x] Daftar memiliki filter status/format dan pagination jika diperlukan.
- [x] Gunakan `withCount()`/aggregate agar tidak memuat relation penuh untuk count.
- [x] Navigasi admin menampilkan Lomba dan Operator.
- [ ] Breadcrumb dan judul halaman konsisten.
- [x] Empty state mengarahkan admin ke create competition.

### Verifikasi

```bash
php artisan test --compact --filter="admin dashboard"
npm run types:check
```

## Test Gate Fase 2

- [x] AC-01, AC-02, dan AC-03 lulus.
- [x] Seluruh FR-COMP dan FR-TEAM Must pada fase ini memiliki feature test.
- [x] Request nested participant tidak dapat menyeberang competition.
- [x] Operator assignment memengaruhi policy.
- [x] Structural mutation locked ditolak di backend.
- [x] Tidak ada URL backend hardcoded di halaman baru.
- [x] Query daftar tidak memiliki N+1 yang jelas.

Jalankan:

```bash
php artisan test --compact --filter="competition"
php artisan test --compact --filter="participant"
php artisan test --compact --filter="operator assignment"
vendor/bin/pint --dirty --format agent
npm run types:check
npm run lint:check
npm run format:check
```

## Exit Criteria

Fase 2 `DONE` jika admin dapat membuat lomba, mengisi poin, mengelola peserta, dan menugaskan operator melalui UI; seluruh guard juga terbukti melalui request langsung pada test.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
| 2026-07-20 | C1–C5 + Gate | FR-COMP-01–07, FR-TEAM-01–05, AC-01–03 | 124 tests, 348 assertions, 4.5s; build OK, format+typecheck OK | Lulus | Beberapa item UI (dialog konfirmasi, breadcrumb) deferred ke fase berikutnya |
