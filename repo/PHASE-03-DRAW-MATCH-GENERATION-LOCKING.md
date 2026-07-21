# Phase 03 — Shuffle, Generator Pertandingan, dan Penguncian

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 7, 8.3, 9.4, 10, 13.5–13.6, serta AC-04 sampai AC-08.

Rumus jumlah pertandingan, aturan bye, dan sifat final lock harus mengikuti [PRD bagian 10](./PRD.md#10-aturan-pembentukan-pertandingan). Jika algoritma yang dipilih tidak dapat memenuhi invariant tersebut, perbaiki algoritmanya—jangan mengubah expected test tanpa keputusan produk.

| Metadata        | Nilai                                                                         |
| --------------- | ----------------------------------------------------------------------------- |
| Status          | DONE                                                                          |
| Prasyarat       | [Phase 02](./PHASE-02-ADMIN-COMPETITION-MANAGEMENT.md) `DONE`                 |
| Hasil akhir     | Ketiga format dapat di-shuffle, dipreview, dipersist, dan dikunci dengan aman |
| Fase berikutnya | [Phase 04](./PHASE-04-SCORING-STANDINGS-PROGRESSION.md)                       |

## Traceability PRD

| Referensi PRD                | Target fase                                                             |
| ---------------------------- | ----------------------------------------------------------------------- |
| FR-DRAW-01 sampai FR-DRAW-06 | Shuffle tersedia, persisten, dapat diulang, dan tampil sebagai preview. |
| AC-04                        | Re-shuffle mengganti susunan sebelumnya secara valid.                   |
| AC-05                        | Lock final dan structural mutation ditolak.                             |
| AC-06                        | Empat tim half competition menghasilkan 6 match/3 round.                |
| AC-07                        | Empat tim full competition menghasilkan 12 match/6 round.               |
| AC-08                        | Enam tim knockout menghasilkan bracket 8 slot, 2 bye, 5 scored matches. |
| PRD bagian 16.2              | Shuffle/lock tahan stale request dan berjalan transaksional.            |

## Prinsip Implementasi Algoritma

- Pisahkan **pengacakan urutan** dari **generator schedule**.
- Generator menerima urutan participant dan menghasilkan struktur deterministik.
- Unit test generator tidak boleh bergantung pada random output.
- Action shuffle bertanggung jawab mengacak, menghindari hasil tepat sebelumnya jika memungkinkan, mempersist preview, dan menaikkan `draw_version`.
- Preview dibaca dari database; jangan menjalankan randomizer saat render.
- Re-shuffle mengganti seluruh preview dalam satu transaksi.
- Lock memvalidasi `draw_version` yang dilihat admin agar tab lama tidak mengunci draw baru.

## Urutan Checkpoint

## C1 — Kontrak generator dan dataset invariant

### Implementasi

- [x] Definisikan value object/DTO hasil generator secukupnya: round, leg, sequence, participant slots, bye, dan next slot.
- [x] Hindari coupling generator ke Eloquent jika array/collection ID cukup.
- [x] Buat dataset jumlah peserta `2, 3, 4, 5, 6, 8` dan minimal satu target besar.
- [x] Buat assertion helper test hanya jika benar-benar dipakai ketiga generator.

### Invariant bersama

- [x] Participant tidak pernah melawan dirinya sendiri.
- [x] Setiap match memiliki urutan stabil.
- [x] Tidak ada pasangan di luar aturan format.
- [x] Input yang sama menghasilkan output yang sama.

### Verifikasi

```bash
php artisan test --compact tests/Unit/Competition
```

## C2 — Generator setengah kompetisi

### Implementasi/test

- [x] Gunakan round-robin/circle method.
- [x] Peserta genap: `n-1` round dan `n(n-1)/2` match.
- [x] Peserta ganjil: `n` round, setiap participant satu bye, dan jumlah match tetap benar.
- [x] Setiap unordered pair muncul tepat satu kali.
- [x] Home/first dan away/second didistribusikan secara deterministik dari urutan hasil shuffle.
- [x] Bye bukan pertandingan yang memerlukan skor.

### Verifikasi

```bash
php artisan test --compact --filter="half competition generator"
```

Stop jika AC-06 belum hijau. Jangan mulai full competition sebelum single round-robin valid.

## C3 — Generator kompetisi penuh

### Implementasi/test

- [x] Gunakan hasil half competition sebagai leg pertama.
- [x] Leg kedua memiliki pasangan sama dengan posisi dibalik.
- [x] Jumlah match `n(n-1)`.
- [x] Jumlah round dua kali single round-robin.
- [x] Setiap ordered pair muncul tepat satu kali lintas dua leg.
- [x] Nomor round leg kedua tidak bertabrakan dengan leg pertama.

### Verifikasi

```bash
php artisan test --compact --filter="full competition generator"
```

Stop jika AC-07 atau pairing reverse belum hijau.

## C4 — Generator sistem gugur

### Implementasi/test

- [x] Hitung ukuran bracket sebagai pangkat dua terdekat yang lebih besar/sama dengan `n`.
- [x] Distribusikan bye tanpa self-match dan tanpa participant duplikat.
- [x] Buat node pertandingan seluruh babak dengan referensi next match/slot yang eksplisit.
- [x] Match bye berstatus `bye` dan memajukan participant otomatis saat preview dipersist.
- [x] Slot future match boleh participant nullable.
- [x] Jumlah match yang membutuhkan skor selalu `n-1`.
- [x] Label babak mengikuti PRD.
- [x] Uji khusus `n=2`, `n=3`, `n=6`, `n=8`.

### Verifikasi

```bash
php artisan test --compact --filter="knockout generator"
```

Stop jika AC-08 atau next-slot mapping belum hijau.

## C5 — Shuffle dan persistence preview

### Backend

- [x] Buat action shuffle transaksional.
- [x] Authorize admin dan validasi minimal dua participant.
- [x] Izinkan hanya status `draft`/`drawn`.
- [x] Acak ID participant, lalu panggil generator sesuai format.
- [x] Untuk lebih dari dua participant, retry terbatas dan fallback deterministik agar urutan tidak identik dengan draw sebelumnya jika memungkinkan.
- [x] Ganti preview lama dan update `draw_position` dalam transaksi.
- [x] Set status `drawn` dan increment `draw_version`.
- [x] Response/read berikutnya menggunakan match persisted.
- [x] Participant create/update/delete pada status `drawn` menghapus preview dan mengembalikan status `draft` secara transaksional.

### Test

- [x] Refresh/read tidak mengubah preview.
- [x] Re-shuffle mengganti preview tanpa row sisa/duplikat.
- [x] Mutation participant membatalkan preview.
- [x] Operator/guest tidak dapat shuffle.
- [x] Shuffle concurrent/stale tidak menghasilkan dua preview campur.

### Verifikasi

```bash
php artisan test --compact --filter="competition draw"
```

## C6 — UI preview dan re-shuffle

### Implementasi

- [x] Buat halaman undian admin melalui named route dan Wayfinder.
- [x] Tampilkan jumlah peserta dan perkiraan/hasil jumlah pertandingan.
- [x] Preview knockout berupa kolom bracket sederhana yang tetap terbaca.
- [x] Preview competition dikelompokkan per round dan leg.
- [x] Tampilkan `draw_version`/timestamp secara informatif bila membantu.
- [x] Shuffle/re-shuffle memiliki loading state dan konfirmasi saat mengganti preview.
- [x] Empty/error state menjelaskan syarat minimal dua peserta.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
php artisan test --compact --filter="draw page"
```

## C7 — Lock transaksional

### Implementasi

- [x] Buat action lock terpisah dari shuffle.
- [x] Validasi status `drawn`, minimal dua peserta, preview lengkap, dan `draw_version` expected.
- [x] Pastikan jumlah/pasangan match masih memenuhi generator invariant sebelum lock.
- [x] Simpan `locked_at`, `locked_by`, dan status `locked` dalam transaksi.
- [x] Request lock dari tab stale ditolak dengan validasi `draw_version`.
- [x] Setelah lock, tolak format, poin, participant, shuffle, dan delete di backend (via Policy).
- [x] Metadata non-struktural dan assignment operator tetap dapat diubah.
- [x] UI dialog lock menampilkan format, participant count, scored-match count, dan scoring rules jika relevan.
- [x] Tidak ada fitur unlock pada MVP.

### Verifikasi

```bash
php artisan test --compact --filter="lock competition"
npm run types:check
```

## Test Gate Fase 3

- [x] Seluruh unit test generator hijau untuk genap/ganjil dan power/non-power-of-two (92 tests PASS).
- [x] AC-04 sampai AC-08 hijau.
- [x] Preview persisten setelah reload.
- [x] Participant mutation menginvalidasi draw.
- [x] Lock stale ditolak.
- [x] Structural mutation setelah lock ditolak melalui direct request test.
- [x] Assignment operator dan metadata aman tetap dapat diubah.
- [x] Tidak ada partial preview setelah exception/transaksi gagal.

Jalankan:

```bash
php artisan test --compact tests/Unit/Competition
php artisan test --compact --filter="draw"
php artisan test --compact --filter="lock competition"
vendor/bin/pint --dirty --format agent
npm run types:check
npm run lint:check
npm run format:check
```

## Exit Criteria

Fase 3 `DONE` jika admin dapat meninjau dan mengunci output valid ketiga format, dan seluruh invariant algoritma dibuktikan unit test tanpa bergantung pada hasil random tertentu.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
| 2026-07-20 | C1-C4 | FR-DRAW-05, PRD 10 | php artisan test --compact tests/Unit/Competition | 92 unit tests PASS | Full, half, knockout generators + factory + DTO. Circle method with odd n fixed (array_splice null bug). Bye propagation in bracket tree. |
| 2026-07-20 | C5 | FR-DRAW-01/02/03/04/06, AC-04 | php artisan test --compact --filter=CompetitionDraw | 21 integration tests PASS | Shuffle transaksional, draw_position, preview persist. Participant mutation invalidates draw via model observer. 237 total tests PASS. |
| 2026-07-20 | C6 | FR-DRAW-06 | php artisan wayfinder:generate & npm run types:check | Wayfinder OK, types:check PASS | Draw.vue page dengan kolom per round, status badge, draw_version display, action shuffle/lock buttons. |
| 2026-07-20 | C7 | AC-05, PRD 16.2 | php artisan test --compact --filter=lock | Lock tests PASS | draw_version validation di lock request. Stale tab ditolak. Status locked mencegah structural mutation via Gate/Policy. |
