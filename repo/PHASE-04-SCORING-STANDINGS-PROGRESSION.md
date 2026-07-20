# Phase 04 — Skor, Klasemen, dan Progres Bagan

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6.2, 7.3–7.5, 8.4, 9.5, 11, 12, 15.5, serta AC-09 sampai AC-14 dan bagian backend AC-16.

Klasemen dan bracket progression harus mengikuti data pertandingan final sebagaimana ditetapkan [PRD bagian 11–12](./PRD.md#11-perhitungan-klasemen). Frontend tidak boleh memiliki algoritma alternatif.

| Metadata        | Nilai                                                                                |
| --------------- | ------------------------------------------------------------------------------------ |
| Status          | NOT STARTED                                                                          |
| Prasyarat       | [Phase 03](./PHASE-03-DRAW-MATCH-GENERATION-LOCKING.md) `DONE`                       |
| Hasil akhir     | Operator dapat menyimpan/koreksi skor; klasemen, bracket, dan status diperbarui aman |
| Fase berikutnya | [Phase 05](./PHASE-05-PUBLIC-UI-HARDENING.md)                                        |

## Traceability PRD

| Referensi PRD                  | Target fase                                                              |
| ------------------------------ | ------------------------------------------------------------------------ |
| FR-MATCH-01 sampai FR-MATCH-06 | Input skor valid, authorized, tercatat, transaksional, dan dikonfirmasi. |
| FR-MATCH-07                    | Koreksi competition menghitung ulang klasemen.                           |
| FR-MATCH-08 sampai FR-MATCH-09 | Koreksi knockout menjaga downstream match.                               |
| AC-09 sampai AC-10             | Kalkulasi dan koreksi klasemen benar.                                    |
| AC-11 sampai AC-13             | Tie-break, progression, dan dependency correction benar.                 |
| AC-14                          | Operator unassigned mendapat forbidden.                                  |
| AC-16 backend                  | Hasil pertama/terakhir mengubah status secara otomatis.                  |

## Prinsip Implementasi

- Kalkulator klasemen adalah pure domain class dan tidak menulis database.
- Klasemen dihitung dari seluruh competition match final setiap kali dibutuhkan/hasil berubah.
- Action update skor memegang transaksi, concurrency check, status transition, dan progression.
- Policy menentukan siapa yang boleh update.
- Form Request menentukan bentuk skor yang valid.
- Version result yang dilihat operator wajib dikirim saat update untuk mendeteksi stale form.
- Jangan gunakan optimistic UI untuk skor final.

## Urutan Checkpoint

## C1 — Kalkulator klasemen murni

### Implementasi/test

- [ ] Definisikan output row: rank, participant, played, won, drawn, lost, score for, score against, difference, points.
- [ ] Input hanya participant dan completed match dari satu competition.
- [ ] Participant tanpa match tetap muncul dengan statistik nol.
- [ ] Gunakan scoring rule competition, termasuk poin negatif.
- [ ] Urutkan: points, difference, score for, wins.
- [ ] Jika seluruh tie-break sama, gunakan shared rank model `1, 1, 3`.
- [ ] Nama hanya menstabilkan urutan display, tidak mengubah rank.
- [ ] Pending/bye match tidak masuk kalkulasi.
- [ ] Buat dataset menang, seri, kalah, koreksi, tie, dan poin negatif.

### Verifikasi

```bash
php artisan test --compact --filter="standings calculator"
```

Stop jika AC-09 belum hijau. Jangan menghubungkan UI sebelum kalkulator stabil.

## C2 — Validasi dan concurrency update skor

### Backend

- [ ] Buat Form Request score dengan authorization policy.
- [ ] Validasi score integer minimal nol.
- [ ] Match harus milik competition route dan memiliki dua participant diketahui.
- [ ] Match bye/pending future tidak dapat diberi skor.
- [ ] Competition harus `locked` atau `in_progress`.
- [ ] Request membawa `result_version` expected.
- [ ] Action membuka transaksi, memeriksa version terbaru, lalu menyimpan result.
- [ ] Simpan `result_updated_by`, `result_updated_at`, dan increment version.
- [ ] Jika version stale, kembalikan conflict/validation message yang meminta refresh; jangan overwrite diam-diam.

### Test

- [ ] Admin dan assigned active operator berhasil update.
- [ ] Guest, inactive operator, dan unassigned operator ditolak.
- [ ] Invalid score ditolak.
- [ ] Cross-competition nested match ditolak.
- [ ] Stale result version ditolak dan data terbaru dipertahankan.

### Verifikasi

```bash
php artisan test --compact --filter="update match score"
php artisan test --compact --filter="stale match result"
```

## C3 — Hasil format kompetisi dan status lifecycle

### Implementasi

- [ ] Skor lebih tinggi menghasilkan win/loss; skor sama menghasilkan draw.
- [ ] Simpan winner nullable pada draw atau derive secara konsisten.
- [ ] Setelah hasil pertama, competition `locked -> in_progress`.
- [ ] Setelah koreksi, standings dihitung ulang dari source final matches.
- [ ] Setelah seluruh match non-bye completed, status menjadi `completed`.
- [ ] Jangan menyimpan statistik inkremental yang berpotensi drift.
- [ ] Tampilkan standings hasil backend pada halaman internal.

### Test

- [ ] AC-09 dan AC-10.
- [ ] Tidak ada double count setelah update match yang sama berulang kali.
- [ ] Poin custom dan negatif benar.
- [ ] Status tidak completed jika satu match scorable masih pending.
- [ ] Hasil terakhir mengubah status ke completed.

### Verifikasi

```bash
php artisan test --compact --filter="competition standings"
php artisan test --compact --filter="competition completion status"
```

## C4 — Hasil dan progression sistem gugur

### Implementasi

- [ ] Jika score berbeda, winner otomatis participant dengan score lebih tinggi.
- [ ] Jika score sama, `winner_participant_id`/tie-break winner wajib salah satu participant match.
- [ ] Simpan metode/keterangan tie-break opsional.
- [ ] Setelah result tersimpan, isi home/away slot next match yang ditentukan generator.
- [ ] Next match menjadi ready ketika kedua participant tersedia.
- [ ] Bye tetap tidak memerlukan input skor.
- [ ] Final completed mengubah competition menjadi completed.
- [ ] Seluruh perubahan result dan next slot berada dalam transaksi yang sama.

### Test

- [ ] AC-11: draw tanpa tie-break winner ditolak.
- [ ] Tie-break winner yang bukan participant ditolak.
- [ ] AC-12: winner masuk slot downstream yang tepat.
- [ ] Pemenang final menyelesaikan competition.
- [ ] Exception progression tidak meninggalkan result setengah tersimpan.

### Verifikasi

```bash
php artisan test --compact --filter="knockout result progression"
```

## C5 — Koreksi hasil sistem gugur

### Implementasi

- [ ] Koreksi score tanpa perubahan winner diizinkan jika version valid.
- [ ] Jika winner berubah dan downstream match belum completed, ganti participant pada slot terkait.
- [ ] Jangan mengubah participant slot lain.
- [ ] Jika downstream match sudah completed, tolak koreksi dengan identitas/babak match yang memblokir.
- [ ] Pastikan koreksi tidak meninggalkan winner lama pada jalur berikutnya.
- [ ] Status competition dihitung konsisten setelah koreksi yang diizinkan.

### Test

- [ ] Winner sama, score berubah.
- [ ] Winner berubah, downstream belum dimainkan.
- [ ] AC-13: winner berubah, downstream completed, request ditolak.
- [ ] Data tidak berubah ketika request ditolak.
- [ ] Stale version dan downstream dependency menghasilkan pesan yang dapat dibedakan.

### Verifikasi

```bash
php artisan test --compact --filter="knockout result correction"
```

## C6 — Dashboard dan halaman operator

### Backend/UI

- [ ] Dashboard operator hanya query competition assigned.
- [ ] Daftar match dapat difilter round/status.
- [ ] Operator dapat melihat rules, participant, bracket/standings internal.
- [ ] Form score menampilkan dua participant, score sebelumnya, dan result version.
- [ ] Tie-break winner field hanya tampil untuk knockout dengan skor sama.
- [ ] Submit menampilkan dialog konfirmasi dan dampak ke klasemen/bracket.
- [ ] Konflik stale menampilkan pesan refresh, bukan mengganti data lokal diam-diam.
- [ ] Admin dapat menggunakan halaman hasil administratif dengan action backend yang sama.
- [ ] Semua route frontend menggunakan Wayfinder.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
php artisan test --compact --filter="operator competition access"
php artisan test --compact --filter="match score page"
npm run types:check
```

## Test Gate Fase 4

- [ ] AC-09 sampai AC-14 hijau.
- [ ] Bagian backend AC-16 hijau.
- [ ] Kalkulator standings memiliki unit test seluruh tie-break.
- [ ] Assigned/unassigned/inactive operator diuji melalui HTTP request.
- [ ] Stale update tidak menimpa hasil.
- [ ] Koreksi standings tidak double count.
- [ ] Koreksi knockout tidak merusak downstream.
- [ ] Seluruh progression berada dalam transaksi.
- [ ] UI tidak menghitung standings/winner sendiri.

Jalankan:

```bash
php artisan test --compact --filter="standings"
php artisan test --compact --filter="match score"
php artisan test --compact --filter="knockout result"
php artisan test --compact --filter="operator"
vendor/bin/pint --dirty --format agent
npm run types:check
npm run lint:check
npm run format:check
```

## Exit Criteria

Fase 4 `DONE` jika operator assigned dapat menjalankan lomba sampai completed, sementara seluruh jalur unauthorized, stale, invalid, dan correction terbukti aman melalui test.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
|         |            |                 |            |       |                 |
