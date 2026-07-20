# Phase 04 — Skor, Klasemen, dan Progres Bagan

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6.2, 7.3–7.5, 8.4, 9.5, 11, 12, 15.5, serta AC-09 sampai AC-14 dan bagian backend AC-16.

Klasemen dan bracket progression harus mengikuti data pertandingan final sebagaimana ditetapkan [PRD bagian 11–12](./PRD.md#11-perhitungan-klasemen). Frontend tidak boleh memiliki algoritma alternatif.

| Metadata        | Nilai                                                                                |
| --------------- | ------------------------------------------------------------------------------------ |
| Status          | DONE                                                                                 |
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

- [x] Definisikan output row: rank, participant, played, won, drawn, lost, score for, score against, difference, points.
- [x] Input hanya participant dan completed match dari satu competition.
- [x] Participant tanpa match tetap muncul dengan statistik nol.
- [x] Gunakan scoring rule competition, termasuk poin negatif.
- [x] Urutkan: points, difference, score for, wins.
- [x] Jika seluruh tie-break sama, gunakan shared rank model `1, 1, 3`.
- [x] Nama hanya menstabilkan urutan display, tidak mengubah rank.
- [x] Pending/bye match tidak masuk kalkulasi.
- [x] Buat dataset menang, seri, kalah, koreksi, tie, dan poin negatif.

### Verifikasi

```bash
php artisan test --compact --filter="standings calculator"
```

Stop jika AC-09 belum hijau. Jangan menghubungkan UI sebelum kalkulator stabil.

## C2 — Validasi dan concurrency update skor

### Backend

- [x] Buat Form Request score dengan authorization policy.
- [x] Validasi score integer minimal nol.
- [x] Match harus milik competition route dan memiliki dua participant diketahui.
- [x] Match bye/pending future tidak dapat diberi skor.
- [x] Competition harus `locked` atau `in_progress`.
- [x] Request membawa `result_version` expected.
- [x] Action membuka transaksi, memeriksa version terbaru, lalu menyimpan result.
- [x] Simpan `result_updated_by`, `result_updated_at`, dan increment version.
- [x] Jika version stale, kembalikan conflict/validation message yang meminta refresh; jangan overwrite diam-diam.

### Test

- [x] Admin dan assigned active operator berhasil update.
- [x] Guest, inactive operator, dan unassigned operator ditolak.
- [x] Invalid score ditolak.
- [x] Cross-competition nested match ditolak (di MatchScoreRequest when match->competition_id !== $competition->id).
- [x] Stale result version ditolak dan data terbaru dipertahankan.

### Verifikasi

```bash
php artisan test --compact --filter="update match score"
php artisan test --compact --filter="stale match result"
```

## C3 — Hasil format kompetisi dan status lifecycle

### Implementasi

- [x] Skor lebih tinggi menghasilkan win/loss; skor sama menghasilkan draw.
- [x] Simpan winner nullable pada draw atau derive secara konsisten.
- [x] Setelah hasil pertama, competition `locked -> in_progress`.
- [x] Setelah koreksi, standings dihitung ulang dari source final matches.
- [x] Setelah seluruh match non-bye completed, status menjadi `completed`.
- [x] Jangan menyimpan statistik inkremental yang berpotensi drift.
- [x] Tampilkan standings hasil backend pada halaman internal.

### Test

- [x] AC-09 dan AC-10.
- [x] Tidak ada double count setelah update match yang sama berulang kali.
- [x] Poin custom dan negatif benar.
- [x] Status tidak completed jika satu match scorable masih pending.
- [x] Hasil terakhir mengubah status ke completed.

### Verifikasi

```bash
php artisan test --compact --filter="competition standings"
php artisan test --compact --filter="competition completion status"
```

## C4 — Hasil dan progression sistem gugur

### Implementasi

- [x] Jika score berbeda, winner otomatis participant dengan score lebih tinggi.
- [x] Jika score sama, `winner_participant_id`/tie-break winner wajib salah satu participant match.
- [x] Simpan metode/keterangan tie-break opsional (win_method).
- [x] Setelah result tersimpan, isi home/away slot next match yang ditentukan generator.
- [x] Next match menjadi ready ketika kedua participant tersedia.
- [x] Bye tetap tidak memerlukan input skor.
- [x] Final completed mengubah competition menjadi completed.
- [x] Seluruh perubahan result dan next slot berada dalam transaksi yang sama.

### Test

- [x] AC-11: draw tanpa tie-break winner ditolak.
- [x] Tie-break winner yang bukan participant ditolak (MatchScoreRequest `after()` validation).
- [x] AC-12: winner masuk slot downstream yang tepat.
- [x] Pemenang final menyelesaikan competition.
- [x] Exception progression tidak meninggalkan result setengah tersimpan (semua ada di DB::transaction).

### Verifikasi

```bash
php artisan test --compact --filter="knockout result progression"
```

## C5 — Koreksi hasil sistem gugur

### Implementasi

- [x] Koreksi score tanpa perubahan winner diizinkan jika version valid.
- [x] Jika winner berubah dan downstream match belum completed, ganti participant pada slot terkait.
- [x] Jangan mengubah participant slot lain.
- [x] Jika downstream match sudah completed, tolak koreksi dengan 422 abort.
- [x] Pastikan koreksi tidak meninggalkan winner lama pada jalur berikutnya (clearDownstreamSlots sebelum advanceWinner).
- [x] Status competition dihitung konsisten setelah koreksi yang diizinkan.

### Test

- [x] Winner sama, score berubah.
- [x] Winner berubah, downstream belum dimainkan.
- [x] AC-13: winner berubah, downstream completed, request ditolak.
- [x] Data tidak berubah ketika request ditolak.
- [x] Stale version dan downstream dependency menghasilkan pesan yang dapat dibedakan.

### Verifikasi

```bash
php artisan test --compact --filter="knockout result correction"
```

## C6 — Dashboard dan halaman operator

### Backend/UI

- [x] Dashboard operator hanya query competition assigned.
- [ ] Daftar match dapat difilter round/status. (dilakukan di hardening)
- [x] Operator dapat melihat rules, participant, bracket/standings internal.
- [x] Form score menampilkan dua participant, score sebelumnya, dan result version.
- [ ] Tie-break winner field hanya tampil untuk knockout dengan skor sama. (dilakukan di hardening)
- [ ] Submit menampilkan dialog konfirmasi dan dampak ke klasemen/bracket. (dilakukan di hardening)
- [x] Konflik stale menampilkan pesan error, bukan mengganti data lokal diam-diam.
- [x] Admin dapat menggunakan halaman hasil administratif dengan action backend yang sama.
- [x] Semua route frontend menggunakan Wayfinder.

### Verifikasi

```bash
php artisan wayfinder:generate --with-form --no-interaction
php artisan test --compact --filter="operator competition access"
php artisan test --compact --filter="match score page"
npm run types:check
```

## Test Gate Fase 4

- [x] AC-09 sampai AC-14 hijau.
- [x] Bagian backend AC-16 hijau.
- [x] Kalkulator standings memiliki unit test seluruh tie-break (10 tests).
- [x] Assigned/unassigned/inactive operator diuji melalui HTTP request.
- [x] Stale update tidak menimpa hasil.
- [x] Koreksi standings tidak double count.
- [x] Koreksi knockout tidak merusak downstream.
- [x] Seluruh progression berada dalam transaksi.
- [x] UI tidak menghitung standings/winner sendiri.

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
| 2026-07-20 | C1 | FR-MATCH-01/02, AC-09 | php artisan test --filter=StandingsCalculator | 10 unit tests PASS | StandingsEntry DTO + StandingsCalculator pure domain class. Tie-break: points, diff, score_for, wins. Shared rank 1,1,3. Negative points supported. |
| 2026-07-20 | C2 | FR-MATCH-03/04/05 | php artisan test --filter="MatchScore" | 21 integration tests PASS | MatchScoreRequest with authorization via Policy, stale version detection, cross-competition validation. |
| 2026-07-20 | C3 | FR-MATCH-06/07, AC-16 | php artisan test --filter="competition completion" | Tests PASS (in MatchScoreTest) | Status lifecycle: locked→in_progress→completed. Standings recalculated from source on every change. |
| 2026-07-20 | C4 | FR-MATCH-08, AC-11/12 | php artisan test --filter="knockout result progression" | Tests PASS | Winner advances to next_match_id slot. Tie-break requires explicit winner. Final completes competition. |
| 2026-07-20 | C5 | FR-MATCH-09, AC-13 | php artisan test --filter="knockout result correction" | Tests PASS | Score correction without winner change allowed. Winner change blocked if downstream completed. clearDownstreamSlots prevents stale winners. |
| 2026-07-20 | C6 | Operator dashboard | php artisan test --compact | 268 tests PASS | Admin Scores page, Operator Scores page, updated Dashboard with competition list. Wayfinder routes generated. |
