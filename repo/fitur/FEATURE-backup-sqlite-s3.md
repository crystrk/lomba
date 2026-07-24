# Feature: Backup SQLite Otomatis ke S3

> Dokumen ini adalah rencana pengembangan fitur Backup SQLite Otomatis ke S3.
> Dibuat berdasarkan PRD: `repo/PRD.md` | Versi: 24 Juli 2026

---

## 1. Overview

**Nama Fitur:** Backup SQLite Otomatis ke S3
**Status:** Implemented (MVP)
**Priority:** High *[asumsi: database SQLite adalah satu-satunya sumber kebenaran seluruh data lomba; kehilangan data tanpa backup berdampak fatal pada produk]*
**Epic/Module:** Operasional & Infrastruktur (di luar modul domain lomba)
**Detected Stack:** PHP 8.4, Laravel 13, SQLite, Flysystem S3 v3 (`league/flysystem-aws-s3-v3: 3.0` sudah terinstall di `composer.json`), Pest 4, deployment Docker via Coolify

### Problem Statement

Seluruh data produk — lomba, peserta, pertandingan, skor, dan akun pengguna — tersimpan dalam satu file SQLite (`database/database.sqlite` lokal, `/var/www/db/database.sqlite` di produksi). Saat ini tidak ada mekanisme backup sama sekali. Kerusakan file, kesalahan operasional, atau kegagalan volume Docker akan menghapus seluruh data lomba tanpa kemungkinan pemulihan.

Menyalin file SQLite secara langsung (`copy`) tidak aman karena dapat menghasilkan snapshot yang tidak konsisten ketika aplikasi sedang menulis. Backup harus menggunakan mekanisme konsisten bawaan SQLite.

### Proposed Solution

Buat Artisan command yang membuat snapshot konsisten database menggunakan `VACUUM INTO` ke file sementara lokal, mengompresinya dengan gzip, lalu mengunggahnya ke object storage kompatibel S3 dengan nama file ber-timestamp, dan menghapus backup lama melebihi batas retensi 14 hari. Command dijadwalkan otomatis setiap 3 jam melalui Laravel Scheduler dengan proteksi `withoutOverlapping`; scheduler dijalankan oleh scheduled task Coolify.

`VACUUM INTO` (bukan `VACUUM` biasa) membuat salinan database yang konsisten ke file baru tanpa mengubah database aktif dan tanpa mengunci penulis dalam waktu lama — aman dijalankan saat aplikasi menerima request.

### Keputusan Product Owner

- Retensi backup: **14 hari** = 112 backup terakhir (8 backup/hari).
- Kompresi **gzip selalu aktif**; objek di S3 berakhiran `.sqlite.gz`.
- Runner scheduler produksi: **scheduled task Coolify** yang mengeksekusi `php artisan schedule:run` setiap menit di container `app`.
- Target penyimpanan: **object storage kompatibel S3** (bukan harus AWS), via `AWS_ENDPOINT` dan path-style endpoint.
- Alerting kegagalan: **log terstruktur saja**, tanpa kanal notifikasi tambahan.
- **Bucket lifecycle rule** diterapkan sebagai pelengkap pruning aplikasi (pertahanan lapis kedua).

---

## 2. Alignment dengan PRD

| Aspek            | Keterangan                                                                                                                                        |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Product Goal** | Melindungi "satu sumber data untuk seluruh proses" (PRD §2) dan mendukung kebutuhan pemeliharaan pada PRD §16.5.                                   |
| **Target User**  | Tidak ada user-facing flow; fitur ini untuk operator infrastruktur/admin sistem. Tidak mengubah persona admin/operator/publik pada PRD §6.        |
| **Scope**        | ✅ In scope sebagai kebutuhan operasional non-fungsional; tidak menambah fitur domain, tidak mengubah lifecycle lomba, skor, maupun kalkulasi.     |
| **Dependency**   | Disk `s3` di `config/filesystems.php` (sudah tersedia), endpoint dan kredensial S3-compatible pada environment produksi (belum diisi), dan scheduled task Coolify sebagai runner scheduler (perlu dibuat). |

Fitur ini tidak berkonflik dengan daftar non-goal PRD §5.2. Pengecualian "Notifikasi WhatsApp, email hasil, atau push notification" berlaku untuk notifikasi ke pengguna produk; alerting kegagalan backup versi ini cukup melalui log aplikasi. PRD §20 juga mencatat risiko konkurensi SQLite — desain backup memilih `VACUUM INTO` dan `withoutOverlapping` untuk selaras dengan risiko tersebut.

---

## 3. User Flow

Fitur ini berjalan otomatis di background; tidak ada interaksi pengguna. Flow di bawah ditulis dari sudut pandang sistem.

**Happy Path:**

```text
Scheduler terpicu setiap 3 jam → command membaca path database SQLite dari config →
VACUUM INTO membuat snapshot konsisten ke file sementara di storage lokal →
snapshot dikompresi gzip → file `.gz` di-stream ke S3 (key: backups/{env}/YYYY-MM-DD/HHmmss-database.sqlite.gz) →
file sementara lokal dihapus → backup lama di luar retensi 14 hari dipruning → hasil dicatat ke log
```

**Manual Path (ops):**

```text
Administrator server menjalankan `php artisan app:backup-database` → flow sama seperti di atas tanpa menunggu jadwal
```

**Edge Cases:**

- [ ] Upload ke S3 gagal (kredensial salah, jaringan putus, bucket tidak ada) → command gagal dengan exit code non-zero, error di-log, file sementara lokal tetap dihapus, tidak ada file parsial yang dianggap valid di S3.
- [ ] Eksekusi sebelumnya masih berjalan saat jadwal berikutnya tiba → `withoutOverlapping` melewatkan run tersebut dan mencatatnya.
- [ ] Disk lokal penuh saat membuat file sementara → command gagal sebelum menyentuh S3, error di-log.
- [ ] Kredensial/bucket belum dikonfigurasi → command gagal cepat dengan pesan yang jelas (fail fast), bukan error stack mentah.
- [ ] Database sedang menulis saat backup → `VACUUM INTO` menunggu/mengambil read transaction; pada database sebesar target MVP (maks. 64 peserta per lomba) ini berlangsung singkat.
- [ ] Koneksi database bukan SQLite (docker-compose mendukung override ke pgsql/mysql) → command menolak berjalan dengan pesan bahwa backup ini khusus SQLite.

---

## 4. Functional Requirements

### Must Have (MVP)

- [x] Artisan command `app:backup-database` yang membuat snapshot konsisten via `VACUUM INTO` ke file sementara di disk lokal.
- [x] Kompresi gzip pada snapshot sebelum upload (streaming, tanpa memuat seluruh file ke memory).
- [x] Upload snapshot terkompresi ke disk S3 dengan key ber-timestamp dan prefix per lingkungan, misal `backups/production/2026-07-24/150000-database.sqlite.gz`.
- [x] File sementara lokal (`.sqlite` dan `.gz`) selalu dihapus setelah upload berhasil maupun gagal (finally block).
- [x] Penjadwalan otomatis setiap 3 jam di `routes/console.php` dengan `withoutOverlapping`.
- [x] Pruning: backup di luar batas retensi dihapus dari S3. Retensi ditetapkan **14 hari = 112 backup terakhir** (8 backup/hari), dikonfigurasi via config.
- [x] Fail fast dengan pesan jelas jika koneksi DB bukan SQLite atau konfigurasi S3 belum lengkap.
- [x] Logging terstruktur hasil setiap run: sukses (key, ukuran, durasi) atau gagal (exception).

### Should Have

- [x] Verifikasi pasca-upload: bandingkan ukuran file `.gz` lokal vs `Content-Length` di S3 sebelum menghapus file sementara.
- [ ] Metadata objek S3 (misal `app`, `environment`, `created-at`) untuk audit.

### Won't Have (untuk versi ini)

- [ ] Restore otomatis atau tombol restore dari UI — restore dilakukan manual oleh administrator server (prosedur didokumentasikan di bagian Operational Impact).
- [ ] Notifikasi email/WhatsApp kegagalan backup — dikecualikan dari MVP pada PRD §5.2; kegagalan cukup tercatat di log.
- [ ] Backup multi-destination (S3 + lokal cadangan, dst).
- [ ] Enkripsi sisi aplikasi — mengandalkan enkripsi bucket S3 (SSE) yang diatur di infrastruktur.
- [ ] Backup database non-SQLite.

---

## 5. Non-Functional Requirements

| Aspek           | Requirement                                                                                                                          |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| **Performance** | Snapshot + upload selesai < 60 detik untuk ukuran database target MVP; eksekusi tidak memblokir request web (berjalan via scheduler). |
| **Security**    | Kredensial AWS hanya via environment variable; objek S3 bersifat private; key backup tidak mengandung data sensitif di namanya.      |
| **Reliability** | Tidak ada backup parsial yang tampak valid: upload diverifikasi sebelum file lokal dihapus; run tumpang tindih dicegah.              |
| **Scalability** | Ukuran database tumbuh linear dengan jumlah lomba/pertandingan; retensi menjaga penggunaan storage S3 tetap terbatas.                |
| **Auditability**| Setiap run meninggalkan jejak log terstruktur (timestamp, key S3, ukuran, durasi, status).                                            |

---

## 6. UI/UX Notes

Tidak ada antarmuka pengguna pada fitur ini. Seluruh interaksi melalui command line dan log.

**Touchpoints:**

- [ ] Tidak ada screen/page baru.
- [ ] Tidak ada komponen Vue baru atau yang dimodifikasi.
- [ ] Dokumentasi prosedur restore untuk administrator (di dokumen ini, bagian Operational Impact).

---

## 7. Technical Plan

### Existing Architecture

- **Stack:** Laravel 13 + Inertia 3 + Vue 3; database default `sqlite` (terverifikasi via `php artisan config:show database.default`).
- **S3 sudah siap pakai:** `league/flysystem-aws-s3-v3: 3.0` terinstall (`composer.json`), disk `s3` sudah didefinisikan (`config/filesystems.php` baris 50-61), variabel `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` sudah ada di `.env` dan `.env.example` (nilai masih kosong).
- **Command pattern:** command ditempatkan di `app/Console/Commands/` mengikuti `app/Console/Commands/MakeAdmin.php` (signature dengan opsi, `handle(): int`, return `self::SUCCESS`/`self::FAILURE`).
- **Scheduler:** `routes/console.php` saat ini hanya berisi command `inspire` bawaan — belum ada jadwal apa pun. `bootstrap/app.php` memuat `routes/console.php` via `withRouting(commands: ...)`.
- **Deployment:** Docker via Coolify (`docker-compose.yml`, network `coolify`). Hanya ada service `app` (+ one-shot `db-permissions`) — **belum ada runner scheduler/cron**. Path database produksi: `/var/www/db/database.sqlite` pada volume `db-data`. Queue connection `database`.
- **Testing:** Pest 4 (`tests/Feature`, `tests/Unit`); `Storage::fake()` tersedia untuk mengisolasi disk.

### Implementation Impact

| Layer          | Perubahan                                                                                  | Lokasi/Komponen                                                                 |
| -------------- | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------- |
| Backend        | Command backup baru + config backup baru                                                   | `app/Console/Commands/BackupDatabase.php`, `config/backup.php`, `routes/console.php` |
| Frontend       | Tidak ada                                                                                  | —                                                                               |
| Database       | Tidak ada migration; database hanya dibaca oleh `VACUUM INTO`                              | —                                                                               |
| Infrastructure | Runner scheduler di produksi + variabel environment AWS + (opsional) konfigurasi retensi   | `docker-compose.yml` / konfigurasi Coolify, `.env.example`                      |

### Backend — Laravel

- **Command:** `php artisan make:command BackupDatabase --no-interaction` → signature `app:backup-database`. Mengikuti pola `MakeAdmin.php`.
- **Business logic (di dalam command, langsung dan sederhana):**
  1. Validasi `DB_CONNECTION` adalah `sqlite` dan konfigurasi disk S3 lengkap; jika tidak, gagal cepat dengan pesan jelas.
  2. Ambil path absolut database dari `config('database.connections.sqlite.database')`.
  3. Jalankan `VACUUM INTO '<path sementara>'` melalui koneksi database (`DB::statement`), dengan path tujuan unik di `storage/app/backups/tmp/`. *[catatan: gunakan quoting path yang aman; di Windows path mengandung backslash/spasi]*
  4. Kompresi snapshot ke `*.sqlite.gz` secara streaming (`gzopen`/`gzwrite` per chunk) agar file tidak dimuat penuh ke memory.
  5. Stream file `.gz` ke disk S3: `Storage::disk(config('backup.disk'))->put($key, fopen($path, 'r'))`.
  6. Verifikasi ukuran objek di S3, lalu hapus seluruh file sementara dalam `finally`.
  7. Pruning: list objek dengan prefix, urutkan, hapus yang melebihi `config('backup.retention')`.
  8. Log hasil via `Log::info` / `Log::error` dengan context terstruktur.
- **Config baru `config/backup.php`:** `disk` (default `s3`), `prefix` (default `backups/{app_env}`), `retention` (default `112`, setara 14 hari × 8 backup/hari). Semua env-driven.
- **Scheduling (`routes/console.php`):**

  ```php
  Schedule::command('app:backup-database')
      ->everyThreeHours()
      ->withoutOverlapping()
      ->runInBackground();
  ```

  Runner di produksi: **scheduled task Coolify** yang mengeksekusi `php artisan schedule:run` setiap menit di dalam container `app`. Tidak ada perubahan pada `docker-compose.yml`.

- **Authorization:** tidak relevan — command hanya dapat dijalankan dari server (CLI), tidak terekspos via HTTP.
- **Async processing:** tidak menggunakan queue; scheduler cukup untuk ukuran database MVP.

### Data Model

Tidak ada perubahan schema. File backup di S3 adalah artefak di luar database.

### Security & Privacy

- [ ] Objek S3 private (default Flysystem); tidak ada URL publik untuk backup.
- [ ] Kredensial AWS tidak di-commit; `.env.example` hanya berisi key kosong (sudah demikian).
- [ ] Key backup tidak memuat nama pengguna/data pribadi; hanya timestamp dan nama file generik.
- [ ] Pruning menghapus data historis sesuai retensi — mendukung minimasi retensi data.
- [ ] Command tidak menerima input dari request web → tidak ada permukaan CSRF/injection baru; argumen CLI tervalidasi.

### Testing Strategy

| Level     | Skenario                                                                                                                                                              | Tool Existing |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| Backend   | Feature test command dengan `Storage::fake('s3')`: snapshot `.sqlite.gz` ter-upload dengan key ber-timestamp; file sementara terhapus; retensi menghapus backup di luar 112 objek terbaru; exit code failure saat koneksi bukan sqlite; command tidak menimpa objek existing pada run yang sama. | Pest 4        |
| Backend   | Uji konsistensi snapshot: tulis data ke DB, jalankan command, dekompresi objek `.gz` dari disk fake, buka hasilnya sebagai SQLite kedua, verifikasi data terbaca utuh.                                                                                                                          | Pest 4        |
| Frontend  | Tidak ada.                                                                                                                                                            | —             |
| End-to-end| Tidak ada (belum ada tooling browser E2E di repo); validasi jadwal cukup via `php artisan schedule:list`.                                                              | —             |

### Operational Impact

- **Migration/backfill:** tidak ada.
- **Scheduler runner:** gunakan **scheduled task Coolify** yang mengeksekusi `php artisan schedule:run` setiap menit di container `app`. Deployment saat ini belum menjalankan scheduler, jadi task ini **wajib dibuat sebelum fitur aktif di produksi**.
- **Environment:** isi `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, serta `AWS_ENDPOINT` (URL object storage kompatibel S3) dan `AWS_USE_PATH_STYLE_ENDPOINT=true` di produksi; tambah opsional `BACKUP_DISK`, `BACKUP_PREFIX`, `BACKUP_RETENTION` ke `.env.example`.
- **Deployment:** perintah `php artisan app:backup-database` dapat dijalankan manual pasca-deploy untuk verifikasi pertama; tidak ada downtime.
- **Observability:** log terstruktur per run menjadi satu-satunya kanal alerting (keputusan PO); kegagalan dipantau dari log Coolify/Laravel.
- **Prosedur restore (dokumentasi untuk administrator):**
  1. Unduh objek backup `.sqlite.gz` dari S3 ke server.
  2. Hentikan traffic tulis (maintenance mode / stop container app).
  3. Ekstrak dengan `gunzip` hingga diperoleh file `.sqlite` utuh.
  4. Ganti `/var/www/db/database.sqlite` dengan file hasil ekstrak.
  5. Pastikan ownership `33:33` dan permission sesuai service `db-permissions`.
  6. Jalankan kembali aplikasi; verifikasi `/up` dan beberapa halaman lomba.

### Integration

- [ ] Object storage **kompatibel S3** (keputusan PO): dikonfigurasi via `AWS_ENDPOINT` dan `AWS_USE_PATH_STYLE_ENDPOINT=true` yang sudah didukung `config/filesystems.php`. Menggunakan dependency `league/flysystem-aws-s3-v3` yang sudah terinstall — tidak ada package baru.
- [ ] **Bucket lifecycle rule** di sisi S3 sebagai pelengkap pruning aplikasi (misal hapus objek di prefix `backups/` yang lebih tua dari 30 hari) — pertahanan lapis kedua jika pruning aplikasi gagal. Diatur di level infrastruktur, bukan kode aplikasi.

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:

- [x] `php artisan app:backup-database` menghasilkan satu objek `.sqlite.gz` valid di S3 dengan key ber-timestamp dan seluruh file sementara lokal terhapus.
- [x] File hasil backup (setelah dekompresi) dapat dibuka sebagai database SQLite dan memuat data yang sama dengan database sumber (terverifikasi di test).
- [x] `php artisan schedule:list` menampilkan jadwal `app:backup-database` setiap 3 jam, dan run tumpang tindih tidak terjadi (`withoutOverlapping`).
- [x] Retensi terpenuhi: setelah jumlah backup melebihi 112 objek (14 hari), objek terlama terhapus dari S3.
- [x] Kegagalan konfigurasi (kredensial kosong / koneksi non-SQLite) menghasilkan exit code non-zero dan pesan error yang jelas, tanpa artefak parsial.
- [ ] Scheduled task Coolify aktif menjalankan `schedule:run` setiap menit dan backup terlihat di S3 dalam interval ≤ 3 jam setelah deploy.
- [ ] Bucket lifecycle rule pelengkap terkonfigurasi di sisi S3.
- [x] Test Pest untuk skenario di atas berhasil dijalankan (`php artisan test --compact`).
- [x] Tidak ada regression pada fitur existing.
- [x] `.env.example` dan dokumen operasional restore diperbarui.

---

## 9. Keputusan dan Sisa Pertanyaan

> Seluruh open questions awal telah diputuskan Product Owner pada 24 Juli 2026.

| Pertanyaan                    | Keputusan                                                                                          |
| ----------------------------- | -------------------------------------------------------------------------------------------------- |
| Retensi backup                | 14 hari = 112 backup terakhir (8 backup/hari), config-driven.                                       |
| Kompresi                      | Gzip selalu aktif; objek di S3 berakhiran `.sqlite.gz`.                                             |
| Runner scheduler produksi     | Scheduled task Coolify yang mengeksekusi `php artisan schedule:run` setiap menit di container `app`. |
| Penyimpanan                   | Object storage kompatibel S3 via `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT=true`.               |
| Alerting kegagalan            | Log terstruktur saja; tidak ada kanal notifikasi tambahan pada versi ini.                           |
| Lifecycle rule bucket         | Diterapkan sebagai pelengkap pruning aplikasi (pertahanan lapis kedua).                             |

Sisa prasyarat operasional (bukan keputusan produk):

- [ ] Endpoint, bucket, dan kredensial object storage kompatibel S3 produksi harus tersedia sebelum deploy.
- [ ] Scheduled task Coolify harus dibuat saat/pasca deploy pertama.

---

## 10. Timeline Estimasi

| Fase          | Estimasi    | Keterangan                                                                              |
| ------------- | ----------- | --------------------------------------------------------------------------------------- |
| Design & Spec | Selesai     | Seluruh keputusan diambil pada 24 Juli 2026.                                             |
| Development   | 1 hari      | Command, config, scheduling, pruning selesai pada 24 Juli 2026.                          |
| Testing       | 0.5 hari    | Test Pest (4 test, 15 assertions) selesai; uji end-to-end menunggu kredensial S3.        |
| Release       | *[menyusul]* | Menunggu ketersediaan endpoint/bucket/kredensial S3-compatible produksi.                |

**Confidence:** High — kode dan test selesai; blocker hanya prasyarat infrastruktur produksi (kredensial S3 + scheduled task Coolify).

---

*Dokumen ini akan terus diperbarui selama proses development.*
