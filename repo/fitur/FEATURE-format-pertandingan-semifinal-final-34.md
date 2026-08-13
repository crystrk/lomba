# Feature: Format Lomba Final Four (Semifinal, Final & Perebutan Juara Ke-3)

> Dokumen ini adalah rencana pengembangan fitur Format Lomba Khusus **Final Four** (dengan babak **SEMIFINAL**, **FINAL**, dan **PEREBUTAN JUARA Ke-3**).  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 13 Agustus 2026 (Finalized Spec)

---

## 1. Overview

**Nama Fitur:** Format Lomba Final Four (`final_four`)  
**Status:** Approved / Ready for Development  
**Priority:** High  
**Epic/Module:** Match Generation Engine & Competition Formats (`Phase 02`, `Phase 03` & `Phase 04`)  
**Detected Stack:** Laravel 13 (PHP 8.4) + Inertia.js 3 + Vue 3.5 (TypeScript) + shadcn-vue & Tailwind CSS 4 + Pest 4 + SQLite

### Problem Statement
Penyelenggara perlombaan membutuhkan format lomba tersendiri berbasis **Final Four** yang secara khusus menyediakan alur pertandingan babak **SEMIFINAL**, **FINAL** (Juara 1 & 2), serta **PEREBUTAN JUARA Ke-3** (Juara 3 & Peringkat 4). Format Sistem Gugur bawaan di PRD saat ini hanya memajukan pemenang ke babak Final tanpa memfasilitasi perebutan peringkat 3 dan 4 untuk tim yang kalah di semifinal.

### Proposed Solution
Menyediakan Format Lomba tersendiri bernama **"Final Four"** (`final_four`) pada aplikasi:
1. **Opsi Format Resmi:** Ditambahkan sebagai opsi format resmi ke-4 di aplikasi (selain *Sistem Gugur*, *Kompetisi Penuh*, dan *Setengah Kompetisi*).
2. **Dual Progression (Babak Puncak):**
   - **SEMIFINAL 1:** Tim A vs Tim B
   - **SEMIFINAL 2:** Tim C vs Tim D
   - **FINAL:** Pemenang SF 1 vs Pemenang SF 2 $\rightarrow$ Menentukan Juara 1 & Juara 2.
   - **PEREBUTAN JUARA Ke-3:** Kalah SF 1 vs Kalah SF 2 $\rightarrow$ Menentukan Juara 3 & Peringkat 4.
3. **Dukungan Ekspansi Peserta ($\ge 4$ Tim):**
   - **4 Peserta (Final Four Standar):** Langsung memainkan 2 match Semifinal $\rightarrow$ 1 match FINAL + 1 match PEREBUTAN JUARA Ke-3.
   - **> 4 Peserta (Kualifikasi $\rightarrow$ Final Four):** Pertandingan dimulai dari babak kualifikasi / perempat final ($8, 16, \dots$ tim). Tim yang lolos ke 4 besar otomatis memasuki skema Final Four di babak Semifinal.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Menambah pilihan format lomba resmi ke-4 bernama **Final Four** untuk pencatatan dan publikasi peringkat 1 s/d 4 yang fair dan otomatis. |
| **Target User** | Admin Lomba, Operator Skor, Pengunjung Publik. |
| **Scope** | ✅ Fitur Tambahan Terstruktur. Penambahan enum format `final_four` pada tabel `competitions`. |
| **Dependency** | `Competition` (`format = 'final_four'`), `CompetitionMatch` (`loser_next_match_id`, `loser_next_slot`, `match_type`), `DrawGenerator`, `UpdateMatchResultAction`, `KnockoutBracket.vue`. |

---

## 3. Skenario Ekspansi Peserta ($\ge 4$ Tim)

Format **Final Four** dapat menangani jumlah peserta berapa pun ($\ge 4$ tim) secara fleksibel:

### Skenario 1: 4 Tim (Final Four Murni)
- **Babak 1 (Semifinal):**
  - Semifinal 1: Tim A vs Tim B
  - Semifinal 2: Tim C vs Tim D
- **Babak 2 (Puncak):**
  - **FINAL:** Pemenang SF 1 vs Pemenang SF 2
  - **PEREBUTAN JUARA Ke-3:** Kalah SF 1 vs Kalah SF 2

### Skenario 2: 8 Tim (Kualifikasi Perempat Final $\rightarrow$ Final Four)
- **Babak 1 (Perempat Final - 4 Match):** 8 tim bertanding. 4 tim pemenang maju ke Final Four (Semifinal), 4 tim kalah tereliminasi.
- **Babak 2 (Semifinal - 2 Match):** 4 tim terbaik bertanding di Semifinal 1 & 2.
- **Babak 3 (Puncak - 2 Match):**
  - **FINAL:** Pemenang SF 1 vs Pemenang SF 2
  - **PEREBUTAN JUARA Ke-3:** Kalah SF 1 vs Kalah SF 2

### Skenario 3: Jumlah Peserta Ganjil / Dengan Bye (Contoh: 5, 6, atau 7 Tim)
- Slot bagan dibuat sesuai pangkat dua terdekat (8 slot). Tim dengan `Bye` langsung melaju ke babak berikutnya hingga tersisa 4 tim di Semifinal.
- Saat mencapai 4 tim tersisa, alur pemenang menuju **FINAL** dan alur tim kalah menuju **PEREBUTAN JUARA Ke-3**.

---

## 4. User Flow & Nomenclature

### Visual Structure:
```
[ BABAK SEMIFINAL ]

SEMIFINAL 1
[ Tim A vs Tim B ] ───┬── Pemenang SF 1 ──┐
                      │                   ├───> [ FINAL ] ─────────────────> Juara 1 & Juara 2
SEMIFINAL 2           │   Pemenang SF 2 ──┘
[ Tim C vs Tim D ] ───┼──┐
                      │  │
                      └──┼── Kalah SF 1 ────┐
                         │                  ├───> [ PEREBUTAN JUARA Ke-3 ] ──> Juara 3 & Peringkat 4
                         └── Kalah SF 2 ────┘
```

---

## 5. Functional Requirements

### Must Have (MVP)
- [ ] **Pilihan Format Lomba "Final Four":** Opsi format `final_four` pada dropdown pilihan format saat Admin membuat/mengedit lomba.
- [ ] **Dual Progression Engine:**
  - `winner_id` dari Semifinal 1 & 2 dipromosikan ke match **FINAL**.
  - `loser_id` dari Semifinal 1 & 2 dialokasikan ke match **PEREBUTAN JUARA Ke-3**.
- [ ] **Match Type Classifier:** Indikator `match_type` pada record match: `standard`, `semifinal`, `final`, dan `third_place`.
- [ ] **Visual Bracket Header & Card UI:**
  - Tampilan bagan publik dan admin memisahkan 2 match akhir di babak puncak dengan judul tegas: **FINAL** dan **PEREBUTAN JUARA Ke-3**.
  - Sebelum skor Semifinal terisi, placeholder di kartu puncak menampilkan:
    - FINAL: `Pemenang SF 1` vs `Pemenang SF 2`
    - PEREBUTAN JUARA Ke-3: `Kalah SF 1` vs `Kalah SF 2`
- [ ] **Standings / Podium Result:** Menghasilkan urutan peringkat otomatis setelah match FINAL dan PEREBUTAN JUARA Ke-3 selesai: Juara 1, Juara 2, Juara 3, dan Peringkat 4.

### Should Have
- [ ] **Badge/Icon Penanda Match Puncak:** Visual Emas 🥇 pada match `FINAL` dan Perunggu 🥉 pada match `PEREBUTAN JUARA Ke-3` di dashboard admin dan halaman publik.

---

## 6. Technical Plan

### Database & Schema Updates
1. **Model `Competition`:**
   - Enum `format`: `'knockout'`, `'full_competition'`, `'half_competition'`, `'final_four'`.

2. **Model `CompetitionMatch`:**
   ```php
   Schema::table('competition_matches', function (Blueprint $table) {
       $table->foreignId('loser_next_match_id')->nullable()->constrained('competition_matches')->nullOnDelete();
       $table->unsignedTinyInteger('loser_next_slot')->nullable(); // 1 = Home, 2 = Away
       $table->string('match_type')->default('standard'); // 'standard', 'semifinal', 'final', 'third_place'
   });
   ```

3. **DrawGenerator (`app/Services/DrawGenerator.php`):**
   - Saat membentuk bagan untuk format `final_four`:
     - Di babak terakhir (`round = total_rounds`), buat 2 pertandingan:
       - Match 1: `match_type = 'final'` (Label: **FINAL**)
       - Match 2: `match_type = 'third_place'` (Label: **PEREBUTAN JUARA Ke-3**)
     - Pada 2 match di babak Semifinal (`round = total_rounds - 1`):
       - Assign `next_match_id` ke Match FINAL (slot 1 & 2).
       - Assign `loser_next_match_id` ke Match PEREBUTAN JUARA Ke-3 (slot 1 & 2).

4. **Update Match Result Action (`app/Actions/UpdateMatchResultAction.php`):**
   - Ketika match Semifinal selesai:
     - Promosikan `$winnerId` ke `$match->next_match_id` slot `$match->next_slot`.
     - Promosikan `$loserId` ke `$match->loser_next_match_id` slot `$match->loser_next_slot`.

---

## 7. Acceptance Criteria

- [ ] Admin dapat memilih format **Final Four** saat membuat atau mengedit lomba.
- [ ] Sistem mendukung pembuatan bagan Final Four untuk **4 tim** maupun **> 4 tim** (8, 16 tim atau dengan Bye).
- [ ] Pada babak puncak, bagan secara eksplisit menampilkan dua blok pertandingan: **FINAL** dan **PEREBUTAN JUARA Ke-3**.
- [ ] Menyimpan skor Semifinal otomatis mengirimkan pemenang ke match **FINAL** dan tim kalah ke match **PEREBUTAN JUARA Ke-3**.
- [ ] Publik & Admin dapat melihat hasil akhir podium Juara 1, 2, 3, dan Peringkat 4.
- [ ] Test Pest (`tests/Feature/Draw/FinalFourDrawTest.php`) lulus 100%.

---

## 8. Finalized Decisions

- **Nama Format Resmi:** `Final Four` (Value DB: `final_four`).
- **Label Babak Puncak:** `FINAL` dan `PEREBUTAN JUARA Ke-3`.
- **Ekspansi Peserta:** Mendukung $\ge 4$ tim dengan babak kualifikasi otomatis sebelum Semifinal.
