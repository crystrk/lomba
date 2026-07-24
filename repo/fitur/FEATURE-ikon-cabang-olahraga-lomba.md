# Feature: Ikon Cabang Olahraga Lomba

> Dokumen ini adalah rencana pengembangan fitur Ikon Cabang Olahraga Lomba.  
> Dibuat berdasarkan PRD: `repo/PRD.md` | Versi: 24 Juli 2026

---

## 1. Overview

**Nama Fitur:** Ikon Cabang Olahraga Lomba
**Status:** Implemented (MVP)
**Priority:** Medium  
**Epic/Module:** Pengelolaan Lomba dan Landing Page Publik  
**Detected Stack:** PHP 8.4, Laravel 13.20, SQLite, Inertia.js 3.1, Vue 3.5 + TypeScript, Tailwind CSS 4.3, shadcn-vue/reka-ui, Wayfinder, dan Pest 4

### Problem Statement

Kartu lomba pada landing page publik saat ini hanya membedakan lomba melalui nama, format, status, jumlah peserta, dan progres pertandingan. Pengunjung belum dapat mengenali cabang olahraga secara cepat melalui elemen visual, terutama ketika beberapa lomba memiliki nama atau format yang serupa.

### Proposed Solution

Tambahkan pilihan cabang olahraga pada form tambah dan edit lomba dengan enam nilai awal: sepak bola, badminton, tenis lapangan, tenis meja, catur, dan bola voli. Sistem menyimpan identifier cabang olahraga, kemudian frontend memetakan identifier tersebut ke SVG lokal yang aman dan menampilkannya pada kartu lomba di landing page publik.

Markup SVG tidak disimpan di database dan tidak diunggah oleh admin. Database hanya menyimpan nilai semantik cabang olahraga agar validasi, aksesibilitas, dan pemetaan ikon tetap konsisten.

### Keputusan Product Owner

- Setiap cabang olahraga yang tersedia wajib mempunyai ikon SVG khusus.
- Ikon default/fallback menggunakan SVG piala.
- Desain awal seluruh SVG dibuat pada tahap development dan diserahkan untuk review visual Product Owner.

---

## 2. Alignment dengan PRD

| Aspek            | Keterangan                                                                                                                         |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Product Goal** | Mendukung tujuan “menyediakan informasi lomba yang mudah diakses publik” dan memperjelas kartu lomba pada landing page.            |
| **Target User**  | Admin memilih cabang olahraga saat mengelola lomba; pengunjung publik melihat ikon pada kartu lomba.                               |
| **Scope**        | ✅ In scope sebagai pengayaan metadata lomba dan UI publik; tidak mengubah format pertandingan, lifecycle, skor, maupun kalkulasi. |
| **Dependency**   | CRUD lomba, response Inertia landing page, dan kartu lomba publik yang sudah tersedia.                                             |

Fitur ini memperluas FR-COMP-01 (metadata lomba), FR-PUB-01, dan FR-PUB-02 (informasi pada kartu lomba). Tidak ada konflik dengan daftar non-goal PRD karena fitur tidak menambah format pertandingan, upload publik, atau sistem penjadwalan.

---

## 3. User Flow

### Admin memilih ikon cabang olahraga

1. Admin membuka form **Tambah Lomba** atau **Edit Lomba**.
2. Admin memilih cabang olahraga dari pilihan yang tersedia.
3. UI menampilkan label dan pratinjau ikon SVG untuk pilihan tersebut.
4. Admin menyimpan form.
5. Backend memvalidasi identifier cabang olahraga dan menyimpannya bersama metadata lomba.

### Publik melihat ikon lomba

1. Pengunjung membuka landing page publik.
2. Backend mengirim identifier cabang olahraga pada setiap item `competitions`.
3. Kartu memetakan identifier ke komponen SVG yang sesuai.
4. Ikon tampil bersama nama lomba tanpa mengurangi informasi format, status, peserta, dan progres yang sudah ada.

**Happy Path:**

```text
Admin memilih cabang olahraga → backend memvalidasi dan menyimpan identifier → lomba dipublikasikan → landing page menerima identifier → kartu menampilkan SVG yang sesuai
```

**Edge Cases:**

- [ ] Data lomba lama yang belum memiliki cabang olahraga menampilkan ikon SVG piala tanpa merusak kartu.
- [ ] Request dengan identifier yang tidak tersedia ditolak dan menampilkan error pada field cabang olahraga.
- [ ] Mapping SVG yang tidak ditemukan menggunakan ikon SVG piala serta label “Cabang olahraga belum ditentukan”.
- [ ] Ikon tetap terbaca pada light mode, dark mode, layar 360 px, dan kondisi fokus keyboard.
- [ ] Kegagalan validasi tidak menghilangkan pilihan cabang olahraga yang sudah dipilih di form.

---

## 4. Functional Requirements

### Must Have (MVP)

- [x] Admin dapat memilih satu cabang olahraga saat membuat lomba.
- [x] Admin dapat melihat dan mengubah cabang olahraga melalui form edit sesuai policy update lomba yang berlaku.
- [x] Pilihan awal terdiri dari `football`, `badminton`, `tennis`, `table_tennis`, `chess`, dan `volleyball` dengan label Bahasa Indonesia. (ditambah `general` untuk fallback)
- [x] Backend hanya menerima identifier yang terdaftar dan tidak menerima markup SVG dari request.
- [x] Identifier cabang olahraga disimpan pada tabel `competitions` dan masuk dalam mass-assignment model secara eksplisit.
- [x] Landing page menerima identifier cabang olahraga dalam props lomba.
- [x] Setiap identifier memiliki SVG yang berbeda dan sesuai cabang olahraga.
- [x] Tidak ada cabang olahraga yang dapat ditambahkan ke registry tanpa komponen SVG khusus.
- [x] Kartu lomba publik menampilkan SVG, nama cabang olahraga yang aksesibel, serta SVG piala untuk data lama/null atau mapping yang tidak ditemukan.
- [x] SVG menggunakan `currentColor` agar mengikuti warna tema dan state kartu.
- [x] Desain awal enam SVG cabang olahraga dan satu SVG piala dibuat sebagai bagian implementasi untuk direview Product Owner.

### Should Have

- [x] Form admin menampilkan pratinjau SVG di dalam atau di dekat pilihan cabang olahraga.
- [x] Factory dan demo seeder menghasilkan data cabang olahraga yang representatif.
- [x] Mapping label, identifier, dan komponen SVG memiliki satu sumber kebenaran frontend agar tidak diduplikasi pada Create, Edit, dan kartu publik.

### Won't Have (untuk versi ini)

- [ ] Upload SVG bebas oleh admin, karena menambah risiko XSS, sanitasi, penyimpanan file, dan inkonsistensi visual.
- [ ] Pembuatan ikon otomatis berdasarkan nama lomba.
- [ ] Cabang olahraga di luar enam pilihan awal.
- [ ] Perubahan algoritma pertandingan atau aturan skor berdasarkan cabang olahraga.
- [ ] Filter landing page berdasarkan cabang olahraga; dapat direncanakan setelah metadata tersedia dan kebutuhan pengguna tervalidasi.

---

## 5. Non-Functional Requirements

| Aspek               | Requirement                                                                                                                                              |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Performance**     | SVG dibundel sebagai aset/component lokal tanpa request jaringan per kartu; landing page tetap memenuhi target PRD kurang dari 2 detik pada data target. |
| **Security**        | Tidak merender SVG atau HTML dari database/user input; request hanya menerima enum identifier yang diizinkan.                                            |
| **Scalability**     | Penambahan cabang baru dilakukan melalui enum backend dan registry ikon frontend tanpa perubahan struktur tabel baru.                                    |
| **Availability**    | Kegagalan mapping atau data null tidak memblokir render landing page dan selalu menghasilkan SVG piala.                                                  |
| **Accessibility**   | Ikon dekoratif menggunakan `aria-hidden="true"` bila label cabang tampil; jika tanpa label visual, sediakan nama aksesibel untuk pembaca layar.          |
| **Maintainability** | Nama identifier stabil, tidak bergantung pada nama file atau label terjemahan, dan tidak diduplikasi sebagai string bebas di backend.                    |

---

## 6. UI/UX Notes

### Form admin

- Tambahkan field **Cabang Olahraga** setelah deskripsi dan sebelum **Format Lomba**.
- Gunakan komponen `Select` yang sudah dipakai oleh form lomba, bukan input teks bebas.
- Setiap opsi menampilkan label Bahasa Indonesia; bila perilaku komponen mendukung dengan baik, sertakan ikon kecil.
- Tampilkan `InputError` pada field yang sama mengikuti pola validasi existing.
- Pada form edit, nilai tersimpan harus langsung terpilih.

### Kartu publik

Ikon ditempatkan pada area header kartu agar cabang olahraga dapat dikenali sebelum membaca detail format. Ukuran visual yang disarankan adalah 40–48 px di dalam container berwarna lembut, sementara judul dan badge status tetap mendapatkan ruang utama.

```text
┌──────────────────────────────────────┐
│ accent status                       │
│ ┌──────┐  Nama Lomba       [Status] │
│ │ SVG  │  Badminton                 │
│ └──────┘                             │
│ Format · Peserta · Progres           │
│ ███████░░░                    70%     │
│ Lihat Match & Detail              →  │
└──────────────────────────────────────┘
```

- Ikon harus mempertahankan rasio aspek, tidak terpotong, dan tetap jelas pada light/dark mode.
- Ikon tidak menggantikan teks nama lomba atau status; warna bukan satu-satunya pembeda.
- Layout tidak boleh menyebabkan badge status atau judul terpotong secara berlebihan pada lebar 360 px.

**Touchpoints:**

- [x] Form tambah lomba: `resources/js/pages/Admin/Competitions/Create.vue`
- [x] Form edit lomba: `resources/js/pages/Admin/Competitions/Edit.vue`
- [x] Card landing page publik: `resources/js/pages/Welcome.vue`
- [x] Registry/komponen ikon cabang olahraga reusable di bawah struktur existing `resources/js/components/competitions/`
- [x] Halaman detail publik — `CompetitionOverviewCard.vue` + `Public/Competition/Show.vue` menampilkan ikon sport

---

## 7. Technical Plan

### Existing Architecture

- Aplikasi menggunakan Laravel + Inertia + Vue, dibuktikan oleh `composer.json`, `package.json`, `vite.config.ts`, dan lokasi page di `resources/js/pages`.
- `Competition` saat ini menyimpan metadata nama, slug, deskripsi, format, status, poin, dan tanggal di `database/migrations/2026_07_20_120915_create_competitions_table.php` serta `app/Models/Competition.php`.
- Pembuatan dan pembaruan lomba divalidasi melalui `StoreCompetitionRequest` dan `UpdateCompetitionRequest`, lalu diorkestrasi oleh `app/Http/Controllers/Admin/CompetitionController.php`.
- Form Create/Edit menggunakan `useForm`, komponen `Select`, `InputError`, dan route yang dihasilkan Wayfinder.
- Landing page dibangun di `resources/js/pages/Welcome.vue`; props publik dipetakan secara eksplisit oleh `PublicCompetitionController::index()`.
- SVG/icon UI existing menggunakan komponen `@lucide/vue`, tetapi enam ikon cabang olahraga spesifik belum tersedia sebagai domain registry.
- Pengujian backend menggunakan Pest; coverage relevan berada di `tests/Feature/CompetitionManagementTest.php` dan `tests/Feature/PublicCompetitionTest.php`.

### Implementation Impact

| Layer          | Perubahan                                                                           | Lokasi/Komponen                                                          |
| -------------- | ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| Backend        | Tambah enum cabang olahraga, validasi store/update, fillable, dan props controller. | `app/Enums`, model `Competition`, Form Requests, admin/public controller |
| Frontend       | Tambah pilihan cabang pada form, registry ikon SVG, dan render ikon pada card.      | Create/Edit competition, `Welcome.vue`, komponen/registry ikon reusable  |
| Database       | Tambah kolom string nullable `sport` dan strategi backfill.                         | Migration baru tabel `competitions`                                      |
| Infrastructure | Tidak ada queue, cache, storage, endpoint, atau environment variable baru.          | Tidak ada                                                                |

### Backend — Laravel

- **Domain value:** _[usulan:]_ buat backed enum `CompetitionSport` berisi `Football`, `Badminton`, `Tennis`, `TableTennis`, `Chess`, dan `Volleyball`. Gunakan nilai database stabil `football`, `badminton`, `tennis`, `table_tennis`, `chess`, dan `volleyball`.
- **Data model:** migration baru menambahkan `sport` sebagai string nullable. Nullable menjaga deployment aman untuk baris existing dan menyediakan fallback eksplisit.
- **Model:** tambahkan `sport` ke `#[Fillable]`, PHPDoc property, dan cast ke `CompetitionSport` bila nilai non-null.
- **Validation:** `StoreCompetitionRequest` dan `UpdateCompetitionRequest` menggunakan `Rule::enum(CompetitionSport::class)` atau mekanisme enum Laravel 13 yang setara. Keputusan required/nullable mengikuti jawaban Open Question pertama.
- **Business logic:** controller tetap tipis; cukup menyertakan `sport` pada props create/edit/index/show yang benar-benar membutuhkan data tersebut.
- **Authorization:** tidak ada policy baru. Pemilihan/perubahan cabang mengikuti otorisasi create/update `Competition` yang sudah ada.
- **Routing:** tidak ada route baru; form tetap memakai fungsi Wayfinder existing.

### Frontend — Vue/Inertia

- **Rendering mode:** Inertia Vue client-side page, mengikuti page existing.
- **UI state:** tambahkan `sport` pada object `useForm` Create/Edit; tidak memerlukan store atau composable global.
- **Data flow:** daftar pilihan dikirim sebagai props dari controller berdasarkan enum backend, sedangkan identifier lomba dikirim pada props `competitions` landing page.
- **Icon registry:** buat satu mapping typed dari identifier ke label dan komponen SVG. Registry harus lengkap untuk seluruh nilai `CompetitionSport`; jangan gunakan `v-html`.
- **SVG implementation:** buat desain awal enam komponen Vue SVG cabang olahraga dan satu komponen SVG piala sebagai default. Gunakan `viewBox` konsisten, `fill="none"`/`stroke="currentColor"` atau `fill="currentColor"`, dan tanpa style/script eksternal. Desain awal masuk tahap review visual Product Owner sebelum dianggap final.
- **Card integration:** perluas tipe props competition pada `Welcome.vue` dengan `sport: string | null`, lalu render komponen hasil mapping dengan SVG piala sebagai fallback.
- **Styling:** gunakan utility Tailwind CSS 4 existing dan token tema (`text-*`, `bg-*/10`, `border-border`) agar dark mode tetap konsisten.

### Data Model

| Field                | Tipe   | Null            | Aturan                                                                                                                                   |
| -------------------- | ------ | --------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `competitions.sport` | string | Ya saat rollout | Harus salah satu nilai `CompetitionSport` untuk request baru/ubah; tidak perlu index pada MVP karena belum digunakan untuk filter/query. |

**Migration dan backfill:**

1. Tambahkan kolom nullable agar migration tidak menebak cabang olahraga lomba existing.
2. Data existing tetap null dan menggunakan SVG piala sampai admin mengeditnya atau backfill manual disetujui.
3. Jika pilihan cabang diwajibkan untuk lomba baru, validation store `required`; update menerima existing null tetapi mewajibkan nilai ketika form lengkap dikirim.
4. Rollback hanya menghapus kolom `sport`; tidak ada file upload yang perlu dibersihkan.

### Security & Privacy

- [x] Authentication dan authorization mengikuti policy CRUD lomba existing.
- [x] Backend membatasi nilai dengan enum; frontend tidak menjadi sumber validasi utama.
- [x] SVG bersumber dari kode/aset lokal dan tidak dirender menggunakan `v-html`.
- [x] Tidak ada data sensitif, upload, logging khusus, atau mass assignment bebas.
- [x] Lisensi aset SVG harus kompatibel dengan proyek dan atribusi dipenuhi bila diperlukan.

### Testing Strategy

| Level           | Skenario                                                                                                               | Tool Existing                                                                       |
| --------------- | ---------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Backend feature | Admin dapat membuat dan mengubah lomba dengan sport valid; nilai invalid ditolak; operator tetap ditolak.              | Pest 4, `CompetitionManagementTest.php`                                             |
| Backend feature | Landing props menyertakan identifier sport untuk lomba aktif/selesai dan null tetap aman.                              | Pest 4, `PublicCompetitionTest.php`                                                 |
| Domain/schema   | Kolom/cast enum tersedia dan factory menghasilkan nilai valid.                                                         | Pest 4, `DomainSchemaTest.php`/factory test terkait                                 |
| Frontend static | TypeScript menerima seluruh mapping sport dan SVG piala default; build tidak menemukan import SVG yang rusak.          | `npm run types:check`, `npm run build`                                              |
| UI/browser      | Enam ikon dan SVG piala tampil benar pada card desktop/mobile, light/dark, serta tidak mengganggu focus link.          | Pemeriksaan browser existing; browser automation hanya jika tooling proyek tersedia |
| Visual review   | Product Owner mereview keterbacaan dan kesesuaian desain awal seluruh SVG; revisi diterapkan sebelum finalisasi fitur. | Review visual pada halaman publik                                                   |

### Operational Impact

- **Migration/backfill:** migration additive dan nullable; tidak memerlukan downtime. Backfill otomatis tidak disarankan karena nama lomba bukan sumber cabang olahraga yang andal.
- **Queue/scheduler/cache:** tidak ada perubahan.
- **Environment:** tidak ada variable atau secret baru.
- **Deployment:** jalankan migration dan build Vite pada deployment normal. Deploy backend dan frontend dalam rilis yang sama agar props dan type sinkron.
- **Observability:** tidak perlu metric baru; error frontend mapping dicegah oleh typed registry dan SVG piala default.

### Integration

- [x] Tidak memerlukan third-party runtime service atau package baru.
- [x] Desain awal SVG dibuat secara internal dalam implementasi sehingga tidak bergantung pada aset atau lisensi pihak ketiga.

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:

- [x] Diberikan admin membuka form tambah lomba, ketika memilih salah satu dari enam cabang dan menyimpan data valid, maka identifier cabang tersimpan pada lomba.
- [x] Diberikan request store/update berisi identifier di luar daftar, maka backend menolak field `sport` dan tidak mengubah database.
- [x] Diberikan lomba memiliki cabang olahraga, ketika lomba tampil pada landing page, maka kartu menampilkan SVG dan label cabang yang tepat.
- [x] Keenam cabang—sepak bola, badminton, tenis lapangan, tenis meja, catur, dan bola voli—memiliki ikon yang berbeda dan dapat dikenali.
- [x] Registry tidak dapat melewatkan ikon khusus untuk salah satu nilai cabang olahraga tanpa terdeteksi oleh type-check atau test.
- [x] Diberikan lomba existing dengan `sport = null` atau mapping tidak ditemukan, ketika landing page dirender, maka kartu menampilkan SVG piala tanpa error.
- [x] SVG tidak berasal dari database, tidak menggunakan `v-html`, dan tidak mengandung script/event handler.
- [x] Form Create/Edit menampilkan pesan validasi pada field cabang olahraga dan mempertahankan nilai input saat validasi gagal.
- [x] Card tetap responsif pada lebar 360 px, dapat difokuskan dengan keyboard, dan terbaca pada light/dark mode.
- [ ] Product Owner telah mereview desain awal enam ikon cabang olahraga dan ikon piala; revisi yang disepakati sudah diterapkan.
- [x] Test feature relevan, type-check TypeScript, dan build frontend berhasil dijalankan.
- [x] Tidak ada regression pada informasi format, status, peserta, progres, dan navigasi card existing.

---

## 9. Keputusan dan Gap Tersisa

> Seluruh open questions awal telah dijawab oleh implementasi.

| Pertanyaan | Keputusan Implementasi |
| ---------- | ---------------------- |
| Apakah cabang olahraga wajib untuk semua lomba baru? | **Wajib** untuk create (`required`); update bersifat `sometimes` sehingga data existing null tetap aman. |
| Apakah enam pilihan ini daftar tertutup? | **Daftar tertutup** dengan 6 cabang + `general` untuk lomba tanpa cabang atau fallback. |
| Apakah "sepak bola" mencakup futsal? | Tidak — futsal akan menjadi identifier terpisah jika ditambahkan nanti. |
| Apakah ikon perlu tampil di halaman detail publik? | **Ya** — diimplementasikan di `CompetitionOverviewCard.vue` dan `Public/Competition/Show.vue`. |
| Data lomba existing tanpa sport? | Fallback ke SVG piala (`TrophyIcon`) + label "Lomba Umum" via nilai `general` default tidak mengubah data. |


### Gap Minor (diketahui, di luar MVP)

- [ ] **Admin list view** — `Admin/Competitions/Index.vue` dan controller `index()` belum menampilkan kolom sport. Belum menghambat pengelolaan karena data sport dapat dilihat di halaman detail/show.
- [ ] **Desain SVG** — menunggu review visual Product Owner untuk 6 ikon cabang olahraga dan ikon piala. Implementasi sudah menyertakan desain awal.

---

## 10. Timeline Aktual

| Fase          | Estimasi          | Keterangan                                                                         |
| ------------- | ----------------- | ---------------------------------------------------------------------------------- |
| Design & Spec | 0,5 hari          | Desain awal SVG + review internal.                                                  |
| Development   | 2 hari            | Enum/migration/validation, form admin, registry 7 SVG, card publik, detail publik. |
| Testing       | 1 hari            | Pest, type-check/build, review responsif.                                           |
| Release       | 24 Juli 2026      | Migration additive + build frontend berhasil tanpa regression.                      |

**Confidence:** High — seluruh kode backend dan frontend telah diimplementasikan dan teruji. Sisa hanya review visual PO dan tambahan kolom sport di admin list (opsional).

---

_Dokumen ini akan terus diperbarui selama proses development._
