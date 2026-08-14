# Feature: Banner Marquee Hasil 5 Pertandingan Terakhir

> Dokumen ini adalah rencana pengembangan fitur **Banner / Newsticker Marquee Hasil 5 Pertandingan Terakhir** di halaman beranda publik.  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 14 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Banner Marquee Hasil 5 Pertandingan Terakhir  
**Status:** Draft  
**Priority:** Medium  
**Epic/Module:** Portal Publik & Informasi Lomba  
**Detected Stack:** Laravel 13.17 + Inertia.js 3.0 + Vue 3.5 + Tailwind CSS 4.1 + Pest 4.7

### Problem Statement
Pengunjung halaman beranda publik ingin mengetahui hasil akhir pertandingan yang baru saja selesai dari berbagai cabang lomba secara cepat dan dinamis tanpa harus membuka halaman detail masing-masing lomba satu per satu.

### Proposed Solution
Menyediakan komponen **Newsticker / Marquee Banner** yang menampilkan 5 pertandingan terakhir yang telah selesai (`status = 'completed'`). Banner ini ditempatkan tepat di bawah section *Pertandingan Sedang Berlangsung (Live Matches)* pada halaman utama (`Welcome.vue`), dengan animasi horizontal mengalir dari kanan ke kiri (*marquee effect*) dan fitur *pause on hover/touch* agar nyaman dibaca dan dapat diklik menuju detail lomba terkait.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Meningkatkan keterbukaan dan kemudahan akses informasi publik terhadap hasil pertandingan terkini secara real-time. |
| **Target User** | Pengunjung Publik, Peserta Lomba, Suporter, dan Panitia. |
| **Scope** | ✅ In scope (Penyajian informasi hasil pertandingan publik). |
| **Dependency** | Modul Pertandingan (`competition_matches`), Controller Publik (`PublicCompetitionController@index`), dan Komponen Beranda (`Welcome.vue`). |

---

## 3. User Flow

**Happy Path:**
```
[Pengunjung Buka Beranda (/)] 
   └── Sistem memuat 5 pertandingan terakhir yang berstatus 'completed'
         └── Newsticker Marquee muncul di bawah Section Live Match
               └── Kartu skor bergerak halus dari kanan ke kiri (infinite loop)
                     └── Pengunjung hover/tap pada salah satu kartu (animasi jeda otomatis)
                           └── Pengunjung klik kartu → diarahkan ke halaman detail lomba (/lomba/{slug})
```

**Edge Cases:**
- [ ] **Belum Ada Pertandingan Selesai:** Jika belum ada match yang berstatus `completed` (`count === 0`), banner newsticker tidak ditampilkan (hidden secara bersih).
- [ ] **Pertandingan Selesai Kurang dari 5:** Jika baru 1–4 pertandingan yang selesai, newsticker tetap menampilkan sejumlah pertandingan yang tersedia dengan looping mulus.
- [ ] **Pertandingan Sistem Gugur dengan Adu Penalti (Tie-Break):** Kartu ticker menampilkan skor utama beserta indikator pemenang tie-break / keterangan (`win_method`, misal: "Penalti 5-4").
- [ ] **Aksesibilitas & Motion Sensitivity:** Mengakomodasi preferensi `prefers-reduced-motion` untuk menghentikan animasi otomatis bagi pengguna yang sensitif terhadap gerakan.

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] **Backend Data Fetching:** `PublicCompetitionController@index` memuat 5 pertandingan terakhir yang berstatus `completed` (`where('status', 'completed')`), terurut dari yang terbaru berdasarkan `result_updated_at desc` (atau `updated_at desc`), lengkap dengan relasi `competition`, `homeParticipant`, `awayParticipant`, dan `winner`.
- [ ] **Props Inertia:** Mengirimkan properti `recentResults` ke halaman `Welcome.vue`.
- [ ] **Komponen Marquee Banner:** Komponen newsticker responsif yang ditempatkan di bawah section live match di `resources/js/pages/Welcome.vue`.
- [ ] **Informasi Kartu Ticker:**
  - Ikon cabang olahraga / Nama lomba (link ke `/lomba/{slug}`)
  - Nama Tim Tuan Rumah (Home) vs Tim Tamu (Away) dengan indikator pemenang 🏆
  - Skor akhir (`score_home` - `score_away`)
  - Waktu selesai / keterangan tie-break (jika ada).
- [ ] **Animasi Marquee Halus:** Animasi CSS berjalan terus-menerus dari kanan ke kiri dengan duplikasi track untuk looping tanpa celah (*seamless infinite scroll*).
- [ ] **Interaksi Pause-on-Hover:** Animasi otomatis berhenti sementara saat kursor mouse diarahkan ke banner atau saat disentuh di layar HP.

### Should Have
- [ ] Label badge tetap di sisi kiri (sticky/fixed badge *"HASIL TERBARU"* atau *"LATEST RESULTS"*) dengan efek gradien agar konteks newsticker selalu terlihat jelas.
- [ ] Tombol pintasan untuk melihat semua hasil pertandingan jika diperlukan.

### Won't Have (untuk versi ini)
- [ ] Webhook/WebSocket streaming per detik (data diperbarui setiap kali pengunjung me-refresh atau mengunjungi halaman beranda).
- [ ] Filter cabor khusus di dalam newsticker (ticker merangkum semua cabor secara global).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Query database dibatasi `limit(5)` dengan eager loading relasi dan index pada `status` serta `result_updated_at`, waktu query < 20ms. |
| **Animation Performance** | Animasi marquee menggunakan CSS `transform: translateX()` (hardware accelerated) 60fps tanpa jank layout recalculation. |
| **Responsiveness** | Tampil rapi di layar mobile (320px) hingga layar desktop besar (4K). |
| **Accessibility** | Mendukung `prefers-reduced-motion` dan tag semantik HTML. |

---

## 6. UI/UX Notes

**Touchpoints:**
- `app/Http/Controllers/PublicCompetitionController.php` (tambahkan query `recentResults`).
- `resources/js/pages/Welcome.vue` (tambahkan section banner marquee di bawah section live).
- `resources/js/components/Public/RecentResultsMarquee.vue` (opsional: komponen modular terpisah untuk newsticker).

**Wireframe ASCII (Beranda Publik):**
```
+-----------------------------------------------------------------------------------+
|  🔴 PERTANDINGAN SEDANG BERLANGSUNG (LIVE)                                        |
|  [ Match Live 1 ]    [ Match Live 2 ]    [ Match Live 3 ]                         |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  +---------------+  <<< ANIMASI MARQUEE BERGERAK KE KIRI <<<                      |
|  | HASIL TERBARU |  [🏆 Tim A 3-1 Tim B]   [Tim C 0-0 Tim D]   [🏆 Tim E 2-1 Tim F] |
|  +---------------+                                                                |
|                                                                                   |
+-----------------------------------------------------------------------------------+
|  🏆 DAFTAR LOMBA AKTIF                                                            |
|  [Filter & Search] ...                                                            |
+-----------------------------------------------------------------------------------+
```

---

## 7. Technical Plan

### Existing Architecture
Aplikasi menggunakan **Laravel 13** backend dengan **Inertia.js v3** dan **Vue 3 (TypeScript)** di frontend, distilasi dengan **Tailwind CSS v4**. Data pertandingan disimpan di tabel `competition_matches`.

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Backend | Menambahkan query `recentResults` (5 match terakhir berstatus `completed`) | `app/Http/Controllers/PublicCompetitionController.php` |
| Frontend | Menambahkan komponen / section Marquee Newsticker | `resources/js/pages/Welcome.vue` atau `resources/js/components/Public/RecentResultsMarquee.vue` |
| Styling | Keyframes CSS untuk infinite marquee translate X | Tailwind CSS utility / scoped style di Vue |

### Backend — Laravel
- **Controller:** Pada `PublicCompetitionController::index()`:
  ```php
  $recentResults = CompetitionMatch::query()
      ->where('status', CompetitionMatchStatus::Completed)
      ->whereNotNull('score_home')
      ->whereNotNull('score_away')
      ->whereHas('competition', fn ($q) => $q->whereIn('status', [CompetitionStatus::Locked, CompetitionStatus::InProgress, CompetitionStatus::Completed]))
      ->with(['competition', 'homeParticipant', 'awayParticipant', 'winner'])
      ->orderByDesc('result_updated_at')
      ->orderByDesc('updated_at')
      ->limit(5)
      ->get()
      ->map(fn (CompetitionMatch $m) => [
          'id' => $m->id,
          'round' => $m->round,
          'score_home' => $m->score_home,
          'score_away' => $m->score_away,
          'winner_id' => $m->winner_id,
          'win_method' => $m->win_method,
          'competition' => [
              'id' => $m->competition->id,
              'name' => $m->competition->name,
              'slug' => $m->competition->slug,
              'sport' => $m->competition->sport?->value,
          ],
          'home' => $m->homeParticipant ? ['id' => $m->homeParticipant->id, 'name' => $m->homeParticipant->name, 'short_name' => $m->homeParticipant->short_name] : null,
          'away' => $m->awayParticipant ? ['id' => $m->awayParticipant->id, 'name' => $m->awayParticipant->name, 'short_name' => $m->awayParticipant->short_name] : null,
      ]);
  ```

### Frontend — Vue 3
- Props baru pada `Welcome.vue`:
  ```ts
  recentResults: Array<{
      id: number;
      round: number;
      score_home: number;
      score_away: number;
      winner_id: number | null;
      win_method: string | null;
      competition: { id: number; name: string; slug: string; sport: string | null };
      home: { id: number; name: string; short_name: string | null } | null;
      away: { id: number; name: string; short_name: string | null } | null;
  }>;
  ```
- Animasi CSS Marquee:
  - Menggunakan dua track identik (duplikasi list) dengan animasi `@keyframes marquee { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }` berdurasi ~25-35s.
  - Kelas `hover:[animation-play-state:paused]` untuk menghentikan animasi saat di-hover.

### Testing Strategy
| Level | Skenario | Tool Existing |
|-------|----------|---------------|
| Backend | `PublicCompetitionControllerTest` memastikan data 5 pertandingan terakhir terkirim di props `recentResults` dengan urutan yang benar | Pest PHP |
| Frontend | Render marquee saat ada data match completed & hidden saat 0 match | Manual/Smoke test |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Backend mengirimkan maksimal 5 pertandingan terakhir yang berstatus `completed` ke halaman beranda.
- [ ] Newsticker / Marquee banner tampil tepat di bawah section *Pertandingan Sedang Berlangsung* pada halaman beranda publik (`/`).
- [ ] Banner bergerak halus dari kanan ke kiri (*marquee effect*) secara terus menerus.
- [ ] Saat kursor mouse diarahkan ke banner atau disentuh pada layar HP, animasi berhenti (*pause on hover/tap*).
- [ ] Setiap kartu skor menampilkan nama lomba, nama tim tuan rumah vs tamu, skor akhir, dan highlight pemenang.
- [ ] Kartu dapat diklik untuk membuka halaman detail lomba terkait (`/lomba/{slug}`).
- [ ] Jika belum ada pertandingan selesai, banner tidak muncul dan tidak menimbulkan layout kosong/rusak.
- [ ] Semua test Pest berjalan lancar (100% pass) dan `npm run build` sukses.

---

## 9. Keputusan Desain (Finalized)

- ✅ **Waktu Pertandingan:** Tidak ditampilkan di kartu ticker agar tampilan tetap ringkas, padat, dan fokus pada skor akhir, nama tim/peserta, dan nama lomba.
- ✅ **Animasi Looping:** Marquee selalu bergerak looping mulus ke kiri tanpa henti (infinite loop) terlepas dari jumlah match (1 s.d. 5 match).


---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 hari | Finalisasi dokumen ini |
| Backend & Query | 0.5 hari | Query `recentResults` di `PublicCompetitionController` |
| Frontend UI & Marquee | 0.5 - 1 hari | Pembuatan komponen Marquee & integrasi ke `Welcome.vue` |
| Testing & QA | 0.5 hari | Feature test Pest & uji responsivitas mobile |

**Confidence:** High — Struktur data `competition_matches` dan halaman `Welcome.vue` sudah sangat siap untuk integrasi fitur ini.

---

*Dokumen ini akan terus diperbarui selama proses development.*
