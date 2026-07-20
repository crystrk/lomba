# Phase 03 — Shuffle, Generator Pertandingan, dan Penguncian

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 7, 8.3, 9.4, 10, 13.5–13.6, serta AC-04 sampai AC-08.

Rumus jumlah pertandingan, aturan bye, dan sifat final lock harus mengikuti [PRD bagian 10](./PRD.md#10-aturan-pembentukan-pertandingan). Jika algoritma yang dipilih tidak dapat memenuhi invariant tersebut, perbaiki algoritmanya—jangan mengubah expected test tanpa keputusan produk.

| Metadata        | Nilai                                                                         |
| --------------- | ----------------------------------------------------------------------------- |
| Status          | NOT STARTED                                                                   |
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

- [ ] Definisikan value object/DTO hasil generator secukupnya: round, leg, sequence, participant slots, bye, dan next slot.
- [ ] Hindari coupling generator ke Eloquent jika array/collection ID cukup.
- [ ] Buat dataset jumlah peserta `2, 3, 4, 5, 6, 8` dan minimal satu target besar.
- [ ] Buat assertion helper test hanya jika benar-benar dipakai ketiga generator.

### Invariant bersama

- [ ] Participant tidak pernah melawan dirinya sendiri.
- [ ] Setiap match memiliki urutan stabil.
- [ ] Tidak ada pasangan di luar aturan format.
- [ ] Input yang sama menghasilkan output yang sama.

### Verifikasi

```bash
php artisan test --compact tests/Unit/Competition
```

## C2 — Generator setengah kompetisi

### Implementasi/test

- [ ] Gunakan round-robin/circle method.
- [ ] Peserta genap: `n-1` round dan `n(n-1)/2` match.
- [ ] Peserta ganjil: `n` round, setiap participant satu bye, dan jumlah match tetap benar.
- [ ] Setiap unordered pair muncul tepat satu kali.
- [ ] Home/first dan away/second didistribusikan secara deterministik dari urutan hasil shuffle.
- [ ] Bye bukan pertandingan yang memerlukan skor.

### Verifikasi

```bash
php artisan test --compact --filter="half competition generator"
```

Stop jika AC-06 belum hijau. Jangan mulai full competition sebelum single round-robin valid.

## C3 — Generator kompetisi penuh

### Implementasi/test

- [ ] Gunakan hasil half competition sebagai leg pertama.
- [ ] Leg kedua memiliki pasangan sama dengan posisi dibalik.
- [ ] Jumlah match `n(n-1)`.
- [ ] Jumlah round dua kali single round-robin.
- [ ] Setiap ordered pair muncul tepat satu kali lintas dua leg.
- [ ] Nomor round leg kedua tidak bertabrakan dengan leg pertama.

### Verifikasi

```bash
php artisan test --compact --filter="full competition generator"
```

Stop jika AC-07 atau pairing reverse belum hijau.

## C4 — Generator sistem gugur

### Implementasi/test

- [ ] Hitung ukuran bracket sebagai pangkat dua terdekat yang lebih besar/sama dengan `n`.
- [ ] Distribusikan bye tanpa self-match dan tanpa participant duplikat.
- [ ] Buat node pertandingan seluruh babak dengan referensi next match/slot yang eksplisit.
- [ ] Match bye berstatus `bye` dan memajukan participant otomatis saat preview dipersist.
- [ ] Slot future match boleh participant nullable.
- [ ] Jumlah match yang membutuhkan skor selalu `n-1`.
- [ ] Label babak mengikuti PRD.
- [ ] Uji khusus `n=2`, `n=3`, `n=6`, `n=8`.

### Verifikasi

```bash
php artisan test --compact --filter="knockout generator"
```

Stop jika AC-08 atau next-slot mapping belum hijau.

## C5 — Shuffle dan persistence preview

### Backend

- [ ] Buat action shuffle transaksional.
- [ ] Authorize admin dan validasi minimal dua participant.
- [ ] Izinkan hanya status `draft`/`drawn`.
- [ ] Acak ID participant, lalu panggil generator sesuai format.
- [ ] Untuk lebih dari dua participant, retry terbatas dan fallback deterministik agar urutan tidak identik dengan draw sebelumnya jika memungkinkan.
- [ ] Ganti preview lama dan update `draw_position` dalam transaksi.
- [ ] Set status `drawn` dan increment `draw_version`.
- [ ] Response/read berikutnya menggunakan match persisted.
- [ ] Participant create/update/delete pada status `drawn` menghapus preview dan mengembalikan status `draft` secara transaksional.

### Test

- [ ] Refresh/read tidak mengubah preview.
- [ ] Re-shuffle mengganti preview tanpa row sisa/duplikat.
- [ ] Mutation participant membatalkan preview.
- [ ] Operator/guest tidak dapat shuffle.
- [ ] Shuffle concurrent/stale tidak menghasilkan dua preview campur.

### Verifikasi

```bash
php artisan test --compact --filter="competition draw"
```

## C6 — UI preview dan re-shuffle

### Implementasi

- [ ] Buat halaman undian admin melalui named route dan Wayfinder.
- [ ] Tampilkan jumlah peserta dan perkiraan/hasil jumlah pertandingan.
- [ ] Preview knockout berupa kolom bracket sederhana yang tetap terbaca.
- [ ] Preview competition dikelompokkan per round dan leg.
- [ ] Tampilkan `draw_version`/timestamp secara informatif bila membantu.
- [ ] Shuffle/re-shuffle memiliki loading state dan konfirmasi saat mengganti preview.
- [ ] Empty/error state menjelaskan syarat minimal dua peserta.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
php artisan test --compact --filter="draw page"
```

## C7 — Lock transaksional

### Implementasi

- [ ] Buat action lock terpisah dari shuffle.
- [ ] Validasi status `drawn`, minimal dua peserta, preview lengkap, dan `draw_version` expected.
- [ ] Pastikan jumlah/pasangan match masih memenuhi generator invariant sebelum lock.
- [ ] Simpan `locked_at`, `locked_by`, dan status `locked` dalam transaksi.
- [ ] Request lock dari tab stale ditolak dengan pesan refresh.
- [ ] Setelah lock, tolak format, poin, participant, shuffle, dan delete di backend.
- [ ] Metadata non-struktural dan assignment operator tetap dapat diubah.
- [ ] UI dialog lock menampilkan format, participant count, scored-match count, dan scoring rules jika relevan.
- [ ] Tidak ada fitur unlock pada MVP.

### Verifikasi

```bash
php artisan test --compact --filter="lock competition"
npm run types:check
```

## Test Gate Fase 3

- [ ] Seluruh unit test generator hijau untuk genap/ganjil dan power/non-power-of-two.
- [ ] AC-04 sampai AC-08 hijau.
- [ ] Preview persisten setelah reload.
- [ ] Participant mutation menginvalidasi draw.
- [ ] Lock stale ditolak.
- [ ] Structural mutation setelah lock ditolak melalui direct request test.
- [ ] Assignment operator dan metadata aman tetap dapat diubah.
- [ ] Tidak ada partial preview setelah exception/transaksi gagal.

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
|         |            |                 |            |       |                 |
