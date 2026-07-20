# Phase 05 — Halaman Publik dan Hardening MVP

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6.3, 8.5, 9.6, 14.1, 15, 16, 18.3, AC-15, presentasi AC-16, dan Tahap 5 bagian 19.

Fase ini tidak boleh menambah fitur di luar [ruang lingkup MVP PRD](./PRD.md#5-ruang-lingkup). Fokusnya adalah mengekspos data yang sudah benar, menyelesaikan UX, dan menguji aplikasi secara menyeluruh.

| Metadata        | Nilai                                                                 |
| --------------- | --------------------------------------------------------------------- |
| Status          | NOT STARTED                                                           |
| Prasyarat       | [Phase 04](./PHASE-04-SCORING-STANDINGS-PROGRESSION.md) `DONE`        |
| Hasil akhir     | Public pages lengkap, aplikasi responsif/aman, seluruh MVP gate hijau |
| Fase berikutnya | Rilis MVP                                                             |

## Traceability PRD

| Referensi PRD              | Target fase                                                           |
| -------------------------- | --------------------------------------------------------------------- |
| FR-PUB-01 sampai FR-PUB-03 | Landing aktif dan detail slug tanpa login.                            |
| FR-PUB-04                  | Bracket publik menampilkan babak, score, dan winner.                  |
| FR-PUB-05 sampai FR-PUB-06 | Standings, rules, round matches, dan pending state.                   |
| FR-PUB-07                  | Completed tetap dapat dibuka langsung.                                |
| FR-PUB-08                  | Landing memiliki empty state.                                         |
| AC-15                      | Locked/in-progress tampil dan dapat dibuka guest.                     |
| AC-16                      | Completed hilang dari landing aktif tetapi URL detail tetap tersedia. |
| PRD bagian 15–16           | UI responsif, aksesibel, aman, dan cukup cepat.                       |

## Batas Fase

### Termasuk

- landing publik;
- detail publik berbasis slug;
- bracket publik;
- standings dan daftar match publik;
- final polish internal UI yang diperlukan PRD;
- audit otorisasi, query, aksesibilitas, dan responsivitas;
- smoke/e2e verification yang tersedia;
- final CI dan production build.

### Tidak termasuk

- WebSocket/live events;
- archive listing completed;
- export/import;
- configurable tie-breaker;
- unlock/reset bracket;
- dependency baru tanpa persetujuan.

## Catatan Browser Test

PRD meminta browser test, tetapi starter kit saat dokumen ini dibuat belum memasang `pestphp/pest-plugin-browser`. Jangan menambah dependency tanpa persetujuan. Pada awal fase:

1. cek kembali dependency yang tersedia;
2. jika plugin tersedia, buat browser test sesuai checkpoint;
3. jika belum, minta persetujuan dependency atau dokumentasikan manual browser verification sebagai gap sebelum rilis.

Feature test, typecheck, lint, dan build tetap wajib dan tidak bergantung pada keputusan plugin browser.

## Urutan Checkpoint

## C1 — Query dan route publik

### Backend

- [ ] Ubah route `/` menjadi landing yang mengambil competition aktif saja: `locked` dan `in_progress`.
- [ ] Tambahkan named route detail `/lomba/{competition:slug}`.
- [ ] Detail mengizinkan locked, in-progress, dan completed.
- [ ] Draft/drawn tidak boleh diekspos guest; pilih 404 untuk menghindari metadata leak.
- [ ] Eager load/select hanya data yang diperlukan.
- [ ] Landing menghitung participant count dan progress tanpa N+1.
- [ ] Detail memilih presenter/prop shape berdasarkan format di backend.
- [ ] Jangan kirim email operator, internal IDs yang tidak perlu, atau metadata admin.

### Test

- [ ] Guest dapat mengakses landing.
- [ ] Landing hanya berisi active competition.
- [ ] Completed tidak muncul di landing.
- [ ] Completed dapat dibuka melalui slug.
- [ ] Draft/drawn menghasilkan not found untuk guest.
- [ ] Slug tidak valid menghasilkan not found.

### Verifikasi

```bash
php artisan test --compact --filter="public competition"
```

## C2 — Landing page

### Frontend

- [ ] Header menampilkan identitas aplikasi dan login staf.
- [ ] Grid card responsif mulai 360 px.
- [ ] Card menampilkan nama, badge format/status, participant count, dan progress selesai/total.
- [ ] Card menggunakan Wayfinder route detail.
- [ ] Empty state muncul jika tidak ada lomba aktif.
- [ ] Card dapat difokuskan dan dibuka dengan keyboard.
- [ ] Status tidak disampaikan melalui warna saja.
- [ ] Pertahankan dark mode jika starter kit mendukungnya.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
php artisan test --compact --filter="public landing"
```

## C3 — Detail format kompetisi

### Backend/UI

- [ ] Backend mengirim scoring rules dan standings hasil kalkulator Fase 4.
- [ ] Match dikelompokkan per round dan leg.
- [ ] Detail menyediakan tab/section Klasemen dan Pertandingan.
- [ ] Table memprioritaskan rank, team, main, difference, dan point pada mobile.
- [ ] Shared rank ditampilkan sesuai hasil backend.
- [ ] Pending match diberi label `Belum dimainkan`.
- [ ] Filter round/status tidak membocorkan route internal.
- [ ] Tim tanpa match tetap tampil di standings.

### Test

- [ ] Guest melihat custom scoring rules.
- [ ] Standings prop sesuai calculator.
- [ ] Pending dan completed match dapat dibedakan.
- [ ] Full competition menampilkan leg dengan benar.

### Verifikasi

```bash
php artisan test --compact --filter="public standings"
npm run types:check
```

## C4 — Detail bracket sistem gugur

### Backend/UI

- [ ] Group match berdasarkan round/babak dengan urutan stabil.
- [ ] Desktop menampilkan kolom babak kiri ke kanan.
- [ ] Mobile menggunakan horizontal scroll, bukan mengecilkan card sampai tidak terbaca.
- [ ] Future slot menampilkan `Menunggu pemenang`.
- [ ] Bye ditandai jelas.
- [ ] Completed match menampilkan score dan winner highlight.
- [ ] Tie-break menampilkan keterangan yang aman untuk publik jika ada.
- [ ] Bracket final completed tetap dapat dibuka.

### Test

- [ ] Bracket prop memiliki seluruh node dan next round.
- [ ] Bye tidak tampil sebagai pertandingan yang menunggu score.
- [ ] Winner/future slot sesuai data progression.

### Verifikasi

```bash
php artisan test --compact --filter="public knockout bracket"
npm run types:check
```

## C5 — Audit UX internal

### Implementasi

- [ ] Semua form memiliki error field, processing state, dan pencegahan submit ganda.
- [ ] Semua destructive/final action memiliki dialog konfirmasi.
- [ ] Status badge konsisten di admin, operator, dan public.
- [ ] Empty/loading states tersedia pada daftar yang dapat kosong.
- [ ] Navigasi role tidak menampilkan menu yang tidak relevan.
- [ ] Flash/toast tidak menampilkan detail exception sensitif.
- [ ] Tabel dan bracket dapat digunakan pada mobile.
- [ ] Semua halaman Vue memiliki satu root element.
- [ ] Tidak ada hardcoded route backend.

### Verifikasi

```bash
npm run types:check
npm run lint:check
npm run format:check
```

## C6 — Audit keamanan dan integritas

### Implementasi/test

- [ ] Audit setiap mutation route memiliki auth, verified jika dipertahankan, policy, dan Form Request.
- [ ] Audit scoped binding seluruh nested resource.
- [ ] Audit inactive operator tidak dapat update.
- [ ] Audit props publik/internal agar tidak overexpose data.
- [ ] Audit file upload logo.
- [ ] Audit transaksi lock dan result progression.
- [ ] Audit concurrency test result version/draw version.
- [ ] Jalankan test role matrix admin/assigned/unassigned/guest.

### Verifikasi

```bash
php artisan test --compact --filter="authorization"
php artisan test --compact --filter="forbidden"
php artisan test --compact --filter="stale"
```

## C7 — Performa dan skala target

### Implementasi/test

- [ ] Seed/factory test hingga 64 participant pada tiap format yang relevan.
- [ ] Verifikasi generator selesai dan jumlah match benar.
- [ ] Periksa query landing/list/detail untuk N+1.
- [ ] Tambahkan index berdasarkan query nyata, bukan dugaan.
- [ ] Pastikan pagination internal tidak menghilangkan filter.
- [ ] Ukur response target secara lokal sebagai indikasi, bukan jaminan infrastruktur production.
- [ ] Jangan cache sebelum query terbukti menjadi bottleneck.

### Verifikasi

```bash
php artisan test --compact --filter="competition scale"
```

## C8 — Browser/smoke dan final CI

### Browser verification

- [ ] Admin: login → buat lomba → peserta → assignment → shuffle → lock.
- [ ] Operator: login → buka assignment → input score → koreksi yang diizinkan.
- [ ] Public: landing → detail → standings/bracket.
- [ ] Periksa desktop dan viewport sekitar 360 px.
- [ ] Periksa keyboard focus pada link, form, dialog, dan tab.
- [ ] Periksa tidak ada JavaScript error atau console error.
- [ ] Jika Pest Browser tersedia, automasikan smoke flow utama.

### Final verification

```bash
vendor/bin/pint --dirty --format agent
composer run ci:check
npm run build
```

Jangan menandai fase selesai jika full CI gagal, walaupun kegagalan terlihat tidak terkait. Diagnosis dan dokumentasikan sumbernya terlebih dahulu.

## Test Gate Fase 5

- [ ] FR-PUB-01 sampai FR-PUB-08 hijau.
- [ ] AC-15 dan AC-16 hijau end-to-end pada layer HTTP.
- [ ] Seluruh AC-01 sampai AC-16 dari fase sebelumnya tetap hijau.
- [ ] Public props tidak mengandung data internal sensitif.
- [ ] Role matrix tidak memiliki celah direct URL/request.
- [ ] Tampilan utama dapat digunakan pada mobile dan keyboard.
- [ ] Tidak ada error JavaScript pada smoke flow.
- [ ] Full test, lint, typecheck, format check, static analysis, dan build hijau.
- [ ] Gap browser automation, jika ada, disetujui dan tercatat.

## Exit Criteria

Fase 5 dan MVP dapat ditandai `DONE` jika:

- seluruh Definition of Done pada [Implementation Roadmap](./IMPLEMENTATION-ROADMAP.md#definition-of-done-proyek) terpenuhi;
- tidak ada bug blocker;
- tidak ada deviasi terhadap [PRD](./PRD.md) yang belum didokumentasikan;
- build production berhasil.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
|         |            |                 |            |       |                 |
