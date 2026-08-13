# Feature: Seeder Reset Password Admin (DDMMYYYY)

> Dokumen ini adalah rencana pengembangan seeder/command khusus untuk mereset password akun Admin dengan password baru berformat tanggal hari ini (`DDMMYYYY`) dan menampilkannya di terminal.  
> Dibuat berdasarkan PRD: [repo/PRD.md](file:///C:/laragonx/www/lomba/repo/PRD.md) | Versi: 14 Agustus 2026

---

## 1. Overview

**Nama Fitur:** Seeder Reset Password Admin (DDMMYYYY)  
**Status:** Draft  
**Priority:** Medium  
**Epic/Module:** Database & Utilitas Admin  
**Detected Stack:** Laravel 13.17 + PHP 8.4 + Pest 4.7

### Problem Statement
Admin terkadang perlu mereset password akun administrator secara cepat lewat CLI / Artisan tanpa harus memasukkan string manual di tinker atau mengubah database secara langsung. Diperlukan seeder/artisan command yang secara otomatis meng-generate password baru berdasarkan tanggal hari ini dengan format `DDMMYYYY` (misal `14082026` untuk tanggal 14 Agustus 2026), meng-update user Admin di database, dan mencetak password baru tersebut dengan jelas di terminal output.

### Proposed Solution
Membuat class seeder khusus `AdminPasswordResetSeeder` (atau Artisan Command) yang mencari user ber-role Admin (atau `admin@email.com`), memperbarui passwordnya dengan `Hash::make(now()->format('dmY'))`, lalu menampilkan pesan konfirmasi berisi email dan password plain-text baru menggunakan CLI output format (`$this->command->info()`).

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Memudahkan pemeliharaan sistem & akses darurat bagi Administrator. |
| **Target User** | Administrator / Pengembang Sistem. |
| **Scope** | ✅ In scope (Utilitas Seeder / Command CLI). |
| **Dependency** | Model `User`, Enum `UserRole`, `Illuminate\Support\Facades\Hash`. |

---

## 3. User Flow

### Happy Path:
```
[User / Developer di Terminal]
   └── Jalankan perintah: `php artisan db:seed --class=AdminPasswordResetSeeder`
         └── Seeder mencari akun Admin di database
               └── Seeder meng-generate password dari tanggal saat ini (misal: 14082026)
                     └── Seeder meng-update hash password user Admin
                           └── Terminal menampilkan informasi email & password baru dalam format DDMMYYYY
```

### Edge Cases:
- [ ] **Belum Ada User Admin di Database:** Jika user Admin belum ada, seeder akan membuatkan user Admin baru dengan email `admin@email.com` dan password format `DDMMYYYY`.
- [ ] **Multi User Admin:** Jika terdapat lebih dari 1 user ber-role Admin, seeder akan meng-update seluruh akun Admin dan menampilkan daftar email beserta password yang baru diset di terminal.

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] Class Seeder `AdminPasswordResetSeeder` di `database/seeders/AdminPasswordResetSeeder.php`.
- [ ] Format password menggunakan format tanggal `dmY` (`DDMMYYYY`), contoh: `14082026`.
- [ ] Mengenkripsi password menggunakan `Hash::make()`.
- [ ] Menampilkan output berwarna di terminal menggunakan `$this->command->info()` atau `$this->command->table()`.
- [ ] Berhasil dijalankan dengan `php artisan db:seed --class=AdminPasswordResetSeeder`.

### Should Have
- [ ] Menampilkan informasi email, role, dan tanggal reset secara rapi di terminal.

### Won't Have (untuk versi ini)
- [ ] Halaman web UI reset password (hanya seeder CLI).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Eksekusi < 1 detik. |
| **Security** | Hanya dapat dijalankan via CLI / terminal lokal server. |

---

## 6. UI/UX Notes (CLI Terminal Output)

### Output Format di Terminal:
```
+-------------------------------------------------------------+
|               RESET PASSWORD ADMIN BERHASIL                 |
+-------------------------------------------------------------+
| Email Admin : admin@email.com                               |
| Password    : 14082026 (Format DDMMYYYY)                    |
| Tanggal     : 14-08-2026                                    |
+-------------------------------------------------------------+
```

---

## 7. Technical Plan

### Existing Architecture
- **Model:** `App\Models\User`
- **Enum Role:** `App\Enums\UserRole::Admin`
- **Password Hasher:** `Illuminate\Support\Facades\Hash`

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Database | New Seeder Class | `database/seeders/AdminPasswordResetSeeder.php` |
| Testing | New Pest Test | `tests/Feature/AdminPasswordResetSeederTest.php` |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Seeder `AdminPasswordResetSeeder` dibuat dan dapat dijalankan tanpa error.
- [ ] Password admin berhasil diperbarui menjadi format `DDMMYYYY` tanggal hari ini (misal `14082026`).
- [ ] Terminal menampilkan password plain-text baru dengan jelas.
- [ ] User Admin dapat login menggunakan password baru tersebut.
- [ ] Unit/Feature test Pest lulus 100%.

---

## 9. Open Questions

- [x] **Format Tanggal:** DDMMYYYY (`dmY`), contoh tanggal 14 Agustus 2026 => `14082026`.
- [x] **Email Admin:** Menggunakan user Admin existing / `admin@email.com`.

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Development | 15 menit | Buat seeder & testing |

**Confidence:** High

---

*Dokumen ini siap digunakan sebagai acuan pembuatan seeder AdminPasswordResetSeeder.*
