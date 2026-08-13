# Feature: Undian Jadwal Pertandingan (Manual Sort & Random Draw)

> Dokumen ini adalah rencana pengembangan fitur Undian Jadwal Pertandingan (Manual Sort & Random Draw).  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 13 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Undian & Pengurutan Manual Jadwal Pertandingan (Match Schedule Draw & Manual Sorting)  
**Status:** Draft  
**Priority:** High  
**Epic/Module:** Admin Competition Management & Draw Engine (`Phase 03`)  
**Detected Stack:** Laravel 13 (PHP 8.4) + Inertia.js 3 + Vue 3.5 (TypeScript) + shadcn-vue & Tailwind CSS 4

### Problem Statement
Saat ini admin hanya dapat mengacak (shuffle) urutan peserta secara random untuk menghasilkan jadwal pertandingan (draw). Namun dalam banyak skenario kompetisi, admin membutuhkan kemampuan untuk mengatur urutan posisi undian peserta secara manual (*manual seeding / manual draw ordering*) — misalnya menempatkan tim unggulan di posisi tertentu atau menyusun urutan peserta sesuai hasil kualifikasi/kehadiran teknikal meeting — sebelum jadwal pertandingan resmi dikunci.

### Proposed Solution
Menyediakan dual-mode pada fitur Undian Pertandingan: Mode **Acak Otomatis (Random Shuffle)** dan Mode **Urutkan Manual (Manual Reordering / Drag-and-Drop Sort)** menggunakan komponen UI berbasis **shadcn-vue**. Admin dapat memindahkan posisi peserta (drag & drop / button move up-down) untuk memperbarui `draw_position`, kemudian backend secara otomatis meregenerasi preview jadwal pertandingan sesuai format lomba (sistem gugur, setengah kompetisi, kompetisi penuh) secara deterministik dan transaksional.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Memungkinkan admin menyusun susunan undian peserta dan menghasilkan jadwal pertandingan yang fair dan transparan sebelum dikunci. |
| **Target User** | Admin Lomba |
| **Scope** | ✅ In scope (Perluasan kemampuan FR-DRAW-01 s/d FR-DRAW-06 untuk mendukung pengurutan manual selain shuffle) |
| **Dependency** | `Participant` model (kolom `draw_position`), `DrawGenerator` (`knockout`, `half_competition`, `full_competition`), `CompetitionMatch` model, `CompetitionDrawController`. |

---

## 3. User Flow

### Happy Path:
```
[Admin Buka Halaman Draw Lomba]
       ↓
[Pilih Mode Pengundian: Acak Otomatis / Urutkan Manual]
       ↓
(Jika Urutkan Manual) → [Drag-and-Drop / Ubah Posisi Peserta di Daftar UI shadcn-vue]
       ↓
[Klik "Simpan Urutan & Regenerasi Jadwal"]
       ↓
[Backend Memvalidasi + Memperbarui draw_position Peserta + Regenerasi CompetitionMatch via DrawGenerator]
       ↓
[Tampilan Preview Jadwal Pertandingan Berubah Sesuai Urutan Baru]
       ↓
[Admin Review Preview Jadwal & Bagan]
       ↓
[Klik "Kunci Undian" → Status Lomba Menjadi 'locked']
```

### Edge Cases:
- [ ] **Perselisihan draw_version / Stale Request:** Jika ada dua admin yang mengubah urutan atau mengacak di tab berbeda, sistem memvalidasi `draw_version` agar data tidak saling menimpa.
- [ ] **Pengubahan Peserta Saat Status 'drawn':** Jika peserta ditambah/dihapus/diubah saat status 'drawn', preview undian dibatalkan dan status kembali ke 'draft'.
- [ ] **Percobaan Drag & Drop pada Lomba Terkunci ('locked' / 'in_progress'):** UI menonaktifkan mode pengurutan manual dan menyembunyikan aksi reorder bila status lomba sudah dikunci.
- [ ] **Peserta kurang dari 2:** Tombol simpan/acak/sort di-disable dengan petunjuk bahwa minimal 2 peserta diperlukan.

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] **Dual Control Sorting Interface (shadcn-vue):** Komponen UI pengurutan posisi peserta (*sortable list*) yang mendukung interaksi **Drag & Drop** (drag handle `GripVertical`) DAN **tombol naik/turun** (`ArrowUp`/`ArrowDown`) berbasis shadcn-vue `Card`/`Badge`/`Button`.
- [ ] **Instant Live Client-Side Preview:** Rekalkulasi visual preview jadwal pertandingan secara instan (*real-time reactive*) di frontend Vue saat urutan peserta berubah, sebelum disimpan ke server.
- [ ] **Preset Reordering Actions:** Tombol quick-action untuk mengurutkan secara alfabetis (A-Z) atau mereset ke urutan pendaftaran awal.
- [ ] **Transactional Manual Draw Endpoint:** Endpoint POST `admin/competitions/{competition}/reorder` yang menerima array ID peserta beserta `draw_position` baru.
- [ ] **Deterministic Match Preview Generation:** `DrawGenerator` memproses urutan ID peserta hasil pengurutan manual untuk menghasilkan match schedule yang stabil dan deterministik di server.
- [ ] **Draw Versioning & Concurrency Control:** Penambahan `draw_version` increment setiap kali urutan manual disimpan atau diacak ulang.
- [ ] **Visual Feedback:** Status indikator "Ada Perubahan Belum Disimpan" dan notifikasi sukses (*toast*) setelah berhasil menyimpan.

### Should Have
- [ ] **Keyboard Accessibility for Reordering:** Dukungan navigasi keyboard (Up/Down arrow key / alt+arrow) untuk memindahkan item urutan peserta.
- [ ] **Unsaved Changes Guard:** Warning alert bila admin mengubah urutan drag-and-drop namun mencoba meninggalkan halaman tanpa menyimpan.

### Won't Have (untuk versi ini)
- [ ] Live WebSocket collaborative sorting (Multi-user realtime co-sorting).
- [ ] Rule constraint automated check (misalnya: melarang tim dari grup yang sama bertemu di ronde 1 secara otomatis).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Waktu respon simpan urutan manual & regenerasi match preview < 1 detik untuk 64 peserta |
| **Security** | Otorisasi via Gate `can:update` competition + middleware `can:admin-access` |
| **Scalability** | Menangani hingga 128 peserta per lomba tanpa browser rendering lag |
| **Availability** | Penanganan transaksi atomik (`DB::transaction`) untuk mencegah inconsistency antara participant `draw_position` dan record `CompetitionMatch` |

---

## 6. UI/UX Notes (shadcn-vue Design)

### Touchpoints:
- **Halaman Utama:** `resources/js/Pages/Admin/Competitions/Draw.vue`
- **Komponen UI Baru / Diperbarui:**
  - `resources/js/Components/Admin/ParticipantSortableList.vue` (Komponen sorting list peserta berbasis shadcn-vue Card & Reorder handle)
  - `resources/js/components/ui/card`, `button`, `badge`, `dialog`, `toast` (shadcn-vue primitives)

### ASCII Wireframe UI:
```
+-----------------------------------------------------------------------------------+
|  < Kembali ke Lomba         Undian & Jadwal Pertandingan       [Diundi] [v2]     |
+-----------------------------------------------------------------------------------+
|  [ Format: Knockout ]      [ 8 Peserta ]                [ 7 Match Total ]         |
+-----------------------------------------------------------------------------------+
|  Mode Pengundian:                                                                 |
|  (o) Urutkan Manual (Drag & Drop)    ( ) Acak Otomatis (Shuffle)                  |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | Draf Urutan Peserta (Draw Positions)                [ Urutkan A-Z ] [ Reset ] |  |
|  +-----------------------------------------------------------------------------+  |
|  | [::: GRID] Pos 1: Tim Garuda FC                        [^] [v] [ Atas ]     |  |
|  | [::: GRID] Pos 2: Elang Putih FC                       [^] [v]              |  |
|  | [::: GRID] Pos 3: Rajawali FC                          [^] [v]              |  |
|  | [::: GRID] Pos 4: Harimau Sumatra                      [^] [v] [ Bawah ]    |  |
|  +-----------------------------------------------------------------------------+  |
|  | [ Simpan Urutan & Generasi Jadwal ]    [ Batal / Reset Urutan ]               |  |
|  +-----------------------------------------------------------------------------+  |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | Preview Jadwal Pertandingan (Ronde 1, Ronde 2, dst.)                       |  |
|  +-----------------------------------------------------------------------------+  |
|  | Ronde 1:                                                                       |  |
|  |   #1 Tim Garuda FC   VS   Elang Putih FC                                     |  |
|  |   #2 Rajawali FC      VS   Harimau Sumatra                                  |  |
|  +-----------------------------------------------------------------------------+  |
|                                                                                   |
|  [ Acak Ulang ]                                              [ Kunci Undian ]     |
+-----------------------------------------------------------------------------------+
```

---

## 7. Technical Plan

### Existing Architecture
- **Framework backend:** Laravel 13, Controllers di `App\Http\Controllers\Admin\CompetitionDrawController`
- **Draw Generator Engine:** `App\Generators\DrawGenerator` mendukung `knockout`, `half_competition`, `full_competition`.
- **Frontend Layer:** Inertia v3 + Vue 3 + TypeScript. Router via `@/routes/admin/competitions`.
- **UI System:** `@/components/ui/` berbasis Tailwind v4, `@lucide/vue` icons, `reka-ui`.

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Backend | Tambah method `reorder` di controller & route POST | `app/Http/Controllers/Admin/CompetitionDrawController.php`, `routes/web.php` |
| Frontend | Buat interaksi sorting manual dengan HTML5 drag-and-drop / sortable list & Shadcn-vue components | `resources/js/Pages/Admin/Competitions/Draw.vue`, `resources/js/Components/Admin/ParticipantSortableList.vue` |
| Testing | Tambah Feature Test untuk manual reordering & update draw_position | `tests/Feature/Admin/CompetitionDrawTest.php` |

### Backend — Laravel
- **Route:** `POST /admin/competitions/{competition}/reorder` (`admin.competitions.reorder`)
- **Controller Logic (`CompetitionDrawController::reorder`):**
  1. Authorize Gate `update` pada competition.
  2. Validasi status competition `draft` atau `drawn`.
  3. Validasi payload `participant_ids` (array of integer, minimum 2 item, harus persis mencakup semua ID peserta yang terdaftar di lomba tersebut tanpa duplikat).
  4. Jalankan `DB::transaction()`:
     - Update kolom `draw_position` pada masing-masing participant (`index + 1`).
     - Jalankan `DrawGenerator::generate($competition->format, $orderedParticipantIds)`.
     - Hapus `$competition->matches()` lama, masukkan `CompetitionMatch` baru hasil generator.
     - Hubungkan `next_match_id` untuk format knockout.
     - Update competition `status` menjadi `drawn` dan increment `draw_version`.
  5. Return Inertia back redirect dengan flash success message.

### Frontend — Inertia + Vue 3 (shadcn-vue)
- **Komponen Sortable UI & Controls:**
  - Interaktif list dengan Drag Handle (`GripVertical`), tombol Naik/Turun (`ArrowUp`/`ArrowDown`), tombol Quick-Move (Ke Paling Atas / Ke Paling Bawah), serta Quick Action (Urutkan A-Z / Reset).
  - Komponen shadcn-vue yang digunakan: `Card`, `Button`, `Badge`, `Dialog`, `Tooltip`, `DropdownMenu`.
  - Icon dari `@lucide/vue`: `GripVertical`, `ArrowUp`, `ArrowDown`, `ChevronsUp`, `ChevronsDown`, `RotateCcw`, `Check`, `Shuffle`, `Lock`.
- **Instant Client-Side Live Preview Generator:**
  - Utility/Composable `generateClientPreview(format, orderedParticipants)` di TypeScript yang mengacak/meregenerasi susunan ronde & match preview secara instan (*computed property* / watcher) di browser begitu urutan peserta digeser via Drag & Drop atau tombol Naik/Turun.
- **State Management & Persistence:**
  - Local state `orderedParticipants` (reactive copy dari props `participants`).
  - Computed flag `isDirty` bila urutan lokal berbeda dari server.
  - Form Inertia `useForm` untuk mengirim payload `{ participant_ids: [...] }` ke endpoint `/reorder`.

### API / Endpoints
| Method | Endpoint | Auth | Validasi | Deskripsi |
|--------|----------|------|----------|-----------|
| `POST` | `/admin/competitions/{competition}/reorder` | Auth (Admin) | `participant_ids`: array, required, exists | Memperbarui urutan manual undian dan meregenerasi match schedule |

### Security & Privacy
- [ ] Gate `update` competition dipastikan aktif.
- [ ] Validasi payload memastikan ID peserta yang dikirim cocok 1-to-1 dengan peserta di lomba target (mencegah penyesuaian peserta lomba lain).
- [ ] Transactional atomic execution mencegah corrupt match data.

### Testing Strategy
| Level | Skenario | Tool Existing |
|-------|----------|---------------|
| Backend Feature Test | Testing `POST /admin/competitions/{competition}/reorder` memvalidasi payload, update `draw_position`, regenerasi match, dan increment `draw_version` | Pest v4 (`php artisan test --compact --filter=CompetitionDrawTest`) |
| Backend Validation Test | Testing reorder ditolak jika status `locked` atau ID peserta tidak valid | Pest v4 |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Admin dapat mengurutkan peserta secara manual melalui drag-and-drop atau tombol naik/turun di UI Draw.
- [ ] Tombol reset/urutkan A-Z tersedia untuk mempermudah penyusunan awal.
- [ ] Klik "Simpan Urutan & Buat Undian" berhasil menyimpan `draw_position` baru ke database dan men-generate match preview yang sesuai dengan urutan tersebut.
- [ ] Fitur shuffle acak (random draw) tetap berfungsi dengan baik sebagai alternatif mode.
- [ ] Preview jadwal pertandingan (match list & knockout bracket preview) langsung berubah sesuai urutan baru.
- [ ] Setelah dikunci (`lock`), mode pengurutan manual dan shuffle otomatis ter-disable.
- [ ] Pest Feature Test untuk endpoint `reorder` dan UI flow lulus 100% tanpa regressi.

---

## 9. Open Questions & Decided Specifications

- [x] **Kontrol Pengurutan Manual:** Mendukung gabungan **Drag & Drop** (via drag handle `GripVertical`) DAN **tombol Naik/Turun** (`ArrowUp` / `ArrowDown`) untuk fleksibilitas maksimal di perangkat desktop & mobile/touch.
- [x] **Instant Live Preview:** Rekalkulasi visual preview jadwal pertandingan dilakukan secara **real-time di client (Vue)** saat peserta digeser/diurutkan sebelum tombol "Simpan Urutan & Buat Undian" diklik, sehingga admin dapat langsung melihat dampak urutan peserta pada bagan/jadwal secara instan.

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 hari | Finalisasi FEATURE-undian-jadwal-pertandingan.md |
| Backend Endpoint & Service | 1 hari | Implementasi `reorder` method, route, & Pest tests |
| Frontend UI (shadcn-vue) | 1.5 hari | Komponen drag-and-drop sortable list & integrasi ke `Draw.vue` |
| Testing & QA | 0.5 hari | Pest test run & UI manual testing |

**Confidence:** High — Arsitektur `DrawGenerator` dan `CompetitionDrawController` di backend sudah matang dan sangat modular (`Phase 03` status DONE).
