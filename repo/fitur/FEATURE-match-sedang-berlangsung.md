# Feature: Flag Match Sedang Berlangsung & Section Publik Match Ongoing

> Dokumen ini adalah rencana pengembangan fitur **Flag Match Sedang Berlangsung (Ongoing Match)** dan **Section Pertandingan Berlangsung di Halaman Beranda Publik**.  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 13 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Flag Match Sedang Berlangsung & Section Publik Match Ongoing  
**Status:** Draft  
**Priority:** High  
**Epic/Module:** Manajemen Pertandingan & Portal Publik  
**Detected Stack:** Laravel 13.17 + Inertia.js 3.0 + Vue 3.5 + TailwindCSS 4.1 + Pest 4.7

### Problem Statement
Pengunjung halaman publik (guest) ingin mengetahui secara cepat pertandingan mana saja yang **sedang dimainkan secara live / berlangsung saat ini** dari berbagai lomba tanpa harus membuka halaman detail tiap lomba satu per satu. Di sisi lain, Admin/Operator membutuhkan mekanisme sederhana (flag/switch) untuk menandai bahwa suatu pertandingan sedang dimulai (berlangsung) dan otomatis membatalkan flag tersebut saat skor akhir disimpan.

### Proposed Solution
Menambahkan kolom boolean `is_ongoing` pada tabel `competition_matches`. Admin dan Operator dapat mengaktifkan/mematikan toggle "Sedang Berlangsung" pada setiap match di halaman input skor. Pertandingan dengan `is_ongoing = true` dan status belum `completed` akan ditampilkan di **Section Paling Atas Halaman Beranda Publik (`/`)**. Begitu skor final diinput dan disimpan oleh Operator/Admin, status match berubah menjadi `completed` dan otomatis hilang dari section tersebut (tidak memerlukan live score angka per detik, hanya penanda pertandingan berlangsung).

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Meningkatkan keterlibatan pengunjung publik dengan memberikan informasi real-time mengenai pertandingan yang sedang berlangsung (*Ongoing Matches*). |
| **Target User** | **Pengunjung Publik** (melihat match ongoing), **Admin & Operator** (menandai match yang sedang dimainkan). |
| **Scope** | ✅ In scope (Penanda match ongoing + Section Beranda Publik). |
| **Dependency** | Fitur Manajemen Match (`competition_matches`), Halaman Skor Admin & Operator, dan Halaman Utama Publik (`Welcome.vue`). |

---

## 3. User Flow

### Happy Path:

```
[Admin/Operator di Halaman Skor] 
   └── Tekan Toggle / Button "Tandai Sedang Berlangsung" pada Match #X
         └── Sistem menyimpan `is_ongoing = true` via API Endpoint
               └── Match #X muncul di Section "🔥 Pertandingan Sedang Berlangsung" Halaman Utama Publik (/)
                     └── Match selesai dimainkan → Admin/Operator input skor final & simpan
                           └── Sistem menyimpan skor, ubah status = completed & set `is_ongoing = false`
                                 └── Match #X otomatis hilang dari Section Ongoing Halaman Utama Publik
```

### Edge Cases:
- [ ] **Kondisi Match Belum Siap (Pending/Bye/Belum Ada Tim):** Toggle "Sedang Berlangsung" dinonaktifkan (*disabled*) jika match belum memiliki lawan bertanding (`home` atau `away` masih null / status `pending` / `bye`).
- [ ] **Operator Mematikan Toggle Secara Manual:** Jika match ditunda/dibatalkan di tengah jalan sebelum input skor, Admin/Operator dapat mematikan toggle (`is_ongoing = false`) agar match hilang dari section publik.
- [ ] **Multi-Match Ongoing:** Jika ada beberapa pertandingan sedang berlangsung secara bersamaan dari berbagai lomba, semuanya akan muncul dalam grid/carousel responsive di section publik.
- [ ] **Lomba Terkunci Final:** Jika hasil pertandingan lomba sudah dikunci final oleh Admin (`is_results_locked = true`), toggle tidak dapat diubah lagi.

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] **Migration Database:** Menambahkan kolom `is_ongoing` (boolean, default: `false`) dan index `is_ongoing` pada tabel `competition_matches`.
- [ ] **API Controller & Route:** Route `POST /admin/competitions/{competition}/matches/{match}/toggle-ongoing` dan `POST /operator/competitions/{competition}/matches/{match}/toggle-ongoing` untuk menyalakan/mematikan status ongoing.
- [ ] **Auto-Reset saat Input Skor:** Saat skor final disimpan via `MatchScoreController@update`, `is_ongoing` otomatis diubah menjadi `false` dan `status` menjadi `completed`.
- [ ] **UI Halaman Skor (Admin & Operator):** Switch / Button toggle "Sedang Berlangsung" dengan indikator visual (badge hijau pulse "LIVE / Berlangsung") pada setiap kartu/baris match.
- [ ] **Section Beranda Publik (`Welcome.vue`):** Section khusus paling atas di halaman `/` dengan judul **"🔥 Pertandingan Sedang Berlangsung"** yang hanya tampil jika terdapat minimal 1 match dengan `is_ongoing = true` dan `status != completed`.
- [ ] **Kartu Match Ongoing Publik:** Menampilkan Nama Lomba, Ikon Olahraga SVG, Nama Tim Home vs Away, Waktu/Jadwal Pertandingan, Badge "Sedang Dimainkan", dan Link menuju Halaman Detail Lomba (`/lomba/{slug}`).

### Should Have
- [ ] Filter cepat di halaman skor Admin & Operator untuk menampilkan match yang sedang berstatus `is_ongoing`.
- [ ] Animasi pulse / indikator berkedip lembut pada kartu match ongoing di beranda publik.

### Won't Have (untuk versi ini)
- [ ] Live score streaming / pembaruan skor per menit (hanya berupa penanda pertandingan sedang dimainkan sesuai permintaan user).
- [ ] Websocket / Realtime push notification (halaman publik memperbarui list ongoing match saat dipuat ulang / polling Inertia opsional).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Query fetching ongoing matches di halaman publik ter-index (`is_ongoing = true`, `status != 'completed'`) dengan eksekusi < 50ms. |
| **Security** | Otorisasi toggle match ongoing terlindungi via Policy (`Gate::authorize('updateScore', $competition)`), hanya Admin atau Operator yang di-assign ke lomba tersebut yang berhak mengubah. |
| **Usability** | Desain responsif (Mobile-first & Desktop) menggunakan TailwindCSS v4 dan `shadcn-vue`. |
| **Data Integrity** | Saat match berstatus `completed`, flag `is_ongoing` secara otomatis diset ke `false`. |

---

## 6. UI/UX Notes (shadcn-vue Design)

### Touchpoints:
- `resources/js/pages/Welcome.vue`: Halaman Utama Publik (tambah section paling atas).
- `resources/js/pages/Admin/Competitions/Scores.vue`: Halaman Input Skor Admin (tambah toggle status ongoing).
- `resources/js/pages/Operator/Competitions/Scores.vue`: Halaman Input Skor Operator (tambah toggle status ongoing).

### ASCII Visual Wireframe (Beranda Publik - `/`):

```
+-----------------------------------------------------------------------+
|  🔥 PERTANDINGAN SEDANG BERLANGSUNG (2 Match)                         |
+-----------------------------------------------------------------------+
|  +---------------------------------+  +----------------------------+  |
|  | ⚽ Turnamen Sepak Bola Kaltim    |  | 🏀 Liga Basket SMA Utama   |  |
|  | [🔴 SEDANG DIMAINKAN]           |  | [🔴 SEDANG DIMAINKAN]      |  |
|  |                                 |  |                            |  |
|  |   BPMP A    VS    KGTK A        |  |   SMA 1     VS    SMA 3    |  |
|  |                                 |  |                            |  |
|  | 🕒 Lapangan Utama (15:00 WITA)  |  | 🕒 GOR Basket (16:30 WITA) |  |
|  | [ Lihat Detail Lomba -> ]       |  | [ Lihat Detail Lomba -> ]  |  |
|  +---------------------------------+  +----------------------------+  |
+-----------------------------------------------------------------------+
|  🏆 DAFTAR LOMBA TERSEDIA                                             |
|  ... (Content existing beranda)                                       |
+-----------------------------------------------------------------------+
```

---

## 7. Technical Plan

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Database | Add Migration | `database/migrations/YYYY_MM_DD_HHMMSS_add_is_ongoing_to_competition_matches_table.php` |
| Backend | Update Model & Enum | `app/Models/CompetitionMatch.php` |
| Backend | Update Controllers | `app/Http/Controllers/PublicCompetitionController.php`, `app/Http/Controllers/MatchScoreController.php` |
| Backend | Add Routes | `routes/web.php` (route `toggle-ongoing` di kelompok Admin & Operator) |
| Frontend | Update Page | `resources/js/pages/Welcome.vue` |
| Frontend | Update Pages | `resources/js/pages/Admin/Competitions/Scores.vue`, `resources/js/pages/Operator/Competitions/Scores.vue` |
| Testing | Add Pest Feature Test | `tests/Feature/OngoingMatchTest.php` |

### Backend — Laravel
- **Migration:**
  ```php
  Schema::table('competition_matches', function (Blueprint $table) {
      $table->boolean('is_ongoing')->default(false)->after('status');
      $table->index(['is_ongoing', 'status']);
  });
  ```
- **`app/Models/CompetitionMatch.php`:**
  - Tambah `'is_ongoing' => 'boolean'` di casts.
  - Tambah method `scopeOngoing($query)`: `where('is_ongoing', true)->where('status', '!=', CompetitionMatchStatus::Completed)`.
- **`app/Http/Controllers/MatchScoreController.php`:**
  - Method `toggleOngoing(Request $request, Competition $competition, CompetitionMatch $match)`:
    - Validasi otorisasi `updateScore`.
    - Cegah toggle jika `participant_id_home` atau `participant_id_away` null.
    - Toggle `$match->update(['is_ongoing' => ! $match->is_ongoing])`.
  - Method `update()` (input skor):
    - Saat skor disimpan, sertakan `'is_ongoing' => false` dan `'status' => CompetitionMatchStatus::Completed`.
- **`app/Http/Controllers/PublicCompetitionController.php`:**
  - Di method `index()`:
    - Fetch `$ongoingMatches` dengan relasi `competition`, `homeParticipant`, `awayParticipant`.
    - Format data untuk dikirim ke Inertia `Welcome.vue`.

### Frontend — Vue 3 / Inertia
- **`resources/js/pages/Welcome.vue`:**
  - Terima prop `ongoingMatches: Array<OngoingMatchItem>`.
  - Render section `v-if="ongoingMatches.length > 0"` di posisi paling atas di atas daftar lomba.
  - Gunakan `CompetitionSportIcon.vue` untuk ikon cabang olahraga.
- **`resources/js/pages/Admin/Competitions/Scores.vue` & `Operator/Competitions/Scores.vue`:**
  - Tambahkan tombol / switch toggle "Start / Ongoing" pada kartu/baris match.
  - Tampilkan badge "🔴 SEDANG DIMAINKAN" jika `match.is_ongoing = true`.

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Migration berhasil dijalankan dan menambahkan kolom `is_ongoing` ke `competition_matches`.
- [ ] Admin & Operator dapat menekan tombol toggle "Sedang Berlangsung" di halaman skor pertandingan.
- [ ] Match yang di-toggle `is_ongoing = true` langsung muncul pada Section "🔥 Pertandingan Sedang Berlangsung" di halaman utama publik (`/`).
- [ ] Begitu skor final diinput dan disimpan, match otomatis berstatus `completed` dan `is_ongoing` menjadi `false`, sehingga hilang dari section publik.
- [ ] Match yang belum memiliki lawan bertanding tidak dapat di-set sebagai `is_ongoing`.
- [ ] Seluruh unit/feature test di `tests/Feature/OngoingMatchTest.php` serta test suite existing (319+ test) lulus 100%.

---

## 9. Open Questions & Decided Specifications

- [x] **Apakah perlu live score?** Tidak, user menegaskan hanya memerlukan penanda match sedang berlangsung tanpa live score.
- [x] **Siapa yang berhak menyalakan flag?** Baik Admin maupun Operator yang ditugaskan ke lomba tersebut.
- [x] **Kapan match hilang dari section publik?** Otomatis hilang saat Operator/Admin menginput dan menyimpan skor final pertandingan, atau saat toggle di-nonaktifkan secara manual.

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 jam | Dokumen rencana fitur disetujui |
| Backend & Migration | 1.5 jam | Migration DB, update model, controller & route |
| Frontend Score Toggle | 1 jam | Tambah toggle ongoing di halaman skor Admin & Operator |
| Frontend Public Section | 1.5 jam | Redesign section ongoing match di `Welcome.vue` |
| Testing & Verification | 1 jam | Menulis Pest test & pengujian penuh |

**Total Estimasi:** ~5.5 Jam  
**Confidence:** High (Arsitektur dan komponen sudah tersedia secara matang di repository).

---

*Dokumen ini dibuat berdasarkan kebutuhan user dan disesuaikan dengan arsitektur Laravel + Inertia Vue pada repository.*
