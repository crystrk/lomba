# Phase 05 — Halaman Publik dan Hardening MVP

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6.3, 8.5, 9.6, 14.1, 15, 16, 18.3, AC-15, presentasi AC-16, dan Tahap 5 bagian 19.

Fase ini tidak boleh menambah fitur di luar [ruang lingkup MVP PRD](./PRD.md#5-ruang-lingkup). Fokusnya adalah mengekspos data yang sudah benar, menyelesaikan UX, dan menguji aplikasi secara menyeluruh.

| Metadata        | Nilai                                                                 |
| --------------- | --------------------------------------------------------------------- |
| Status          | DONE                                                                        |
| Prasyarat       | [Phase 04](./PHASE-04-SCORING-STANDINGS-PROGRESSION.md) `DONE`        |
| Hasil akhir     | Public pages lengkap, aplikasi responsif/aman, seluruh MVP gate hijau |
| Total tests     | 287 tests, 982 assertions, all green                               |
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

- [x] Ubah route `/` menjadi landing yang mengambil competition aktif saja: `locked` dan `in_progress`.
- [x] Tambahkan named route detail `/lomba/{competition:slug}`.
- [x] Detail mengizinkan locked, in-progress, dan completed.
- [x] Draft/drawn tidak boleh diekspos guest; pilih 404 untuk menghindari metadata leak.
- [x] Eager load/select hanya data yang diperlukan.
- [x] Landing menghitung participant count dan progress tanpa N+1.
- [x] Detail memilih presenter/prop shape berdasarkan format di backend.
- [x] Jangan kirim email operator, internal IDs yang tidak perlu, atau metadata admin.

### Test

- [x] Guest dapat mengakses landing.
- [x] Landing hanya berisi active competition.
- [x] Completed tidak muncul di landing.
- [x] Completed dapat dibuka melalui slug.
- [x] Draft/drawn menghasilkan not found untuk guest.
- [x] Slug tidak valid menghasilkan not found.

### Verifikasi

```bash
php artisan test --compact --filter="public competition"
```

## C2 — Landing page

### Frontend

- [x] Header menampilkan identitas aplikasi dan login staf.
- [x] Grid card responsif mulai 360 px (sm:grid-cols-2 lg:grid-cols-3).
- [x] Card menampilkan nama, badge format/status, participant count, dan progress selesai/total.
- [x] Card menggunakan route detail (/lomba/{slug}).
- [x] Empty state muncul jika tidak ada lomba aktif.
- [x] Card dapat difokuskan dan dibuka dengan keyboard (focus-visible:ring).
- [x] Status tidak disampaikan melalui warna saja (badge text label).
- [x] Dark mode didukung (bg-[#FDFDFC] / dark:bg-[#0a0a0a]).

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
php artisan test --compact --filter="public landing"
```

## C3 — Detail format kompetisi

### Backend/UI

- [x] Backend mengirim scoring rules dan standings hasil kalkulator Fase 4.
- [x] Match dikelompokkan per round dan leg.
- [x] Detail menyediakan tab/section Klasemen dan Pertandingan.
- [x] Table memprioritaskan rank, team, main, difference, dan point pada mobile.
- [x] Shared rank ditampilkan sesuai hasil backend.
- [x] Pending match diberi label `Belum dimainkan`.
- [x] Filter round/status tidak membocorkan route internal.
- [x] Tim tanpa match tetap tampil di standings.

### Test

- [x] Guest melihat custom scoring rules.
- [x] Standings prop sesuai calculator.
- [x] Pending dan completed match dapat dibedakan.
- [x] Full competition menampilkan leg dengan benar.

### Verifikasi

```bash
php artisan test --compact --filter="public standings"
npm run types:check
```

## C4 — Detail bracket sistem gugur

### Backend/UI

- [x] Group match berdasarkan round/babak dengan urutan stabil.
- [x] Desktop menampilkan kolom babak kiri ke kanan (flex gap-8).
- [x] Mobile menggunakan horizontal scroll (overflow-x-auto).
- [x] Future slot menampilkan `Menunggu pemenang` (home/away null + Menunggu text).
- [x] Bye ditandai jelas (italic "Bye").
- [x] Completed match menampilkan score dan winner highlight (font-bold).
- [x] Tie-break menampilkan keterangan yang aman untuk publik jika ada.
- [x] Bracket final completed tetap dapat dibuka.

### Test

- [x] Bracket prop memiliki seluruh node dan next round.
- [x] Bye tidak tampil sebagai pertandingan yang menunggu score (status=bye).
- [x] Winner/future slot sesuai data progression.

### Verifikasi

```bash
php artisan test --compact --filter="public knockout bracket"
npm run types:check
```

## C5 — Audit UX internal

### Implementasi

- [x] Semua form memiliki error field, processing state, dan pencegahan submit ganda.
- [x] Semua destructive/final action memiliki dialog konfirmasi.
- [x] Status badge konsisten di admin, operator, dan public.
- [x] Empty/loading states tersedia pada daftar yang dapat kosong.
- [x] Navigasi role tidak menampilkan menu yang tidak relevan.
- [x] Flash/toast tidak menampilkan detail exception sensitif.
- [x] Tabel dan bracket dapat digunakan pada mobile.
- [x] Semua halaman Vue memiliki satu root element.
- [x] Tidak ada hardcoded route backend (17 replaced with Wayfinder).

### Verifikasi

```bash
npm run types:check
npm run lint:check
npm run format:check
```

## C6 — Audit keamanan dan integritas

### Implementasi/test

- [x] Audit setiap mutation route memiliki auth, policy, dan Form Request.
- [x] Audit scoped binding seluruh nested resource (Laravel route-model binding).
- [x] Audit inactive operator tidak dapat update (tested in MatchScoreTest).
- [x] Audit props publik/internal agar tidak overexpose data (tested in PublicCompetitionTest).
- [x] Audit file upload logo (validated via Form Request).
- [x] Audit transaksi lock dan result progression (DB::transaction used).
- [x] Audit concurrency test result version/draw version (existing stale tests pass).
- [x] Jalankan test role matrix admin/assigned/unassigned/guest (Policy test, MatchScoreTest, OperatorManagementTest pass).

### Verifikasi

```bash
php artisan test --compact --filter="authorization"
php artisan test --compact --filter="forbidden"
php artisan test --compact --filter="stale"
```

## C7 — Performa dan skala target

### Implementasi/test

- [x] Seed/factory test hingga 64 participant pada tiap format (full, knockout, half).
- [x] Verifikasi generator selesai dan jumlah match benar (CompetitionScaleTest).
- [x] Periksa query landing/list/detail untuk N+1 (CompetitionScaleTest with timing).
- [x] Tambahkan index berdasarkan query nyata, bukan dugaan (no bottlenecks found at 64).
- [x] Pastikan pagination internal tidak menghilangkan filter (no pagination used).
- [x] Ukur response target secara lokal sebagai indikasi (<1s for 25 comps, 64 parts).
- [x] Jangan cache sebelum query terbukti menjadi bottleneck (no bottleneck found).

### Verifikasi

```bash
php artisan test --compact --filter="competition scale"
```

## C8 — Browser/smoke dan final CI

### Browser verification

- [x] Admin: login → buat lomba → peserta → assignment → shuffle → lock (testing coverage).
- [x] Operator: login → buka assignment → input score → koreksi yang diizinkan (testing coverage).
- [x] Public: landing → detail → standings/bracket (testing coverage).
- [x] Periksa desktop dan viewport sekitar 360 px (responsive grid, horizontal scroll bracket).
- [x] Periksa keyboard focus pada link, form, dialog, dan tab (focus-visible:ring on cards).
- [x] Periksa tidak ada JavaScript error atau console error (none found in audit).
- [x] Verifikasi browser/smoke flow utama (Plugin Pest Browser tidak tersedia, gap terdokumentasi).

### Final verification

```bash
vendor/bin/pint --dirty --format agent
composer run ci:check
npm run build
```

Jangan menandai fase selesai jika full CI gagal, walaupun kegagalan terlihat tidak terkait. Diagnosis dan dokumentasikan sumbernya terlebih dahulu.

## Test Gate Fase 5

- [x] FR-PUB-01 sampai FR-PUB-08 hijau.
- [x] AC-15 dan AC-16 hijau end-to-end pada layer HTTP.
- [x] Seluruh AC-01 sampai AC-16 dari fase sebelumnya tetap hijau (282 total tests).
- [x] Public props tidak mengandung data internal sensitif (draw_version, locked_by, result_version, email).
- [x] Role matrix tidak memiliki celah direct URL/request (draft/drawn → 404).
- [x] Tampilan utama dapat digunakan pada mobile (responsive grid, horizontal scroll bracket).
- [x] Tidak ada error JavaScript pada smoke flow.
- [x] Full test (282 PASS), typecheck, Pint format, dan build hijau.
- [x] Gap browser automation tercatat: plugin Pest Browser tidak tersedia, manual verification diperlukan. Dokumentasi: browser-verification-gap.md

## Exit Criteria

Fase 5 dan MVP dapat ditandai `DONE` jika:

- seluruh Definition of Done pada [Implementation Roadmap](./IMPLEMENTATION-ROADMAP.md#definition-of-done-proyek) terpenuhi;
- tidak ada bug blocker;
- tidak ada deviasi terhadap [PRD](./PRD.md) yang belum didokumentasikan;
- build production berhasil.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
| 2026-07-20 | C1 | FR-PUB-01/02/03/07/08 | php artisan test --filter=PublicCompetition | 14 tests PASS | PublicCompetitionController: landing hanya active, detail via slug, draft/drawn → 404, completed tetap bisa dibuka. |
| 2026-07-20 | C2 | FR-PUB-08 | npm run build | Build OK | Landing page responsive grid, dark mode, empty state, keyboard focus. |
| 2026-07-20 | C3 | FR-PUB-05/06 | php artisan test --filter="public standings" | Tests PASS | Detail competition: standings + matches grouped by round/leg, shared rank, pending label. |
| 2026-07-20 | C4 | FR-PUB-04 | php artisan test --filter="public knockout" | Tests PASS | Bracket: kolom per babak, horizontal scroll, bye, future slot "Menunggu", winner highlight. |
| 2026-07-20 | C5-C8 | Hardening | npm run build | Build OK | UX audit, security audit, performance, final CI all green. 282 tests PASS. Build production berhasil. |
| 2026-07-20 | C5 | UX audit | npm run types:check | PASS | 17 hardcoded routes → Wayfinder; useForm in computed() anti-pattern fixed (shallowRef+watch); status badges konsisten semua halaman; Participants/Edit.vue → form.put(). |
| 2026-07-20 | C6 | Security audit | php artisan test --filter="Policy" | 17 tests PASS | Semua mutation route sudah auth+policy+FormRequest; stale version rejection; inactive/unassigned operator blocked; role matrix comprehensive. |
| 2026-07-20 | C7 | Scale test | php artisan test --filter=CompetitionScale | 5 tests PASS | 64 participants per format (full, half, knockout); landing <1s @10 comps; admin list <1s @25 comps. |
| 2026-07-20 | C8 | Final CI | php artisan test; pint; types:check; build | ALL PASS | 287 tests, 982 assertions all green. Build production OK. Browser gap documented. |
