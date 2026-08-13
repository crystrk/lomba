# Feature: Format Babak Grup Penyisihan & Final Placement — "Group Four Final"

> Dokumen ini adalah rencana pengembangan fitur format pertandingan **Group Four Final (2 Grup Round-Robin + Playoff Final 1-2 & Final 3-4)**.  
> Dibuat berdasarkan PRD: `repo/PRD.md` | Dikerjakan & Diperbarui: 13 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Group Four Final (Format 2 Grup + Final Placement 1-4)  
**Enum Backend:** `group_four_final` (`CompetitionFormat::GROUP_FOUR_FINAL`)  
**Status:** Approved / Ready for Dev  
**Priority:** High  
**Epic/Module:** Manajemen Format Lomba & Auto Match Generation  
**Detected Stack:** Laravel 13.17 (PHP 8.4) + Inertia.js 3.0 + Vue 3.5 (TypeScript) + Tailwind CSS 4.1 + SQLite + Pest PHP 4.7

### Problem Statement
Sistem manajemen lomba memerlukan dukungan format pertandingan 2 grup dengan nama resmi **"Group Four Final"**. Format ini membagi peserta berjumlah genap dan lebih dari 4 tim ($N > 4$, yaitu minimal 6 tim: 6, 8, 10, dst.) menjadi 2 grup ($n = N/2$ tim per grup). Setelah babak penyisihan *single round-robin* selesai, 4 tim terbaik (Juara & Runner-up tiap grup) melaju ke babak **Final Four Placement**:
- **Final 3–4:** Runner-up Grup A vs Runner-up Grup B (Perebutan Tempat Ke-3)
- **Final 1–2:** Juara Grup A vs Juara Grup B (Grand Final / Perebutan Juara 1)

Jika jumlah tim $\le 4$ atau berjumlah ganjil, sistem menampilkan **warning alert** dan memblokir proses pengundian grup (shuffle/draw) serta pembuatan jadwal pertandingan.

### Proposed Solution
Menambahkan opsi format lomba baru `group_four_final` dengan validasi:
1. **Prasyarat Tim ($N > 4$ dan $N \% 2 == 0$):**
   - Minimum 6 tim dan genap (6, 8, 10, 12, dst.).
   - **Warning & Block:** Jika $N \le 4$ atau ganjil (misal 3, 4, 5, 7 tim), tombol *Generate Undian/Jadwal* dinonaktifkan dengan alert: *"Format Group Four Final memerlukan minimal 6 tim dan jumlah tim harus genap."*
2. **Pembagian 2 Grup (Grup A & B):** Membagi $N$ tim genap menjadi Grup A ($N/2$) dan Grup B ($N/2$) secara acak (shuffle) atau manual oleh Admin.
3. **Generator Match Penyisihan ($n \times (n-1)$ match):**
   - Round-robin per grup.
   - Skenario 6 tim ($n=3$): 3 match Grup A + 3 match Grup B = 6 match penyisihan.
4. **Kalkulasi Klasemen per Grup:** Menghitung poin & statistik klasemen terpisah untuk Grup A dan Grup B.
5. **Manual Rank Adjuster (Tie-Breaker):** Fitur manual penentu urutan peringkat grup jika statistik tim seri sempurna.
6. **Pembuatan 2 Match Final (Auto-Progression Four Final):**
   - **Final 3–4:** Runner-up Grup A vs Runner-up Grup B
   - **Final 1–2:** Juara Grup A vs Juara Grup B
7. **Total Pertandingan:** $[(N/2) \times (N/2 - 1)] + 2$ pertandingan ($8$ match untuk 6 tim).

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Fleksibilitas format pertandingan multi-grup, otomatisasi jadwal & klasemen, serta transparansi informasi untuk publik. |
| **Target User** | **Admin** (membuat lomba `Group Four Final`, shuffle grup, adjust tie-breaker jika seri), **Operator** (input skor match), **Publik** (melihat klasemen & bracket final). |
| **Scope** | ⚠️ **Ekstensi MVP PRD 5.2** — Menyempurnakan non-goal MVP asli menjadi fitur format resmi `Group Four Final`. |
| **Dependency** | Modul `Competitions`, `Teams`, `Matches`, `StandingsCalculator`, `MatchGeneratorService`, serta halaman Inertia Vue detail lomba. |

---

## 3. User Flow

### Happy Path:
```
[Admin] Buat Lomba (Pilih Format: "Group Four Final") 
   → [Admin] Daftarkan N Tim (N > 4 dan N Genap, misal 6 tim)
   → [Sistem] Validasi Lulus (N >= 6 & Genap) → Tampilkan Panel Undian & Jadwal
   → [Admin] Shuffle / Manual Assign Tim ke Grup A (N/2 tim) & Grup B (N/2 tim)
   → [Sistem] Generate Match Penyisihan Round-Robin per Grup + 2 Match Final Placeholder
   → [Admin] Kunci Lomba (Lock Competition)
   → [Operator] Input Skor Seluruh Match Babak Penyisihan
   → [Sistem] Hitung Klasemen Grup A & Klasemen Grup B
   → (Jika Tie) [Admin/Operator] Jalankan Manual Rank Adjuster untuk menentukan Juara & Runner-up Grup
   → [Sistem] Otomatis isi peserta Final 3-4 (Runner-Up A vs B) & Final 1-2 (Juara A vs B)
   → [Operator] Input Skor Match Final 3-4 & Final 1-2 (dengan Pemenang Tie-Break jika skor seri)
   → [Sistem] Tentukan Peringkat Akhir (Juara 1, Juara 2, Juara 3, Juara 4) & Tampilkan ke Publik
```

### Edge Cases / Validation Flow:
- [ ] **Jumlah Tim $\le 4$ atau Ganjil (Terhalang Warning):**  
  Ketika Admin membuka halaman *Undian & Jadwal* format **Group Four Final** dengan jumlah tim misal 4 atau 5 tim:
  - Tampilkan Alert Peringatan (Kuning/Merah):  
    > ⚠️ **Format Tidak Sesuai:** Format **Group Four Final** membutuhkan MINIMAL **6 TIM** dan jumlah tim HARUS **GENAP** (6, 8, 10, dst.). Saat ini terdaftar **[Jumlah Tim] tim**. Silakan tambahkan/sesuaikan tim terlebih dahulu.
  - Tombol **"Acak Undian (Shuffle)"** dan **"Kunci & Buat Jadwal"** dalam keadaan **Disabled**.
- [ ] **Tie-Breaker Klasemen Group:**  
  Sistem menampilkan badge *“Tie-breaker Required”*. Admin/Operator dapat membuka dialog *Manual Rank Adjuster* untuk menetapkan posisi Peringkat 1 dan 2 secara manual sebelum peserta Final di-generate.

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] **Opsi Format Lomba Baru:** Penambahan jenis format lomba `"Group Four Final"` (`group_four_final`) pada form pembuatan & edit lomba.
- [ ] **Prasyarat Validasi Tim ($N > 4$ & $N \% 2 == 0$):** Total tim registered $> 4$ (minimal 6 tim) dan kelipatan 2 (genap).
- [ ] **Warning Alert & Blocking UI:** Warning banner & disable button jika tim $\le 4$ atau ganjil.
- [ ] **Pembagian Grup & Shuffle:** Drag-and-drop / auto-shuffle tim ke Grup A ($N/2$) & Grup B ($N/2$).
- [ ] **Generator Match Penyisihan:** Round-robin per grup.
- [ ] **Klasemen Terpisah per Grup:** Tabel Klasemen Grup A & Klasemen Grup B.
- [ ] **Manual Rank Adjuster (Admin/Operator):** Modal override urutan peringkat grup jika statistik tim seri sempurna.
- [ ] **Auto-Progression Babak Final (Four Final):**
  - Match Final 3–4: `Runner-up Grup A` vs `Runner-up Grup B`.
  - Match Final 1–2: `Juara Grup A` vs `Juara Grup B`.
- [ ] **Tanpa Match Perebutan Tempat 5-6:** Hanya memutar Final 1-2 & 3-4.
- [ ] **Visualisasi UI Publik:** Tab Penyisihan Grup A & B, Tab Final Placement, serta Peringkat Pemenang 1-4.

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Generasi jadwal & kalkulasi klasemen $N \le 16$ tim diproses dalam `< 100ms`. |
| **Security** | Manual Rank Adjuster hanya bisa diakses role Admin/Operator lomba terkait. |
| **Scalability** | Rumus match penyisihan $n(n-1)$ dan 2 final match berjalan secara dinamis. |
| **Availability** | Data klasemen & jadwal dapat diakses publik 24/7 tanpa N+1 query. |

---

## 6. UI/UX Notes

### Touchpoints
- [ ] **Form Create/Edit Competition:** Dropdown / Radio option: `Group Four Final`.
- [ ] **Halaman Manage Undian / Shuffle (`ManageGroups.vue` / `Show.vue`):** Banner Warning jika tim $< 6$ atau ganjil, tombol shuffle disabled.
- [ ] **Modal Manual Rank Adjuster:** Modal dialog tukar posisi klasemen grup.
- [ ] **Halaman Publik Lomba (`Public/Show.vue`):** Label format **Group Four Final**, Tab Klasemen Grup A/B, Tab Final, & Podium Pemenang (Juara 1 s.d. 4).

### Wireframe ASCII (Warning State pada Undian & Jadwal)

```
========================================================================
 LOMBA: TURNAMEN BOLA VOLLI (Format: Group Four Final)
 Status: DRAFT | Total Tim Terdaftar: 5 Tim
========================================================================

+----------------------------------------------------------------------+
| ⚠️ WARNING: FORMAT TIDAK MEMENUHI SYARAT                             |
| Format "Group Four Final" membutuhkan MINIMAL 6 TIM dan jumlah tim   |
| HARUS GENAP (6, 8, 10, dst.).                                        |
| Tim terdaftar saat ini: 5 Tim (Ganjil).                              |
|                                                                      |
| -> Silakan tambahkan atau sesuaikan jumlah tim sebelum melanjutkan. |
+----------------------------------------------------------------------+

[ Tombol Shuffle: DISABLED ]   [ Tombol Kunci & Buat Jadwal: DISABLED ]
========================================================================
```

---

## 7. Technical Plan

### Validation Logic & Enum
- **Enum Definition (`CompetitionFormat.php`):**
  ```php
  enum CompetitionFormat: string
  {
      case SINGLE_ELIMINATION = 'single_elimination';
      case DOUBLE_ROUND_ROBIN = 'double_round_robin';
      case SINGLE_ROUND_ROBIN = 'single_round_robin';
      case GROUP_FOUR_FINAL = 'group_four_final';
  }
  ```
- **Backend Action (`LockCompetitionAction.php`):**
  ```php
  if ($competition->format === CompetitionFormat::GROUP_FOUR_FINAL->value) {
      $teamCount = $competition->teams()->count();
      if ($teamCount <= 4 || $teamCount % 2 !== 0) {
          throw ValidationException::withMessages([
              'teams' => "Format Group Four Final memerlukan minimal 6 tim dan jumlah tim harus genap (6, 8, 10, dst.).",
          ]);
      }
  }
  ```

### Database Schema Expansion
```php
Schema::table('competition_team', function (Blueprint $table) {
    $table->string('group_name')->nullable()->after('team_id'); // 'A', 'B'
    $table->integer('manual_rank_override')->nullable();
});

Schema::table('matches', function (Blueprint $table) {
    $table->string('group_name')->nullable(); // 'A', 'B', null for finals
    $table->string('stage')->default('group'); // 'group', 'final'
    $table->string('match_type')->nullable(); // 'regular', 'final_12', 'final_34'
    $table->string('placeholder_home')->nullable(); // e.g. "Juara Grup A"
    $table->string('placeholder_away')->nullable(); // e.g. "Juara Grup B"
});
```

### Testing Strategy (Pest PHP 4.7)
- **Feature Test Validation (`tests/Feature/GroupFourFinalValidationTest.php`):**
  - Memastikan penguncian lomba `group_four_final` dengan 4 atau 5 tim melempar error validasi.
  - Memastikan penguncian lomba dengan 6 tim menghasilkan 6 match penyisihan + 2 match final (8 match total).
- **Unit Test Generator Matches (`tests/Unit/GroupFourFinalMatchGeneratorTest.php`):**
  - Menguji generator match dinamis untuk 6 tim, 8 tim, dan 10 tim.

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Opsi format lomba dinamai **"Group Four Final"** (`group_four_final`) di seluruh UI Admin & Publik.
- [ ] Form penguncian & pembuatan undian menolak dan menampilkan **warning alert** jika tim $\le 4$ atau **ganjil**.
- [ ] Tombol *Acak Undian* & *Kunci & Buat Jadwal* secara visual ter-disable saat warning aktif.
- [ ] Jika jumlah tim genap dan $> 4$ (misal 6 tim), sistem menghasilkan $[(N/2) \times (N/2 - 1)] + 2$ pertandingan.
- [ ] Admin/Operator dapat me-override peringkat grup secara manual jika statistik 2 tim seri persis (*manual tie-breaker*).
- [ ] Setelah penyisihan selesai, peserta Final 1-2 (Juara A vs B) dan Final 3-4 (Runner-up A vs B) otomatis terisi.
- [ ] Seluruh unit & feature test Pest lulus (100% pass).

---

## 9. Open Questions & Decided Items

- [x] **Nama Resmi Format:** **Group Four Final** (slug: `group_four_final`).
- [x] **Prasyarat Jumlah Tim:** **Minimal 6 Tim & Harus Genap ($N > 4$ dan $N \% 2 == 0$)**.
- [x] **Behavior saat Tidak Memenuhi Syarat:** **Warning Alert & Blocking UI** pada halaman pengundian & pembuat jadwal.
- [x] **Match Perebutan Tempat 5-6:** **TIDAK PERLU** (hanya Final 1-2 dan Final 3-4).
- [x] **Mekanisme Tie-Breaker Klasemen:** **MANUAL ADJUSTER** oleh Admin/Operator.

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | Finished | Penamaan "Group Four Final" & validasi disetujui |
| Backend & Validation Logic | 2 hari | Enum `GROUP_FOUR_FINAL`, Validator $N \ge 6$, Dynamic Generator, Pest Tests |
| Frontend Admin/Operator | 2 hari | Display label "Group Four Final", warning banner, disabled button states, Manual Rank Adjuster modal |
| Frontend Public View | 1 hari | Tab Klasemen Grup A/B & Four Final placement card |
| Testing & UAT | 1 hari | Full flow test format Group Four Final |

**Confidence:** High — Nama format **Group Four Final** sangat deskriptif dan mencerminkan babak penyisihan 2 grup yang bermuara pada 4 tim terbaik di babak final.
