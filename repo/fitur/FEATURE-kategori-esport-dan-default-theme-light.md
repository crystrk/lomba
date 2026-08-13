# Feature: Kategori Esport & Default Light Mode Theme

> Dokumen ini adalah rencana pengembangan fitur penambahan **Kategori Esport** dan penerapan **Default Theme Light Mode** (non-system detection).  
> Dibuat berdasarkan PRD: `repo/PRD.md` | Versi: 13 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Kategori Esport & Default Light Mode Theme  
**Status:** Draft  
**Priority:** Medium  
**Epic/Module:** Competitions & Core Design System / Appearance  
**Detected Stack:** Laravel 13 (PHP 8.4) + Inertia v3 + Vue 3.5 + TypeScript + Tailwind CSS v4 + Pest v4  

### Problem Statement
1. Pengguna ingin membuat lomba kategori e-Sports / Game Online (seperti Mobile Legends, Valorant, PUBG, FC Mobile, dll.), namun kategori `esport` belum tersedia pada pilihan jenis olahraga/cabang lomba.
2. Tampilan bawaan (theme default) saat ini mendeteksi pengaturan tema sistem OS (`system`), yang dapat membuat tampilan tidak konsisten antar perangkat dan berpotensi memicu layout flash atau ketidaksesuaian preferensi UI.

### Proposed Solution
1. Menambahkan enum `Esport = 'esport'` pada `CompetitionSport` di backend Laravel, serta membuat komponen SVG `EsportIcon.vue` di frontend berdasarkan asset SVG Game-10.
2. Mengubah default theme aplikasi di client composable (`useAppearance.ts`) dan Blade template (`resources/views/app.blade.php`) agar selalu mengarah ke `'light'` daripada `'system'`.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Fleksibilitas kategorisasi lomba & konsistensi tampilan UI publik/admin |
| **Target User** | Admin (pembuat lomba), Operator, dan Pengunjung Publik |
| **Scope** | ✅ In scope (Penambahan Enum Sport & Penyesuaian Theme Defaults) |
| **Dependency** | `CompetitionSport` enum, `competitionSport.ts`, `useAppearance.ts`, `app.blade.php` |

---

## 3. User Flow

### Flow 1: Pembuatan & Tampilan Lomba Kategori Esport
```
[Admin / User] → [Pilih Kategori "Esport" pada Form Lomba] → [Simpan Lomba] → [Ikon Controller/Game (SVG) tampil di Admin & Halaman Publik]
```

### Flow 2: Tema Aplikasi Pertama Kali Dibuka
```
[Pengunjung / User Baru] → [Buka Aplikasi Web (Tanpa localStorage theme)] → [Aplikasi langsung me-render Light Mode (Tanpa Cek System OS Dark Mode)]
```

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] Menambahkan `case Esport = 'esport';` pada `App\Enums\CompetitionSport`.
- [ ] Menyiapkan file komponen SVG `resources/js/components/competitions/sport-icons/EsportIcon.vue` dengan kode SVG game controller.
- [ ] Meng-update `resources/js/components/competitions/competitionSport.ts` agar menyertakan label `'Esport'` dan ikon `EsportIcon`.
- [ ] Mengubah `initializeTheme()` & `useAppearance()` pada `resources/js/composables/useAppearance.ts` agar default fallback adalah `'light'`.
- [ ] Mengubah script inline di `resources/views/app.blade.php` agar default `$appearance` adalah `'light'`.

### Should Have
- [ ] Menyesuaikan opsi `AppearanceTabs.vue` jika pilihan 'system' dihapus atau diprioritaskan Light mode.

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | SVG Icon ter-bundle secara ringan di komponen Vue (< 2KB) |
| **Consistency** | Visual ikon Esport selaras dengan style stroke SVG ikon cabang olahraga lainnya |
| **UX** | Tidak ada screen flickering (FOUT/theme flash) saat halaman pertama kali di-load |

---

## 6. UI/UX Notes

**Touchpoints:**
1. Form Tambah / Edit Lomba (`Admin/Competitions/Create.vue` & `Edit.vue`): Opsi "Esport" dengan ikon Stik Game.
2. Card Lomba (`Welcome.vue`, `Public/Competition/Show.vue`, `Admin/Competitions/Show.vue`): Watermark/Badge ikon Esport.
3. Appearance Selector / Root HTML class: Default tanpa class `.dark` pada `<html>`.

---

## 7. Technical Plan

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Backend | Penambahan enum `Esport` | `app/Enums/CompetitionSport.php` |
| Frontend | Komponen SVG baru & penambahan mapping sport | `resources/js/components/competitions/sport-icons/EsportIcon.vue`<br>`resources/js/components/competitions/competitionSport.ts` |
| Theme System | Mengubah default theme fallback dari `'system'` menjadi `'light'` | `resources/js/composables/useAppearance.ts`<br>`resources/views/app.blade.php` |

### Backend — Laravel
- **Enum Change:** `case Esport = 'esport';` pada `App\Enums\CompetitionSport`.

### Frontend — Vue
- **New Component:** `resources/js/components/competitions/sport-icons/EsportIcon.vue` (SVG Icon).
- **TypeScript Types:** Update `CompetitionSport` type pada `competitionSport.ts` menjadi `'football' | 'badminton' | 'tennis' | 'table_tennis' | 'chess' | 'volleyball' | 'esport' | 'general'`.
- **Composables:** Update default theme di `useAppearance.ts` (`const appearance = ref<Appearance>('light');`, fallback `savedAppearance || 'light'`).

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] `CompetitionSport` di backend memuat `esport`.
- [ ] Form pembuatan & pengeditan lomba dapat memilih kategori Esport.
- [ ] Ikon stik game (Esport) tampil dengan benar di daftar lomba dan detail lomba (Admin & Publik).
- [ ] User baru yang membuka aplikasi pertama kali langsung mendapatkan tampilan **Light Mode** secara default, tanpa terpengaruh dark mode pada OS/Sistem.
- [ ] Seluruh unit/feature test Pest tetap hijau (100% pass).
- [ ] `npm run build` berjalan tanpa error TypeScript / Vite rollup.

---

## 9. Open Questions

- [ ] Apakah opsi tombol toggle theme 'system' di Settings/Navigation tetap ingin dipertahankan sebagai opsi manual, atau disederhanakan hanya Light & Dark? *(Rekomendasi: Tetapkan default ke Light, tetapi tetap izinkan user memilih Dark jika diinginkan).*

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Spec & Plan | Done | Dokumen rencana fitur disetujui |
| Implementation | 1 Jam | Update Enum, SVG icon, `competitionSport.ts`, `useAppearance.ts`, `app.blade.php` |
| Verification | 30 Menit | Pest tests & Vite build |

**Confidence:** High — Perubahan terisolasi dengan rapi pada Enum backend dan Theme composable frontend.
