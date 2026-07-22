# 🏆 Lomba - Sistem Manajemen Turnamen & Kompetisi

**Lomba** adalah platform manajemen turnamen dan kompetisi olahraga/esports berbasis web modern yang dibangun dengan **Laravel 13**, **Inertia.js v3**, **Vue 3**, **Tailwind CSS v4**, dan **Shadcn Vue**.

Aplikasi ini dirancang untuk memudahkan penyelenggara dalam mengelola pendaftaran peserta, pengundian bagan/jadwal pertandingan, pencatatan skor, hingga publikasi bagan gugur dan tabel klasemen secara real-time.

---

## ✨ Fitur Utama

### 1. 🥇 Format Kompetisi Fleksibel
- **Knockout (Sistem Gugur)**: Bagan turnamen dinamis ($2^k$) dilengkapi algoritma penanganan **BYE** otomatis untuk jumlah peserta ganjil (misal 3, 5, 6, 7 peserta).
- **Kompetisi Penuh (Liga / Double Round-Robin)**: Pertandingan sistem kandang & tandang (*Home & Away*).
- **Setengah Kompetisi (Single Round-Robin)**: Pertandingan 1 kali pertemuan antar seluruh peserta.

### 2. 👥 Manajemen Peserta & Bulk Add
- Tambah peserta individual lengkap dengan unggah logo dan singkatan nama (*Short Name*).
- **Bulk Add Participants**: Tambah puluhan peserta sekaligus hanya dengan memasukkan daftar nama (1 baris per peserta) beserta penjelas singkatan otomatis.

### 3. 🎲 Pengundian & Penjadwalan (Draw Generator)
- Acak urutan peserta dan buat bagan/jadwal pertandingan secara otomatis.
- Fitur **Kunci Undian** (*Lock Draw*) untuk mengunci peserta dan memulai kompetisi.

### 4. ⚽ Perhitungan Klasemen & Tie-Break Otomatis
- Tabel Klasemen Otomatis: Main (M), Menang (W), Seri (D), Kalah (L), Gol Memasukkan (GM), Gol Kemasukan (GK), Selisih Gol (SG), dan Poin (Pts).
- Penanganan laga imbang pada sistem gugur dengan pilihan pemenang *Tie-Break*.

### 5. 🔐 Multi-Role Authorization
- **Admin**: Kontrol penuh pembuatan lomba, manajemen operator, pengundian bagan, dan penginputan skor.
- **Operator**: Akses khusus untuk menginput dan memperbarui skor pertandingan lomba yang ditugaskan.
- **Public**: Portal informasi real-time untuk penonton tanpa perlu login (Bagan, Jadwal, Skor & Klasemen Lomba Aktif / Selesai).

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 8.4, Laravel 13, Laravel Fortify (Auth), Laravel Wayfinder (Typed TypeScript Routes)
- **Frontend**: Vue 3 (Composition API), Inertia.js v3, Tailwind CSS v4, Shadcn Vue, Lucide Icons, Vue Sonner
- **Testing**: Pest PHP v4 (290+ Automated Tests Passed)
- **Containerization**: Docker (`serversideup/php:8.4-fpm-nginx`)

---

## 🚀 Panduan Instalasi Lokal

### Prasyarat
- PHP >= 8.4
- Composer >= 2.x
- Node.js >= 20.x & NPM
- Database (MySQL / PostgreSQL / SQLite)

### Langkah Instalasi

1. **Clone repository & masuk ke direktori proyek**:
   ```bash
   git clone <repository-url>
   cd lomba
   ```

2. **Install dependensi PHP & Node.js**:
   ```bash
   composer install
   npm install
   ```

3. **Salin environment file & generate App Key**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database**:
   Sesuaikan `DB_CONNECTION` pada file `.env` (default menggunakan SQLite atau MySQL).

5. **Jalankan Migrasi & Seeder Data Demo**:
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Generate Wayfinder Routes & Build Frontend**:
   ```bash
   php artisan wayfinder:generate
   npm run build
   ```

7. **Jalankan Server Lokal**:
   ```bash
   # Server Laravel
   php artisan serve

   # Vite Dev Server (Opsional jika mengembangkan frontend)
   npm run dev
   ```

---

## 🐳 Menggunakan Docker

Proyek ini dilengkapi dengan `Dockerfile` berbasis `serversideup/php:8.4-fpm-nginx`.

```bash
# Build dan jalankan container
docker compose up -d --build
```
Akses aplikasi melalui browser di `http://localhost:8080`.

---

## 🧪 Pengujian (Testing)

Proyek ini menggunakan **Pest PHP** untuk pengujian otomatis (Unit & Feature Tests):

```bash
# Jalankan seluruh test suite
php artisan test --compact
```

---

## 🔑 Akun Demo (Seeder)

Setelah menjalankan `php artisan db:seed`, Anda dapat menguji aplikasi menggunakan akun berikut:

- **Admin**: `admin@lomba.com` / `password`
- **Operator**: `operator@lomba.com` / `password`

---

## 📄 Lisensi

Proyek ini dirilis di bawah [Lisensi MIT](LICENSE).
