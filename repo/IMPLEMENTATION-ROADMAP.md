# Implementation Roadmap — Sistem Manajemen Lomba

## Sumber Kebenaran

> **Wajib dibaca sebelum mengerjakan fase mana pun:** [Product Requirements Document](./PRD.md).

Roadmap ini menerjemahkan [PRD](./PRD.md), terutama bagian 5–19, menjadi urutan implementasi yang kecil, dapat diuji, dan mudah di-debug. Roadmap tidak boleh mengubah kebutuhan produk secara diam-diam.

Urutan sumber kebenaran proyek:

1. [PRD](./PRD.md) menetapkan perilaku dan batas MVP.
2. Roadmap ini menetapkan urutan pengerjaan.
3. Dokumen fase menetapkan checkpoint teknis dan test gate.
4. Kode dan test membuktikan implementasi sesuai dokumen.

Jika kode, dokumen fase, atau roadmap bertentangan dengan PRD:

1. hentikan checkpoint terkait;
2. catat perbedaannya;
3. putuskan apakah implementasi yang salah atau PRD perlu diubah;
4. perbarui PRD lebih dahulu jika keputusan produk berubah;
5. baru lanjutkan implementasi dan test.

## Sasaran Roadmap

- Menjaga perubahan setiap checkpoint tetap kecil dan dapat dilacak.
- Menghindari implementasi UI sebelum kontrak backend stabil.
- Menempatkan logika domain kompleks dalam unit yang dapat diuji terpisah.
- Menjalankan test terarah setelah setiap checkpoint, bukan menunggu akhir proyek.
- Mencegah fase berikutnya menutupi bug dari fase sebelumnya.
- Memastikan semua requirement dan acceptance criteria PRD memiliki fase pemilik.

## Status Fase

Gunakan salah satu status berikut dan perbarui tabel ketika pekerjaan dimulai atau selesai:

- `NOT STARTED`
- `IN PROGRESS`
- `BLOCKED`
- `DONE`

| Urutan | Fase                               | Status | Bergantung pada      | Dokumen                                                 |
| -----: | ---------------------------------- | ------ | -------------------- | ------------------------------------------------------- |
|      1 | Fondasi akses dan domain           | DONE   | Baseline starter kit | [PHASE-01](./PHASE-01-FOUNDATION-ACCESS-DOMAIN.md)      |
|      2 | Pengelolaan lomba                  | DONE   | Fase 1 `DONE`        | [PHASE-02](./PHASE-02-ADMIN-COMPETITION-MANAGEMENT.md)  |
|      3 | Shuffle, generator, dan penguncian | DONE   | Fase 2 `DONE`        | [PHASE-03](./PHASE-03-DRAW-MATCH-GENERATION-LOCKING.md) |
|      4 | Skor, klasemen, dan progres bagan  | DONE   | Fase 3 `DONE`        | [PHASE-04](./PHASE-04-SCORING-STANDINGS-PROGRESSION.md) |
|      5 | Halaman publik dan hardening       | DONE   | Fase 4 `DONE`        | [PHASE-05](./PHASE-05-PUBLIC-UI-HARDENING.md)           |

Fase harus dikerjakan berurutan. Pekerjaan fase berikutnya hanya boleh dimulai jika test gate fase sebelumnya hijau dan tidak ada bug blocker yang belum diselesaikan.

## Peta Traceability PRD

| Area PRD                         | Requirement/AC                      | Fase pemilik                            |
| -------------------------------- | ----------------------------------- | --------------------------------------- |
| Role, login, akun internal       | FR-AUTH-01 sampai FR-AUTH-06        | Fase 1                                  |
| Model data dan lifecycle dasar   | Bagian 7 dan 13                     | Fase 1                                  |
| CRUD lomba dan operator          | FR-COMP-01 sampai FR-COMP-07        | Fase 2                                  |
| Peserta/tim                      | FR-TEAM-01 sampai FR-TEAM-05        | Fase 2                                  |
| Poin dan validasi format         | AC-01 sampai AC-03                  | Fase 2                                  |
| Shuffle dan pratinjau            | FR-DRAW-01 sampai FR-DRAW-06, AC-04 | Fase 3                                  |
| Generator ketiga format          | Bagian 10, AC-06 sampai AC-08       | Fase 3                                  |
| Penguncian lomba                 | Bagian 7.6 dan AC-05                | Fase 3                                  |
| Input dan koreksi skor           | FR-MATCH-01 sampai FR-MATCH-09      | Fase 4                                  |
| Kalkulasi klasemen               | Bagian 11, AC-09 dan AC-10          | Fase 4                                  |
| Progres/koreksi sistem gugur     | Bagian 12, AC-11 sampai AC-13       | Fase 4                                  |
| Pembatasan operator              | AC-14                               | Fase 1, 2, dan finalisasi Fase 4        |
| Status `in_progress`/`completed` | AC-16                               | Fase 4 dan presentasi Fase 5            |
| Landing dan detail publik        | FR-PUB-01 sampai FR-PUB-08, AC-15   | Fase 5                                  |
| UI/UX dan non-fungsional         | Bagian 15–16                        | Diterapkan per fase, audit akhir Fase 5 |
| Strategi pengujian               | Bagian 18                           | Diterapkan di seluruh fase              |

Requirement tidak boleh ditandai selesai hanya karena UI tersedia. Requirement selesai jika perilaku backend, otorisasi, validasi, dan test yang relevan juga selesai.

## Keputusan Teknis Lintas Fase

Keputusan ini menjaga penamaan dan ownership tetap konsisten. Jika akan diubah, cek dampaknya terhadap [PRD bagian 13](./PRD.md#13-model-data-konseptual) dan seluruh fase berikutnya.

### Bahasa domain

- Label UI dan pesan pengguna menggunakan Bahasa Indonesia.
- Nama kode menggunakan Bahasa Inggris agar konsisten dengan starter kit Laravel.
- Istilah `Competition` di kode berarti **Lomba** di PRD.
- Istilah `Participant` berarti tim peserta.
- Gunakan `CompetitionMatch`, bukan `Match`, karena `match` adalah keyword PHP.

### Sumber kebenaran data

- Backend Laravel adalah sumber kebenaran untuk status, izin, jadwal, pemenang, dan klasemen.
- Frontend tidak menghitung ulang klasemen atau menentukan pemenang.
- Klasemen dihitung dari pertandingan final; tidak perlu tabel standings pada MVP.
- Hasil shuffle harus dipersist agar refresh tidak mengubah susunan.
- Aksi lock dan update skor harus transaksional.
- Perubahan skor menggunakan version/timestamp untuk mendeteksi update bersamaan.

### Boundary kode

- Form Request menangani validasi dan otorisasi request.
- Policy menjadi sumber aturan akses admin/operator/assignment.
- Controller hanya mengorkestrasi request, action, dan response Inertia.
- Action digunakan untuk operasi domain yang mengubah beberapa model atau membutuhkan transaksi.
- Generator jadwal dan kalkulator klasemen berupa class domain tanpa ketergantungan HTTP agar mudah di-unit-test.
- Model mendefinisikan relasi, casts, scopes, dan atribut; hindari menaruh seluruh workflow di model.
- Vue hanya menangani presentasi, state form, konfirmasi, dan navigasi.

### Frontend

- Gunakan Inertia 3 dan Vue 3 dengan `<script setup lang="ts">` sesuai pola proyek.
- Gunakan `<Form>`/`useForm` sesuai kebutuhan dan tampilkan error backend per field.
- Gunakan fungsi Wayfinder dari `@/actions` atau `@/routes`; jangan hardcode URL backend.
- Gunakan komponen shadcn-vue dan Tailwind CSS 4.
- Setiap halaman Vue memiliki satu root element.
- Jangan menggunakan optimistic update untuk lock atau skor final karena kedua operasi memerlukan konfirmasi backend.

## Siklus Kerja Setiap Checkpoint

### 1. Sebelum mulai

- [ ] Baca ulang bagian PRD yang disebut dokumen fase.
- [ ] Pastikan fase sebelumnya berstatus `DONE`.
- [ ] Pastikan working state dan baseline test dipahami.
- [ ] Pilih hanya satu checkpoint.
- [ ] Identifikasi requirement dan acceptance criteria yang akan dibuktikan.

### 2. Implementasi backend lebih dahulu

- [ ] Tulis atau perbarui test yang gagal untuk perilaku target.
- [ ] Implementasikan perubahan schema/domain paling kecil.
- [ ] Implementasikan validasi dan otorisasi.
- [ ] Jalankan test terarah sampai hijau.

### 3. Integrasikan frontend

- [ ] Tambahkan route/controller/prop Inertia yang dibutuhkan.
- [ ] Regenerasi Wayfinder setelah route/controller berubah.
- [ ] Implementasikan UI dengan loading, error, dan empty state.
- [ ] Jalankan typecheck serta test fitur terkait.

### 4. Tutup checkpoint

- [ ] Jalankan formatter hanya pada file yang berubah.
- [ ] Jalankan seluruh test milik checkpoint.
- [ ] Catat hasil verifikasi dalam log fase.
- [ ] Jangan membawa bug yang diketahui ke checkpoint berikutnya.

## Strategi Debugging

Ketika test atau alur gagal:

1. **Reproduksi pada scope terkecil.** Gunakan satu test dengan `--filter` atau satu file test.
2. **Tentukan layer yang gagal.** Pisahkan schema/data, domain action, policy, HTTP/Inertia, dan Vue.
3. **Periksa invariant PRD.** Pastikan status lomba, role, assignment, dan format data memenuhi prasyarat.
4. **Periksa backend lebih dahulu.** UI tidak boleh menutupi error validasi/otorisasi/backend.
5. **Gunakan log yang relevan.** Periksa error backend dan browser terbaru; abaikan log lama.
6. **Tambahkan regression test.** Bug dianggap selesai jika ada test yang gagal sebelum fix dan hijau setelah fix.
7. **Jangan mengganti algoritma sekaligus.** Perbaiki kasus terkecil lalu jalankan dataset kasus batas.

Kategori diagnosis cepat:

| Gejala                            | Periksa pertama                                                   |
| --------------------------------- | ----------------------------------------------------------------- |
| `403` tidak terduga               | Policy, role user, assignment operator, status aktif user         |
| `422` tidak terduga               | Form Request, conditional rule berdasarkan format/status          |
| Data anak lomba lain bisa diakses | Scoped binding dan validasi parent-child                          |
| Susunan berubah saat refresh      | Persistence preview, bukan randomisasi di query/render            |
| Jumlah pertandingan salah         | Input urutan peserta dan unit test generator                      |
| Klasemen berlipat setelah koreksi | Kalkulasi dari source match final, bukan increment statistik lama |
| Pemenang gugur salah jalur        | Referensi next match/slot dan transaksi progression               |
| TypeScript route error            | Regenerasi Wayfinder dan signature route model binding            |
| UI tidak berubah                  | Jalankan Vite/build dan pastikan prop Inertia terbaru             |

## Perintah Verifikasi Standar

Gunakan test paling sempit terlebih dahulu:

```bash
php artisan test --compact tests/Unit/NamaTest.php
php artisan test --compact tests/Feature/NamaTest.php
php artisan test --compact --filter="nama perilaku"
```

Setelah mengubah route/controller yang dipanggil frontend:

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
```

Setelah mengubah PHP:

```bash
vendor/bin/pint --dirty --format agent
```

Gate frontend per fase:

```bash
npm run types:check
npm run lint:check
npm run format:check
```

Gate keseluruhan pada akhir Fase 5:

```bash
composer run ci:check
npm run build
```

Jangan menjalankan semua test setiap beberapa baris perubahan. Jalankan test terarah per checkpoint, seluruh test fase pada phase gate, lalu seluruh suite pada akhir proyek.

## Format Log Implementasi

Setiap dokumen fase memiliki tabel log. Isi minimal:

| Tanggal    | Checkpoint | Requirement PRD | Verifikasi    | Hasil     | Catatan/deviasi             |
| ---------- | ---------- | --------------- | ------------- | --------- | --------------------------- |
| YYYY-MM-DD | Cx         | FR/AC terkait   | Perintah test | PASS/FAIL | Link keputusan PRD jika ada |

Deviasi tidak boleh dibiarkan hanya di log. Jika mengubah perilaku produk, perbarui [PRD](./PRD.md), traceability roadmap, dan dokumen fase terkait.

## Definition of Done Proyek

- [ ] Seluruh lima fase berstatus `DONE`.
- [ ] Seluruh FR Must pada PRD memiliki test atau bukti verifikasi.
- [ ] AC-01 sampai AC-16 lulus.
- [ ] Otorisasi diuji sebagai admin, operator ditugaskan, operator tidak ditugaskan, dan guest.
- [ ] Generator teruji untuk peserta genap, ganjil, pangkat dua, dan non-pangkat dua.
- [ ] Koreksi skor tidak menggandakan klasemen atau merusak bracket.
- [ ] Landing hanya menampilkan lomba aktif dan detail completed tetap dapat dibuka.
- [ ] Tidak ada URL backend yang di-hardcode di Vue.
- [ ] Formatter, static analysis, lint, typecheck, seluruh test, dan production build lulus.
- [ ] Tidak ada bug blocker atau deviasi PRD yang belum diselesaikan.
