# Phase 01 — Fondasi Akses dan Domain

## Kontrol PRD

> **Sumber kebenaran:** [PRD Sistem Manajemen Lomba](./PRD.md). Fase ini mengimplementasikan PRD bagian 6, 7, 13, 16.1, 16.5, 18, dan Tahap 1 pada bagian 19.

Jika keputusan fase ini bertentangan dengan [PRD](./PRD.md), PRD harus diklarifikasi atau diperbarui sebelum kode dilanjutkan. Dokumen ini tidak boleh digunakan untuk memperluas scope MVP.

| Metadata        | Nilai                                                                                |
| --------------- | ------------------------------------------------------------------------------------ |
| Status          | DONE                                                                                 |
| Prasyarat       | Starter kit dapat dijalankan dan baseline test diketahui                             |
| Hasil akhir     | Schema domain stabil, role aman, akun operator dapat dikelola, policy dasar tersedia |
| Fase berikutnya | [Phase 02](./PHASE-02-ADMIN-COMPETITION-MANAGEMENT.md)                               |

## Traceability PRD

| Referensi PRD   | Target fase                                                           |
| --------------- | --------------------------------------------------------------------- |
| FR-AUTH-01      | Login admin/operator tetap berfungsi melalui Fortify.                 |
| FR-AUTH-02      | User memiliki tepat satu role `admin` atau `operator`.                |
| FR-AUTH-03      | Registrasi publik dinonaktifkan.                                      |
| FR-AUTH-04      | User nonaktif tidak dapat login atau mengubah data.                   |
| FR-AUTH-05      | Tersedia prosedur aman untuk membuat admin pertama.                   |
| FR-AUTH-06      | `/dashboard` mengarahkan user berdasarkan role.                       |
| PRD bagian 13   | Schema, model, enum, relasi, factory, dan constraint domain tersedia. |
| PRD bagian 16.1 | Policy dan autentikasi menjadi enforcement backend.                   |

## Batas Fase

### Termasuk

- baseline test dan konfigurasi test database;
- enum domain;
- migration domain lengkap;
- model, casts, relasi, factory, dan factory state;
- role dan status aktif user;
- menonaktifkan registrasi publik;
- login user aktif/nonaktif;
- redirect dashboard berdasarkan role;
- prosedur pembuatan admin awal;
- CRUD akun operator oleh admin;
- policy dasar lomba dan pertandingan;
- shared auth props/navigation dasar.

### Tidak termasuk

- CRUD lomba;
- pengelolaan peserta dan assignment operator ke lomba;
- shuffle/generator;
- input skor;
- halaman publik baru.

## Keputusan Schema Awal

Schema dibuat pada fase ini agar fase berikutnya tidak berulang kali mengubah kontrak data dasar.

### `users`

- `role`: enum/string yang di-cast ke `UserRole`;
- `is_active`: boolean, default `true`, indexed;
- email tetap unik;
- password tetap menggunakan mekanisme starter kit.

### `competitions`

- metadata: `name`, `slug`, `description`, `starts_at`, `ends_at`;
- `format`: knockout/full competition/half competition;
- `status`: draft/drawn/locked/in progress/completed;
- `win_points`, `draw_points`, `loss_points` nullable untuk knockout;
- `draw_version` untuk mendeteksi preview stale;
- `locked_at` dan `locked_by`;
- timestamps;
- index untuk status, slug, dan daftar admin.

### `competition_operator`

- foreign key competition dan operator user;
- `assigned_by`;
- timestamp assignment;
- unique `(competition_id, user_id)`.

### `participants`

- competition owner;
- `name`, `normalized_name`, `short_name`, `logo_path`;
- `draw_position` nullable;
- unique `(competition_id, normalized_name)`;
- index competition dan draw position.

### `competition_matches`

- competition, round, leg, sequence/position;
- home/first participant dan away/second participant nullable;
- score nullable;
- winner nullable;
- status match;
- win method/notes nullable;
- next match dan next slot nullable untuk knockout;
- `result_version`, `result_updated_by`, `result_updated_at`;
- constraint/index untuk query per competition, round, status, dan urutan.

Gunakan migration Artisan, foreign key yang eksplisit, aksi delete yang sesuai PRD, dan `down()` yang dapat membalik schema. Jangan memasukkan seed data ke migration.

## Urutan Checkpoint

## C1 — Baseline dan test harness

### Implementasi

- [x] Jalankan test starter kit dan catat kegagalan yang sudah ada sebelum perubahan.
- [x] Periksa konfigurasi SQLite test.
- [x] Gunakan trait reset database sesuai pola Pest proyek untuk feature test domain.
- [x] Pertahankan test autentikasi yang masih relevan.
- [x] Siapkan struktur test domain tanpa helper spekulatif.

### Verifikasi

```bash
php artisan test --compact
```

### Gate

- Baseline tercatat.
- Tidak ada kegagalan baru yang disalahartikan sebagai masalah fase.

## C2 — Enum, migration, dan model domain

### Implementasi

- [x] Buat enum `UserRole`, `CompetitionFormat`, `CompetitionStatus`, dan `CompetitionMatchStatus`.
- [x] Buat migration melalui Artisan untuk user role/status dan tabel domain.
- [x] Tambahkan model `Competition`, `Participant`, dan `CompetitionMatch`.
- [x] Definisikan `$fillable`, casts, return type relasi, dan default model yang sama dengan default DB.
- [x] Definisikan relasi user/operator/locker/result updater.
- [x] Buat factory beserta state format/status yang benar-benar digunakan test.
- [x] Buat test schema, casts, relasi, unique constraint, dan cascade/restrict penting.

### Verifikasi terarah

```bash
php artisan migrate:fresh --no-interaction
php artisan test --compact tests/Feature/DomainSchemaTest.php
```

### Debug checkpoint

- Jika migration rollback gagal, selesaikan sebelum membuat controller.
- Jika factory membutuhkan override field terlalu banyak, perbaiki default/state factory.
- Jika relasi menghasilkan query salah, selesaikan nama foreign key sebelum fase berikutnya.

## C3 — Role, registrasi tertutup, dan login aktif

### Implementasi

- [x] Hapus `Features::registration()` dari konfigurasi Fortify.
- [x] Hapus link/halaman registrasi dari navigasi yang dapat diakses publik tanpa menghapus test secara sembarang.
- [x] Perbarui registration feature test agar membuktikan route registrasi tidak tersedia.
- [x] Terapkan pengecekan `is_active` pada pipeline autentikasi Fortify.
- [x] Lindungi seluruh route internal dengan pemeriksaan user aktif agar sesi user yang kemudian dinonaktifkan tidak tetap dapat mengubah data.
- [x] Pastikan operator/admin aktif dapat login dan seluruh user nonaktif ditolak atau dikeluarkan dari sesi internal.
- [x] Pertahankan login throttling starter kit.
- [x] Buat dashboard dispatcher/redirect berdasarkan role.
- [x] Tambahkan test redirect admin dan operator.

### Verifikasi terarah

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
php artisan test --compact tests/Feature/Auth/RegistrationTest.php
php artisan test --compact --filter="dashboard role"
```

### Debug checkpoint

- Bedakan gagal login karena kredensial dan karena akun nonaktif.
- Uji user yang sudah login lalu dinonaktifkan, bukan hanya percobaan login baru.
- Jangan mengandalkan middleware frontend untuk memblokir route registrasi.
- Pastikan redirect tidak membentuk loop melalui `/dashboard`.

## C4 — Bootstrap admin dan pengelolaan operator

### Implementasi

- [x] Sediakan prosedur idempotent untuk membuat admin pertama tanpa password hardcoded; rekomendasi: Artisan command dengan password prompt tersembunyi.
- [x] Akun internal yang dibuat melalui command/admin ditandai terverifikasi agar dapat melewati middleware `verified`; jangan membuka verifikasi untuk registrasi publik.
- [x] Test command membuat admin aktif dan terverifikasi serta menolak/menangani email duplikat dengan aman.
- [x] Buat policy dan Form Request pengelolaan operator.
- [x] Buat route/controller admin untuk daftar, tambah, ubah identitas, aktifkan, dan nonaktifkan operator.
- [x] Password operator di-hash dan tidak pernah dikirim kembali sebagai prop.
- [x] Operator baru dibuat aktif dan terverifikasi karena akunnya diprovisikan admin.
- [x] Admin tidak dapat mengubah operator menjadi admin melalui form operator.
- [x] Buat halaman Inertia pengelolaan operator menggunakan shadcn-vue.
- [x] Gunakan Wayfinder pada link dan form.
- [x] Tambahkan test guest/operator forbidden dan admin success.

### Verifikasi terarah

```bash
php artisan test --compact --filter="operator management"
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
```

## C5 — Policy domain dan shared authorization

### Implementasi

- [x] Buat `CompetitionPolicy` dengan ability view internal, create, update, delete, draw, lock, dan update score.
- [x] Buat `CompetitionMatchPolicy` jika ownership match lebih jelas dipisahkan.
- [x] Admin diizinkan sesuai matriks PRD.
- [x] Operator hanya dapat view/update score jika aktif dan ditugaskan ke competition.
- [x] Guest tidak memiliki izin internal.
- [x] Gunakan policy backend; prop `can` hanya untuk presentasi UI.
- [x] Tambahkan unit/feature test policy untuk seluruh kombinasi role dan assignment.

### Verifikasi terarah

```bash
php artisan test --compact --filter="competition policy"
```

## Test Gate Fase 1

- [x] Migration fresh dan rollback berhasil.
- [x] Login admin/operator aktif berhasil.
- [x] Login user nonaktif ditolak.
- [x] Sesi user yang dinonaktifkan tidak dapat mengakses route internal.
- [x] Registrasi publik tidak tersedia.
- [x] Admin awal dan operator dapat dibuat terverifikasi tanpa credential di source control.
- [x] Hanya admin dapat mengelola operator.
- [x] Policy membedakan operator assigned/unassigned.
- [x] Factory dapat membuat semua status/format yang dibutuhkan fase selanjutnya.

Jalankan:

```bash
php artisan test --compact tests/Feature/Auth
php artisan test --compact --filter="operator management"
php artisan test --compact --filter="competition policy"
vendor/bin/pint --dirty --format agent
npm run types:check
npm run lint:check
npm run format:check
```

## Exit Criteria

Fase 1 dapat ditandai `DONE` jika:

- seluruh test gate hijau;
- schema tidak memiliki keputusan terbuka yang memblokir fase 2–4;
- tidak ada route registrasi publik;
- akses backend tidak bergantung pada tombol tersembunyi;
- dokumentasi fase dan log implementasi diperbarui.

## Log Implementasi

| Tanggal | Checkpoint | Requirement PRD | Verifikasi | Hasil | Catatan/deviasi |
| ------- | ---------- | --------------- | ---------- | ----- | --------------- |
| 2026-07-20 | C1–C5 + Gate | FR-AUTH-01–06, PRD 13, 16.1 | 88 tests, 254 assertions, 3.1s; migration fresh+rollback, format+typecheck OK | Lulus | Migration user digabung ke 1 file |
