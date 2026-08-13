# Feature: Jam / Waktu Pertandingan pada Match (Scheduled Match Time)

> Dokumen ini adalah rencana pengembangan fitur Jam / Waktu Pertandingan pada Match.  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 13 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Jam / Waktu Pertandingan (`scheduled_time`) pada Match  
**Status:** Draft  
**Priority:** Medium  
**Epic/Module:** Match Scheduling & Scoring (`Phase 03` & `Phase 04`)  
**Detected Stack:** Laravel 13 (PHP 8.4) + Inertia.js 3 + Vue 3.5 (TypeScript) + shadcn-vue & Tailwind CSS 4

### Problem Statement
Saat ini setiap pertandingan (`CompetitionMatch`) hanya memiliki informasi nomor ronde, leg, urutan sequence, dan peserta. Pengelola lomba (Admin/Operator) serta pengunjung publik tidak dapat melihat jadwal jam pelaksanaan pertandingan (misalnya: "09:00 WIB", "14:30", atau "Lapangan 1 - 10:00").

### Proposed Solution
Menambahkan kolom `scheduled_time` (bertipe `string` dan `nullable`) pada tabel `competition_matches`. Admin dan Operator dapat mengisi atau mengedit jam pertandingan pada setiap match. Jam pertandingan ini akan ditampilkan di halaman pengelolaan skor (Admin & Operator), preview undian, serta halaman detail lomba untuk publik.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Menyediakan informasi detail jadwal pertandingan yang mudah diakses oleh admin, operator, dan publik. |
| **Target User** | Admin Lomba, Operator Pencatat Skor, & Publik (Pengunjung). |
| **Scope** | ✅ In scope (Perluasan detail informasi match tanpa menambah sistem pencocokan benturan jam/lapangan yang kompleks). |
| **Dependency** | `CompetitionMatch` model, `MatchScoreController` / `CompetitionDrawController`, halaman Admin & Public views. |

---

## 3. User Flow

### Happy Path:
```
[Admin / Operator Buka Halaman Skor / Jadwal Match Lomba]
       ↓
[Lihat Daftar Pertandingan]
       ↓
[Isi / Edit Field "Jam Pertandingan" (opsional, contoh: "09:00 WIB" / "15:30")]
       ↓
[Simpan Detail Match / Skor]
       ↓
[Jam Pertandingan Terupdate di Database (scheduled_time)]
       ↓
[Publik Membuka Detail Lomba → Jam Pertandingan Tampil di Daftar Pertandingan]
```

### Edge Cases:
- [ ] **Kosong / Nullable:** Jika jam pertandingan tidak diisi (null), tampilan UI akan menampilkan badge "Belum diatur" atau tanda minus "—" tanpa merusak layout.
- [ ] **String Bebas:** Karena bertipe string, admin/operator bebas menuliskan format jam (misal: "09:00 WIB", "14:00 - 15:00", atau "Lap 2 / 10:00").

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] **Database Migration:** Migration baru untuk menambahkan kolom `scheduled_time` (`string`, `nullable`) pada tabel `competition_matches`.
- [ ] **Model & Controller Support:** `scheduled_time` ditambahkan pada `$fillable` `CompetitionMatch` dan divalidasi pada request update match/skor.
- [ ] **Input Jam Pertandingan di Admin & Operator:** Input field `scheduled_time` pada form update/edit match di halaman Admin Scores dan Operator Scores.
- [ ] **Tampilan Jam di Publik:** Menampilkan badge jam pertandingan pada daftar pertandingan di halaman Detail Lomba Publik (`PublicCompetitionController`).

### Should Have
- [ ] **Input Jam di Preview Undian (Draw Page):** Kemampuan mengatur jam pertandingan langsung dari halaman undian (`Admin/Competitions/Draw.vue`).

### Won't Have (untuk versi ini)
- [ ] Otomatisasi benturan jadwal / jam antar lapangan (*automated venue scheduling*).
- [ ] Integrasi ke Google Calendar / sistem iCal.

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Update jam pertandingan tidak mempengaruhi performa kalkulasi klasemen / bagan (< 100ms) |
| **Flexibility** | Format teks bebas (*freeform string*) memberikan fleksibilitas catatan jam / lapangan |
| **Security** | Otorisasi edit jam pertandingan dibatasi untuk Admin dan Operator yang ditugaskan |

---

## 6. UI/UX Notes (shadcn-vue Design)

### Touchpoints:
- **Admin & Operator Scores Page:** `resources/js/Pages/Admin/Competitions/Scores.vue` & `resources/js/Pages/Operator/Competitions/Scores.vue`
- **Public Competition Detail Page:** `resources/js/Pages/Public/CompetitionShow.vue`
- **Admin Draw Page:** `resources/js/Pages/Admin/Competitions/Draw.vue`
- **Komponen UI:** `Input`, `Badge`, `Clock` icon dari `@lucide/vue`.

### Visual Wireframe Snippet:
```
+-----------------------------------------------------------------------+
|  Match #1 (Ronde 1)                   [ Jam: 09:00 WIB ] [ Ready ]     |
|  Tim Garuda FC   [ 2 ]  VS  [ 1 ]   Elang Putih FC                  |
|  [ Edit Skor / Jam Pertandingan ]                                     |
+-----------------------------------------------------------------------+
```

---

## 7. Technical Plan

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Database | Migration `add_scheduled_time_to_competition_matches_table` | `database/migrations/` |
| Backend | Update `$fillable`, `MatchScoreRequest`, `MatchScoreController` | `app/Models/CompetitionMatch.php`, `app/Http/Requests/MatchScoreRequest.php`, `app/Http/Controllers/MatchScoreController.php` |
| Frontend | Update UI input & display jam pertandingan | `resources/js/Pages/Admin/Competitions/Scores.vue`, `resources/js/Pages/Operator/Competitions/Scores.vue`, `resources/js/Pages/Public/CompetitionShow.vue` |
| Testing | Update Pest feature tests untuk update match dengan `scheduled_time` | `tests/Feature/MatchScoreTest.php` |

### Backend — Laravel
- **Migration:**
  ```php
  Schema::table('competition_matches', function (Blueprint $table) {
      $table->string('scheduled_time')->nullable()->after('sequence');
  });
  ```
- **Validation in Request (`MatchScoreRequest.php`):**
  ```php
  'scheduled_time' => ['nullable', 'string', 'max:100'],
  ```

### API / Endpoints
| Method | Endpoint | Auth | Validasi | Deskripsi |
|--------|----------|------|----------|-----------|
| `POST` | `/admin/competitions/{competition}/matches/{match}/score` | Auth | `scheduled_time`: nullable, string, max:100 | Mengupdate skor dan jam pertandingan |
| `POST` | `/operator/competitions/{competition}/matches/{match}/score` | Auth (Operator) | `scheduled_time`: nullable, string, max:100 | Mengupdate skor dan jam pertandingan |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Migration berhasil dijalankan dan menambah kolom `scheduled_time` pada tabel `competition_matches`.
- [ ] Admin & Operator dapat mengisi, mengubah, atau mengosongkan jam pertandingan (`scheduled_time`).
- [ ] Jam pertandingan berhasil disimpan dan ditampilkan dengan rapi di halaman kelola skor Admin & Operator.
- [ ] Jam pertandingan tampil di halaman detail lomba publik bagi pengunjung.
- [ ] Seluruh unit & feature tests di Pest lulus 100% tanpa ada regression.

---

## 9. Open Questions & Decided Specifications

- [x] **Tipe Data:** String nullable (misal `VARCHAR(100)`) agar pengguna bebas memasukkan format waktu ("09:00 WIB", "15:00", "Lapangan 1 / 10:00").
- [x] **Timing Pengisian Jam Pertandingan:** Jam pertandingan dapat diisi/diubah kapan saja oleh Admin/Operator sebelum skor diisi (saat match status `ready` atau `pending`), maupun saat update skor, sehingga publik langsung mendapatkan informasi jadwal yang jelas sebelum pertandingan dimulai.

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Migration & Backend | 0.5 hari | Migration `scheduled_time`, update Model & Controller |
| Frontend UI (shadcn-vue) | 0.5 hari | Menambahkan input & badge tampilan jam di halaman Admin, Operator, & Publik |
| Testing & QA | 0.5 hari | Pest test run & build validation |

**Confidence:** High — Sangat terisolasi dan mudah diimplementasikan tanpa mempengaruhi logika perhitungan klasemen/bagan.
