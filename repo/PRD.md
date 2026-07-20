# Product Requirements Document (PRD)

## Sistem Manajemen Lomba

| Informasi     | Nilai                           |
| ------------- | ------------------------------- |
| Versi dokumen | 1.0                             |
| Status        | Draft untuk implementasi MVP    |
| Tanggal       | 20 Juli 2026                    |
| Platform      | Aplikasi web responsif          |
| Backend       | Laravel 13, PHP 8.4             |
| Frontend      | Inertia.js 3, Vue 3, TypeScript |
| UI            | shadcn-vue, Tailwind CSS 4      |
| Database      | SQLite                          |

---

## 1. Ringkasan Produk

Sistem Manajemen Lomba adalah aplikasi web untuk membuat, menjalankan, dan mempublikasikan lomba berbasis tim dengan tiga format pertandingan:

1. **Sistem gugur** — tim yang kalah tersingkir dan tim yang menang maju ke babak berikutnya.
2. **Kompetisi penuh** — setiap tim bertemu dua kali dengan tim lain (dua leg/kandang-tandang).
3. **Setengah kompetisi** — setiap tim bertemu satu kali dengan tim lain.

Admin mengatur lomba, aturan poin, peserta, operator, dan hasil pengundian. Operator memasukkan skor akhir pertandingan yang menjadi tanggung jawabnya. Sistem menghitung klasemen atau memperbarui bagan sistem gugur secara otomatis. Pengunjung dapat melihat lomba aktif, jadwal, hasil, bagan, dan klasemen tanpa login.

## 2. Latar Belakang

Pengelolaan lomba secara manual rentan terhadap beberapa masalah:

- penyusunan bagan atau jadwal memakan waktu dan dapat dianggap tidak adil;
- perubahan peserta sebelum lomba dimulai sulit dikelola;
- penghitungan poin, selisih skor, dan peringkat rawan salah;
- hasil pertandingan tidak langsung tersedia untuk publik;
- tidak ada pembatasan yang jelas antara pengelola lomba dan petugas pencatat skor.

Produk ini menyediakan satu sumber data untuk seluruh proses, mulai dari pembuatan lomba sampai publikasi hasil.

## 3. Tujuan Produk

### 3.1 Tujuan utama

- Mendukung tiga format lomba dalam satu aplikasi.
- Memungkinkan admin mengatur nilai poin menang, seri, dan kalah per lomba.
- Menghasilkan susunan pertandingan secara otomatis dan acak.
- Menjaga susunan peserta dan pertandingan agar tidak berubah setelah dikunci.
- Memungkinkan operator memperbarui skor dengan akses terbatas.
- Menghitung klasemen dan progres bagan secara otomatis serta konsisten.
- Menyediakan informasi lomba yang mudah diakses publik.

### 3.2 Indikator keberhasilan MVP

- Admin dapat membuat dan mengunci ketiga jenis lomba tanpa menyusun pertandingan secara manual.
- Jumlah pertandingan yang dihasilkan sesuai format dan jumlah peserta.
- Setiap skor final langsung menghasilkan klasemen atau progres bagan yang benar.
- Operator tidak dapat mengubah lomba yang tidak ditugaskan kepadanya.
- Publik dapat membuka lomba aktif tanpa autentikasi.
- Tidak ada perubahan peserta, format, atau aturan poin setelah lomba dikunci.

## 4. Definisi dan Keputusan Produk

Definisi berikut digunakan untuk menghindari perbedaan interpretasi selama implementasi:

| Istilah            | Definisi dalam produk                                                                                                 |
| ------------------ | --------------------------------------------------------------------------------------------------------------------- |
| Lomba              | Satu kompetisi yang memiliki format, peserta, aturan, susunan pertandingan, dan operator sendiri.                     |
| Kompetisi penuh    | Double round-robin; setiap pasangan tim bertemu dua kali dengan posisi tim pertama dan kedua dibalik pada leg kedua.  |
| Setengah kompetisi | Single round-robin; setiap pasangan tim bertemu tepat satu kali.                                                      |
| Sistem gugur       | Single-elimination; satu kekalahan mengeliminasi tim dari lomba.                                                      |
| Shuffle/pengundian | Pengacakan urutan peserta untuk membentuk bagan atau jadwal awal.                                                     |
| Kunci lomba        | Finalisasi susunan pertandingan. Setelah dikunci, format, aturan poin, peserta, dan hasil shuffle tidak dapat diubah. |
| Skor final         | Hasil resmi sebuah pertandingan yang sudah disimpan operator/admin dan ikut dalam kalkulasi.                          |
| Lomba aktif        | Lomba berstatus `locked` atau `in_progress`; tampil pada landing page publik.                                         |

### 4.1 Keputusan tentang poin

- Aturan poin berlaku untuk **kompetisi penuh** dan **setengah kompetisi**.
- Admin wajib mengisi poin menang, seri, dan kalah untuk kedua format tersebut.
- Nilai awal yang disarankan adalah menang `3`, seri `1`, dan kalah `0`, tetapi admin dapat mengubahnya sebelum lomba dikunci.
- Nilai poin berupa bilangan bulat dan boleh bernilai negatif untuk mengakomodasi aturan khusus.
- Sistem gugur tidak menggunakan poin klasemen. Form poin tidak ditampilkan ketika format sistem gugur dipilih.

### 4.2 Keputusan tentang skor sistem gugur

- Pertandingan sistem gugur wajib menghasilkan satu tim yang maju.
- Jika skor akhir tidak sama, tim dengan skor lebih tinggi otomatis menjadi pemenang.
- Jika skor akhir sama, operator wajib memilih **pemenang tie-break** sebelum hasil dapat disimpan.
- Cara tie-break (misalnya penalti, overtime, atau keputusan juri) dapat dicatat sebagai keterangan, tetapi perhitungannya tidak menjadi fitur khusus pada MVP.

## 5. Ruang Lingkup

### 5.1 Termasuk dalam MVP

- Login pengguna internal.
- Dua role: admin dan operator.
- Pengelolaan akun operator oleh admin.
- CRUD lomba pada tahap draft.
- Penugasan satu atau lebih operator ke sebuah lomba.
- Pengaturan poin menang, seri, dan kalah.
- Pengelolaan peserta/tim per lomba.
- Shuffle berulang sebelum penguncian.
- Pembuatan bagan sistem gugur.
- Pembuatan jadwal round-robin satu atau dua leg.
- Penguncian lomba.
- Input dan koreksi skor final sesuai batasan pertandingan.
- Kalkulasi klasemen otomatis.
- Progres pemenang otomatis pada bagan sistem gugur.
- Landing page publik berisi lomba aktif.
- Detail publik berupa bagan atau klasemen dan daftar pertandingan.
- Tampilan responsif untuk desktop dan perangkat seluler.

### 5.2 Tidak termasuk dalam MVP

- Pendaftaran tim secara mandiri oleh publik.
- Data anggota/pemain di dalam tim.
- Format grup yang dilanjutkan ke fase gugur.
- Sistem gugur dua leg atau double-elimination.
- Seeding, pot undian, atau proteksi agar tim tertentu tidak bertemu.
- Penjadwalan lapangan, wasit, lokasi, atau benturan jam.
- Pembayaran, tiket, dan biaya pendaftaran.
- Notifikasi WhatsApp, email hasil, atau push notification.
- Live score per kejadian pertandingan.
- Pembaruan real-time berbasis WebSocket; data terbaru tersedia setelah request/refresh.
- Impor/ekspor Excel, PDF, atau pencetakan bagan.
- Multi-organisasi/multi-tenant.
- Tie-breaker klasemen yang dapat dikonfigurasi admin.

## 6. Pengguna dan Hak Akses

### 6.1 Admin

Admin adalah pengelola penuh aplikasi. Admin dapat:

- membuat, melihat, mengubah, dan menghapus lomba yang masih draft;
- menentukan jenis lomba;
- mengatur nilai poin;
- menambahkan, mengubah, dan menghapus peserta sebelum lomba dikunci;
- melakukan shuffle berulang;
- mengunci lomba;
- membuat/nonaktifkan akun operator;
- menugaskan atau melepas operator dari lomba;
- melihat seluruh lomba;
- memasukkan atau mengoreksi skor sebagai tindakan administratif;
- melihat identitas pengguna terakhir yang mengubah sebuah hasil.

### 6.2 Operator

Operator adalah petugas pencatat hasil. Operator dapat:

- melihat daftar lomba yang ditugaskan kepadanya;
- melihat peserta, aturan, jadwal, klasemen, dan bagan lomba tersebut;
- memasukkan skor final pertandingan yang sudah siap dimainkan;
- mengoreksi skor selama koreksi tidak merusak hasil babak berikutnya;
- melihat dampak skor terhadap klasemen atau bagan.

Operator tidak dapat:

- membuat atau menghapus lomba;
- mengubah format, aturan poin, atau peserta;
- melakukan shuffle atau mengunci lomba;
- mengakses halaman pengelolaan lomba yang tidak ditugaskan kepadanya;
- membuat atau mengubah akun pengguna lain.

### 6.3 Publik

Pengunjung publik tidak memerlukan akun. Publik dapat:

- melihat daftar lomba aktif;
- membuka detail lomba aktif atau selesai melalui URL publik;
- melihat peserta, jadwal, skor, progres bagan, dan klasemen sesuai format.

Publik tidak dapat mengakses kontrol administratif atau mengubah data.

### 6.4 Matriks izin

| Aksi                           | Admin | Operator ditugaskan | Operator tidak ditugaskan  | Publik |
| ------------------------------ | :---: | :-----------------: | :------------------------: | :----: |
| Melihat lomba aktif            |  Ya   |         Ya          | Ya, melalui halaman publik |   Ya   |
| Melihat seluruh lomba internal |  Ya   |        Tidak        |           Tidak            | Tidak  |
| Membuat/mengubah draft lomba   |  Ya   |        Tidak        |           Tidak            | Tidak  |
| Mengelola peserta              |  Ya   |        Tidak        |           Tidak            | Tidak  |
| Shuffle dan kunci              |  Ya   |        Tidak        |           Tidak            | Tidak  |
| Mengelola operator             |  Ya   |        Tidak        |           Tidak            | Tidak  |
| Memasukkan skor                |  Ya   |         Ya          |           Tidak            | Tidak  |
| Melihat klasemen/bagan         |  Ya   |         Ya          |     Hanya versi publik     |   Ya   |

Semua otorisasi wajib diperiksa di backend. Menyembunyikan tombol di frontend bukan pengganti otorisasi.

## 7. Siklus Status Lomba

```text
draft ──shuffle──> drawn ──shuffle ulang──> drawn
  ▲                    │
  └──ubah peserta──────┘
                       │ kunci
                       ▼
                    locked ──skor pertama──> in_progress ──semua selesai──> completed
```

### 7.1 `draft`

- Data lomba dan peserta dapat diubah.
- Belum memiliki susunan pertandingan yang valid.
- Tidak tampil di halaman publik.

### 7.2 `drawn`

- Hasil shuffle terakhir disimpan dan tetap sama setelah halaman dimuat ulang.
- Admin dapat shuffle ulang.
- Perubahan peserta membatalkan susunan pertandingan dan mengembalikan status ke `draft`.
- Belum tampil di halaman publik.

### 7.3 `locked`

- Susunan pertandingan sudah final.
- Format, aturan poin, peserta, dan hasil undian tidak dapat diubah.
- Operator dapat mulai memasukkan hasil.
- Lomba tampil sebagai aktif di halaman publik.

### 7.4 `in_progress`

- Ditetapkan otomatis ketika skor final pertama disimpan.
- Lomba tetap tampil sebagai aktif.
- Klasemen atau bagan diperbarui setiap hasil berubah.

### 7.5 `completed`

- Ditetapkan otomatis ketika seluruh pertandingan yang memerlukan skor sudah selesai.
- Tidak tampil pada daftar lomba aktif di landing page.
- Detail tetap dapat diakses melalui URL agar hasil akhir tidak hilang.

### 7.6 Aturan penguncian

- Penguncian membutuhkan konfirmasi eksplisit dari admin.
- Dialog konfirmasi menampilkan format, jumlah peserta, jumlah pertandingan, dan aturan poin.
- Lomba minimal memiliki dua peserta.
- Hasil shuffle harus tersedia sebelum lomba dapat dikunci.
- Penguncian tidak dapat dibatalkan pada MVP.
- Nama, deskripsi, serta tanggal informasi lomba boleh diperbarui setelah penguncian karena tidak memengaruhi integritas pertandingan.
- Penugasan operator boleh diubah kapan saja oleh admin.

## 8. Alur Utama

### 8.1 Admin membuat lomba

1. Admin login dan membuka menu **Lomba**.
2. Admin memilih **Tambah Lomba**.
3. Admin mengisi nama, deskripsi opsional, tanggal opsional, dan jenis lomba.
4. Jika memilih kompetisi penuh atau setengah kompetisi, admin mengisi poin menang, seri, dan kalah.
5. Sistem memvalidasi data lalu menyimpan lomba sebagai `draft`.

### 8.2 Admin menambahkan peserta

1. Admin membuka detail draft lomba.
2. Admin menambahkan tim satu per satu.
3. Sistem menolak nama tim yang sama dalam lomba yang sama tanpa membedakan huruf besar/kecil.
4. Admin dapat mengubah atau menghapus tim selama lomba belum dikunci.
5. Jika susunan pernah di-shuffle, perubahan peserta membatalkan susunan tersebut.

### 8.3 Admin melakukan shuffle dan mengunci lomba

1. Setelah minimal dua peserta tersedia, admin memilih **Shuffle**.
2. Sistem mengacak peserta dan membuat pratinjau bagan/jadwal lengkap.
3. Admin meninjau hasil.
4. Admin dapat memilih **Shuffle Ulang**; hasil terbaru menggantikan pratinjau sebelumnya.
5. Jika hasil disetujui, admin memilih **Kunci Lomba**.
6. Sistem meminta konfirmasi dan mengunci data struktural lomba.
7. Lomba masuk daftar aktif dan dapat diakses operator yang ditugaskan.

### 8.4 Operator memasukkan skor

1. Operator login dan melihat lomba yang ditugaskan.
2. Operator membuka pertandingan yang sudah memiliki dua peserta.
3. Operator memasukkan skor akhir kedua tim.
4. Pada sistem gugur dengan skor sama, operator memilih pemenang tie-break.
5. Sistem menampilkan konfirmasi sebelum menyimpan hasil.
6. Backend menyimpan hasil dan pengguna yang melakukan perubahan dalam satu transaksi.
7. Sistem menghitung ulang klasemen atau mengisi peserta pada babak berikutnya.
8. Hasil terbaru tersedia pada halaman publik.

### 8.5 Publik melihat lomba

1. Pengunjung membuka landing page.
2. Sistem menampilkan kartu lomba aktif.
3. Pengunjung memilih satu lomba.
4. Untuk sistem gugur, halaman menampilkan bagan per babak.
5. Untuk kompetisi penuh/setengah kompetisi, halaman menampilkan klasemen dan pertandingan per ronde.

## 9. Kebutuhan Fungsional

### 9.1 Autentikasi dan pengguna

| ID         | Kebutuhan                                                                           | Prioritas |
| ---------- | ----------------------------------------------------------------------------------- | :-------: |
| FR-AUTH-01 | Sistem menyediakan login untuk admin dan operator.                                  |   Must    |
| FR-AUTH-02 | Setiap pengguna internal memiliki tepat satu role: `admin` atau `operator`.         |   Must    |
| FR-AUTH-03 | Registrasi publik dinonaktifkan; akun operator dibuat oleh admin.                   |   Must    |
| FR-AUTH-04 | Pengguna yang dinonaktifkan tidak dapat login atau memperbarui skor.                |   Must    |
| FR-AUTH-05 | Admin awal dibuat melalui seeder atau prosedur deployment yang aman.                |   Must    |
| FR-AUTH-06 | Setelah login, admin diarahkan ke dashboard admin dan operator ke daftar penugasan. |  Should   |

### 9.2 Pengelolaan lomba

| ID         | Kebutuhan                                                                                                | Prioritas |
| ---------- | -------------------------------------------------------------------------------------------------------- | :-------: |
| FR-COMP-01 | Admin dapat membuat lomba dengan nama unik, slug publik, deskripsi opsional, dan tanggal opsional.       |   Must    |
| FR-COMP-02 | Admin memilih satu format: sistem gugur, kompetisi penuh, atau setengah kompetisi.                       |   Must    |
| FR-COMP-03 | Sistem menampilkan input poin hanya untuk format berbasis klasemen.                                      |   Must    |
| FR-COMP-04 | Admin dapat mengubah dan menghapus lomba selama masih `draft` atau `drawn`.                              |   Must    |
| FR-COMP-05 | Penghapusan lomba yang sudah dikunci ditolak.                                                            |   Must    |
| FR-COMP-06 | Admin dapat menugaskan beberapa operator ke satu lomba dan satu operator dapat menangani beberapa lomba. |   Must    |
| FR-COMP-07 | Dashboard menampilkan status dan progres jumlah pertandingan selesai.                                    |  Should   |

### 9.3 Peserta

| ID         | Kebutuhan                                                                                      | Prioritas |
| ---------- | ---------------------------------------------------------------------------------------------- | :-------: |
| FR-TEAM-01 | Admin dapat menambah tim dengan nama wajib serta singkatan dan logo opsional.                  |   Must    |
| FR-TEAM-02 | Nama tim harus unik di dalam satu lomba, tetapi boleh digunakan pada lomba lain.               |   Must    |
| FR-TEAM-03 | Admin dapat mengubah urutan tampilan, data, atau menghapus tim sebelum lomba dikunci.          |   Must    |
| FR-TEAM-04 | Sistem menolak perubahan peserta setelah lomba dikunci.                                        |   Must    |
| FR-TEAM-05 | Logo yang diunggah divalidasi sebagai gambar dengan batas ukuran yang ditentukan implementasi. |  Should   |

### 9.4 Shuffle dan susunan pertandingan

| ID         | Kebutuhan                                                                                                              | Prioritas |
| ---------- | ---------------------------------------------------------------------------------------------------------------------- | :-------: |
| FR-DRAW-01 | Shuffle tersedia setelah terdapat minimal dua peserta.                                                                 |   Must    |
| FR-DRAW-02 | Sistem menyimpan hasil shuffle terakhir agar tidak berubah ketika halaman dimuat ulang.                                |   Must    |
| FR-DRAW-03 | Admin dapat melakukan shuffle ulang tanpa batas sebelum penguncian.                                                    |   Must    |
| FR-DRAW-04 | Untuk lebih dari dua peserta, sistem sebisa mungkin tidak mengembalikan susunan identik dengan hasil tepat sebelumnya. |  Should   |
| FR-DRAW-05 | Perubahan peserta menghapus hasil shuffle sebelumnya.                                                                  |   Must    |
| FR-DRAW-06 | Sistem menampilkan seluruh pratinjau pertandingan sebelum admin mengunci lomba.                                        |   Must    |

### 9.5 Pertandingan dan skor

| ID          | Kebutuhan                                                                                                                   | Prioritas |
| ----------- | --------------------------------------------------------------------------------------------------------------------------- | :-------: |
| FR-MATCH-01 | Skor berupa bilangan bulat lebih besar dari atau sama dengan nol.                                                           |   Must    |
| FR-MATCH-02 | Pertandingan hanya dapat diberi skor jika memiliki dua peserta yang sudah diketahui.                                        |   Must    |
| FR-MATCH-03 | Operator hanya dapat mengubah pertandingan dari lomba yang ditugaskan.                                                      |   Must    |
| FR-MATCH-04 | Sistem menyimpan waktu perubahan dan pengguna terakhir yang mengubah hasil.                                                 |   Must    |
| FR-MATCH-05 | Perubahan skor memperbarui kalkulasi dalam transaksi database yang sama.                                                    |   Must    |
| FR-MATCH-06 | UI meminta konfirmasi sebelum membuat atau mengoreksi skor final.                                                           |  Should   |
| FR-MATCH-07 | Koreksi hasil kompetisi langsung menghitung ulang seluruh klasemen dari hasil final.                                        |   Must    |
| FR-MATCH-08 | Koreksi hasil sistem gugur diperbolehkan hanya jika pertandingan babak berikutnya yang terdampak belum memiliki skor final. |   Must    |
| FR-MATCH-09 | Jika hasil babak berikutnya sudah final, koreksi hasil sebelumnya ditolak dengan pesan yang menjelaskan dependensinya.      |   Must    |

### 9.6 Halaman publik

| ID        | Kebutuhan                                                                              | Prioritas |
| --------- | -------------------------------------------------------------------------------------- | :-------: |
| FR-PUB-01 | Landing page menampilkan kartu seluruh lomba aktif.                                    |   Must    |
| FR-PUB-02 | Kartu menampilkan nama, format, jumlah peserta, dan progres pertandingan.              |   Must    |
| FR-PUB-03 | Detail lomba memiliki URL berbasis slug yang dapat dibagikan.                          |   Must    |
| FR-PUB-04 | Detail sistem gugur menampilkan bagan, peserta, skor, dan pemenang tiap pertandingan.  |   Must    |
| FR-PUB-05 | Detail format kompetisi menampilkan aturan poin, klasemen, dan pertandingan per ronde. |   Must    |
| FR-PUB-06 | Pertandingan yang belum memiliki skor ditandai sebagai belum dimainkan.                |   Must    |
| FR-PUB-07 | Detail lomba selesai tetap dapat dibuka melalui URL langsung.                          |   Must    |
| FR-PUB-08 | Empty state ditampilkan jika belum ada lomba aktif.                                    |  Should   |

## 10. Aturan Pembentukan Pertandingan

### 10.1 Sistem gugur

1. Urutan peserta diacak dan ditempatkan pada slot bagan.
2. Ukuran bagan adalah pangkat dua terdekat yang lebih besar dari atau sama dengan jumlah peserta.
3. Jika jumlah peserta bukan pangkat dua, sistem membuat slot **bye**.
4. Tim yang memperoleh bye maju otomatis tanpa input skor.
5. Distribusi bye harus mengikuti susunan hasil shuffle dan tidak boleh menyebabkan sebuah tim bermain melawan dirinya sendiri.
6. Pemenang pertandingan mengisi slot yang telah ditentukan pada babak berikutnya.
7. Satu pertandingan final menentukan pemenang bagan.
8. Untuk `n` peserta, jumlah pertandingan yang membutuhkan skor selalu `n - 1`.

Nama babak yang ditampilkan:

- Final untuk 2 tim tersisa;
- Semifinal untuk 4 tim tersisa;
- Perempat Final untuk 8 tim tersisa;
- Babak 16 Besar untuk 16 tim tersisa;
- selanjutnya menggunakan label `Babak {jumlah tim}`.

Contoh enam peserta:

- ukuran bagan: 8 slot;
- jumlah bye: 2;
- jumlah pertandingan yang membutuhkan skor sampai juara: 5.

### 10.2 Setengah kompetisi

1. Sistem menggunakan algoritma round-robin agar setiap pasangan tim bertemu tepat satu kali.
2. Untuk `n` peserta, jumlah pertandingan adalah `n(n-1)/2`.
3. Jika jumlah peserta genap, jumlah ronde adalah `n-1`.
4. Jika jumlah peserta ganjil, jumlah ronde adalah `n` dan setiap tim mendapat satu ronde bye.
5. Hasil shuffle menentukan urutan awal algoritma, urutan ronde, dan penempatan tim pertama/kedua.

Contoh empat peserta menghasilkan enam pertandingan dalam tiga ronde.

### 10.3 Kompetisi penuh

1. Leg pertama mengikuti aturan setengah kompetisi.
2. Leg kedua mengulang semua pasangan dengan posisi tim pertama dan kedua dibalik.
3. Untuk `n` peserta, jumlah pertandingan adalah `n(n-1)`.
4. Jumlah ronde adalah dua kali jumlah ronde pada setengah kompetisi.
5. Tidak boleh ada pasangan dengan posisi yang sama dua kali.

Contoh empat peserta menghasilkan dua belas pertandingan dalam enam ronde.

## 11. Perhitungan Klasemen

Klasemen hanya tersedia untuk kompetisi penuh dan setengah kompetisi. Klasemen dihitung dari seluruh pertandingan berstatus final, bukan dari angka klasemen yang diinput manual.

### 11.1 Kolom klasemen

| Kolom   | Arti                                                                  |
| ------- | --------------------------------------------------------------------- |
| Main    | Jumlah pertandingan final yang dimainkan.                             |
| Menang  | Jumlah pertandingan dengan skor tim lebih tinggi.                     |
| Seri    | Jumlah pertandingan dengan skor sama.                                 |
| Kalah   | Jumlah pertandingan dengan skor tim lebih rendah.                     |
| Skor+   | Total skor yang dibuat tim.                                           |
| Skor-   | Total skor yang diterima tim.                                         |
| Selisih | `Skor+ - Skor-`.                                                      |
| Poin    | `(Menang × poin menang) + (Seri × poin seri) + (Kalah × poin kalah)`. |

### 11.2 Urutan peringkat MVP

Tim diurutkan berdasarkan:

1. total poin tertinggi;
2. selisih skor tertinggi;
3. jumlah Skor+ tertinggi;
4. jumlah kemenangan tertinggi.

Jika seluruh nilai tersebut sama:

- kedua tim memperoleh posisi yang sama;
- posisi berikutnya mengikuti model peringkat kompetisi, contoh `1, 1, 3`;
- nama tim hanya digunakan untuk urutan tampilan yang stabil dan tidak menjadi tie-breaker olahraga.

Head-to-head dan tie-breaker yang dapat dikonfigurasi berada di luar MVP.

### 11.3 Konsistensi kalkulasi

- Tim tanpa pertandingan tetap muncul dengan seluruh statistik bernilai nol.
- Hanya pertandingan final yang dihitung.
- Setiap perubahan skor memicu hitung ulang dari data pertandingan final untuk mencegah akumulasi ganda.
- Nilai poin negatif tetap dihitung sesuai aturan lomba.
- Backend adalah sumber kebenaran; frontend hanya menampilkan hasil kalkulasi backend.

## 12. Aturan Koreksi Hasil

### 12.1 Kompetisi penuh dan setengah kompetisi

- Operator/admin dapat mengganti skor final.
- Sistem menghitung ulang klasemen setelah koreksi.
- Pengguna dan waktu perubahan terakhir diperbarui.

### 12.2 Sistem gugur

- Jika koreksi tidak mengubah pemenang, skor dapat diperbarui.
- Jika koreksi mengubah pemenang dan pertandingan berikutnya belum final, sistem mengganti peserta terkait pada jalur bagan berikutnya.
- Jika pemenang lama sudah bermain dan pertandingan berikutnya sudah final, koreksi ditolak.
- Penolakan harus menyebut pertandingan lanjutan yang perlu diselesaikan secara administratif pada versi produk mendatang.

## 13. Model Data Konseptual

Model berikut adalah kebutuhan data, bukan kewajiban penamaan tabel atau class secara persis.

### 13.1 User

- ID
- nama
- email unik
- password terenkripsi
- role: `admin` atau `operator`
- status aktif/nonaktif
- waktu verifikasi email
- timestamps

### 13.2 Lomba

- ID
- nama
- slug unik
- deskripsi opsional
- jenis: `knockout`, `full_competition`, `half_competition`
- status: `draft`, `drawn`, `locked`, `in_progress`, `completed`
- poin menang, seri, kalah; nullable untuk sistem gugur
- tanggal mulai/selesai opsional
- waktu dikunci dan admin yang mengunci
- timestamps

### 13.3 Penugasan operator

- lomba
- user dengan role operator
- admin yang menugaskan
- waktu penugasan
- kombinasi lomba dan operator harus unik

### 13.4 Peserta

- ID
- lomba
- nama tim
- singkatan opsional
- path logo opsional
- posisi hasil shuffle
- timestamps

### 13.5 Pertandingan

- ID
- lomba
- ronde/babak
- leg
- nomor urut pertandingan
- peserta pertama dan kedua; dapat kosong untuk slot babak mendatang
- skor peserta pertama dan kedua; kosong sebelum final
- pemenang; wajib pada pertandingan sistem gugur yang final
- metode kemenangan/tie-break opsional
- status: `pending`, `ready`, `completed`, atau `bye`
- referensi pertandingan/bagian slot berikutnya untuk sistem gugur
- pengguna dan waktu pembaruan hasil terakhir
- timestamps

### 13.6 Prinsip relasi data

- Menghapus draft lomba menghapus peserta, penugasan, dan pertandingan pratinjaunya.
- Peserta yang sudah direferensikan lomba terkunci tidak boleh dihapus.
- Skor dan pemenang harus konsisten dengan status pertandingan.
- Foreign key SQLite wajib diaktifkan.
- Operasi penguncian, penyimpanan skor, kalkulasi, dan progres sistem gugur dijalankan secara transaksional.

## 14. Struktur Halaman dan Navigasi

### 14.1 Publik

- `/` — landing page dan kartu lomba aktif.
- `/lomba/{slug}` — detail lomba.
    - Tab **Bagan** untuk sistem gugur.
    - Tab **Klasemen** dan **Pertandingan** untuk format kompetisi.

### 14.2 Admin

- `/admin/dashboard` — ringkasan lomba berdasarkan status.
- `/admin/lomba` — daftar dan filter lomba.
- `/admin/lomba/tambah` — form pembuatan.
- `/admin/lomba/{lomba}` — ringkasan dan progres.
- `/admin/lomba/{lomba}/pengaturan` — metadata, format, poin, operator.
- `/admin/lomba/{lomba}/peserta` — pengelolaan tim.
- `/admin/lomba/{lomba}/undian` — shuffle, pratinjau, dan penguncian.
- `/admin/lomba/{lomba}/pertandingan` — hasil seluruh pertandingan.
- `/admin/operator` — pengelolaan akun operator.

### 14.3 Operator

- `/operator/dashboard` — daftar lomba yang ditugaskan.
- `/operator/lomba/{lomba}` — ringkasan lomba.
- `/operator/lomba/{lomba}/pertandingan` — daftar dan input skor.
- `/operator/lomba/{lomba}/pertandingan/{pertandingan}` — form skor final.

Nama route Laravel harus digunakan sebagai sumber URL. Frontend menggunakan fungsi route/controller yang dihasilkan Wayfinder, bukan string URL yang ditulis manual.

## 15. Kebutuhan UI/UX

### 15.1 Prinsip umum

- Seluruh antarmuka utama menggunakan Bahasa Indonesia.
- Gunakan komponen shadcn-vue yang sudah tersedia atau ditambahkan melalui pola proyek.
- Warna status harus selalu disertai teks atau ikon agar tidak bergantung pada warna saja.
- Aksi berisiko seperti kunci lomba, hapus, dan koreksi skor memerlukan dialog konfirmasi.
- Setiap form menampilkan pesan validasi pada field yang relevan.
- Setiap aksi sukses/gagal memberikan toast atau feedback yang jelas.
- Tombol submit memiliki loading state dan mencegah submit ganda.

### 15.2 Landing page

- Header sederhana dengan identitas aplikasi dan tombol login staf.
- Grid kartu lomba aktif.
- Setiap kartu menampilkan badge format/status, jumlah peserta, dan progres `selesai/total`.
- Kartu dapat diakses dengan keyboard dan menuju detail publik.

### 15.3 Bagan sistem gugur

- Desktop menampilkan kolom per babak dari kiri ke kanan.
- Mobile menggunakan horizontal scroll yang tetap mempertahankan keterbacaan kartu pertandingan.
- Slot yang belum diketahui ditampilkan sebagai `Menunggu pemenang`.
- Bye ditandai jelas dan tidak menampilkan input skor.
- Tim pemenang diberi penekanan visual tanpa menyembunyikan skor lawan.

### 15.4 Klasemen

- Header tabel tetap jelas pada layar kecil.
- Nama kolom statistik memiliki tooltip atau label lengkap.
- Peringkat, nama tim, jumlah main, selisih, dan poin diprioritaskan pada mobile.
- Daftar pertandingan dapat difilter berdasarkan ronde dan status.

### 15.5 Form input skor

- Menampilkan nama dan logo kedua tim.
- Input skor menggunakan angka non-negatif.
- Pilihan pemenang tie-break muncul hanya jika format sistem gugur dan skor sama.
- Menampilkan hasil sebelumnya saat melakukan koreksi.
- Menjelaskan bahwa penyimpanan akan memengaruhi klasemen atau babak berikutnya.

## 16. Kebutuhan Non-Fungsional

### 16.1 Keamanan

- Gunakan autentikasi Laravel Fortify yang sudah tersedia.
- Gunakan policy/gate Laravel untuk otorisasi berbasis role dan penugasan lomba.
- Validasi dan otorisasi dilakukan pada setiap request perubahan data.
- Password disimpan menggunakan hasher Laravel.
- Perlindungan CSRF aktif untuk seluruh form internal.
- File logo divalidasi berdasarkan tipe MIME, ukuran, dan ekstensi yang diizinkan.
- Response Inertia hanya mengirim data yang boleh dilihat pengguna saat ini.

### 16.2 Integritas dan konkurensi

- Penyimpanan skor serta perubahan jalur bagan harus atomik.
- Sistem mencegah dua update bersamaan menimpa hasil tanpa disadari, minimal dengan pengecekan waktu/version data terakhir.
- Kalkulasi klasemen harus deterministik untuk input pertandingan yang sama.
- Penguncian hanya berhasil jika peserta dan hasil shuffle masih sama dengan yang dikonfirmasi admin.

### 16.3 Performa

- Target MVP diuji sampai 64 peserta per lomba.
- Landing page dan detail lomba ditargetkan merespons kurang dari 2 detik pada data target di lingkungan produksi normal.
- Query daftar lomba, peserta, pertandingan, dan operator harus menghindari N+1.
- Data besar seperti pertandingan dapat dipaginasi di halaman internal; bagan/klasemen publik memuat data yang diperlukan untuk visualisasi lengkap.

### 16.4 Aksesibilitas dan responsivitas

- Target minimal WCAG 2.1 AA untuk kontras, fokus, label form, dan navigasi keyboard.
- Mendukung lebar layar mulai 360 px.
- Dialog mengunci fokus dan dapat ditutup melalui keyboard sesuai perilaku komponen shadcn-vue.

### 16.5 Pemeliharaan

- Logika pembentukan jadwal, progres bagan, dan kalkulasi klasemen berada di backend serta memiliki automated test.
- Enum/status tidak ditulis sebagai string bebas di banyak tempat.
- Controller menjaga orchestration tetap tipis; aturan domain tidak ditempatkan di komponen Vue.
- Perubahan schema dikelola melalui migration Laravel.

## 17. Kriteria Penerimaan MVP

### AC-01 — Membuat format kompetisi

**Diberikan** admin login  
**Ketika** admin membuat lomba kompetisi penuh dengan poin `3/1/0`  
**Maka** lomba tersimpan sebagai draft dan aturan poin tampil pada detail.

### AC-02 — Validasi poin

**Diberikan** admin memilih format berbasis klasemen  
**Ketika** salah satu nilai poin kosong atau bukan bilangan bulat  
**Maka** sistem menolak penyimpanan dan menunjukkan field yang salah.

### AC-03 — Poin tidak digunakan pada sistem gugur

**Diberikan** admin memilih sistem gugur  
**Maka** input poin tidak ditampilkan dan nilai poin tidak disimpan sebagai aturan aktif.

### AC-04 — Shuffle dapat diulang

**Diberikan** lomba draft memiliki lebih dari dua peserta  
**Ketika** admin melakukan shuffle lalu shuffle ulang  
**Maka** sistem menyimpan hasil terbaru, menampilkan pratinjau valid, dan sebisa mungkin menghasilkan urutan berbeda.

### AC-05 — Penguncian bersifat final

**Diberikan** lomba sudah memiliki hasil shuffle  
**Ketika** admin mengonfirmasi penguncian  
**Maka** status menjadi `locked` dan request perubahan jenis, poin, peserta, atau shuffle ditolak oleh backend.

### AC-06 — Jumlah pertandingan setengah kompetisi

**Diberikan** empat peserta  
**Ketika** setengah kompetisi di-shuffle  
**Maka** sistem menghasilkan enam pertandingan, setiap pasangan hanya bertemu satu kali, dan terdapat tiga ronde.

### AC-07 — Jumlah pertandingan kompetisi penuh

**Diberikan** empat peserta  
**Ketika** kompetisi penuh di-shuffle  
**Maka** sistem menghasilkan dua belas pertandingan dan setiap pasangan bertemu dua kali dengan posisi dibalik.

### AC-08 — Bye pada sistem gugur

**Diberikan** enam peserta  
**Ketika** sistem gugur di-shuffle  
**Maka** sistem membuat bagan delapan slot, dua bye, dan lima pertandingan yang membutuhkan skor sampai final.

### AC-09 — Kalkulasi klasemen

**Diberikan** aturan poin menang `3`, seri `1`, kalah `0`  
**Ketika** Tim A mengalahkan Tim B `2-1`  
**Maka** Tim A memiliki main 1, menang 1, selisih +1, dan 3 poin; Tim B memiliki main 1, kalah 1, selisih -1, dan 0 poin.

### AC-10 — Koreksi skor kompetisi

**Diberikan** sebuah skor sudah final dan masuk klasemen  
**Ketika** operator yang berwenang mengoreksinya  
**Maka** klasemen dihitung ulang tanpa menggandakan statistik lama.

### AC-11 — Skor seri sistem gugur

**Diberikan** pertandingan sistem gugur  
**Ketika** operator memasukkan skor sama tanpa memilih pemenang tie-break  
**Maka** sistem menolak hasil tersebut.

### AC-12 — Pemenang maju otomatis

**Diberikan** pertandingan sistem gugur memiliki dua peserta  
**Ketika** hasil final yang valid disimpan  
**Maka** pemenang mengisi slot yang benar di babak berikutnya.

### AC-13 — Perlindungan koreksi sistem gugur

**Diberikan** pertandingan babak berikutnya sudah final  
**Ketika** operator mencoba mengubah pemenang pertandingan sebelumnya  
**Maka** sistem menolak koreksi dan menjelaskan dependensi pertandingan.

### AC-14 — Pembatasan operator

**Diberikan** operator tidak ditugaskan ke Lomba X  
**Ketika** operator mengirim request untuk mengubah skor Lomba X  
**Maka** backend mengembalikan respons terlarang dan tidak mengubah data.

### AC-15 — Akses publik

**Diberikan** lomba berstatus `locked` atau `in_progress`  
**Ketika** pengunjung membuka landing page tanpa login  
**Maka** kartu lomba tampil dan detailnya dapat dibuka.

### AC-16 — Lomba selesai

**Diberikan** seluruh pertandingan yang membutuhkan skor sudah final  
**Ketika** hasil terakhir disimpan  
**Maka** status otomatis menjadi `completed`, lomba hilang dari daftar aktif, dan URL detail tetap dapat diakses.

## 18. Strategi Pengujian

### 18.1 Unit test

- Generator sistem gugur untuk jumlah peserta pangkat dua dan non-pangkat dua.
- Generator single round-robin untuk peserta genap dan ganjil.
- Generator double round-robin dan pembalikan posisi leg kedua.
- Kalkulator klasemen untuk menang, seri, kalah, poin negatif, dan tie.
- Penentuan urutan dan posisi bersama pada klasemen.
- Penentuan pemenang serta jalur pertandingan berikutnya.

### 18.2 Feature test

- Hak akses admin, operator ditugaskan, operator tidak ditugaskan, dan guest.
- Validasi pembuatan lomba dan peserta.
- Shuffle, invalidasi shuffle, dan penguncian.
- Penolakan perubahan struktural setelah dikunci.
- Input/koreksi skor dan perubahan status lomba.
- Proteksi koreksi pertandingan sistem gugur.
- Landing page dan detail publik.

### 18.3 Browser test

- Alur admin dari membuat lomba sampai mengunci.
- Alur operator memasukkan skor.
- Tampilan bagan dan klasemen di desktop serta mobile.
- Dialog konfirmasi, error validasi, loading state, dan navigasi keyboard utama.

## 19. Rencana Implementasi Bertahap

Pelaksanaan teknis setiap tahap diatur dalam [Implementation Roadmap](./IMPLEMENTATION-ROADMAP.md). Roadmap dan dokumen fase merupakan turunan PRD ini, tidak menggantikan keputusan produk di dalamnya. Jika ditemukan konflik, implementasi harus berhenti sampai PRD dan dokumen fase diselaraskan.

### Tahap 1 — Fondasi akses dan domain

- Role user dan pengelolaan akun operator.
- Model, migration, factory, enum, policy, dan relasi utama.
- Status lifecycle lomba.

### Tahap 2 — Pengelolaan lomba

- CRUD draft lomba.
- Aturan poin.
- Peserta dan penugasan operator.

### Tahap 3 — Generator pertandingan

- Shuffle dan pratinjau.
- Generator sistem gugur.
- Generator kompetisi penuh/setengah kompetisi.
- Penguncian transaksional.

### Tahap 4 — Operasional hasil

- Halaman operator.
- Input/koreksi skor.
- Kalkulasi klasemen.
- Progres otomatis sistem gugur.

### Tahap 5 — Publik dan hardening

- Landing page.
- Detail bagan, klasemen, dan pertandingan.
- Pengujian otorisasi, performa, responsivitas, serta aksesibilitas.

## 20. Risiko dan Mitigasi

| Risiko                                            | Dampak                                     | Mitigasi MVP                                                                                        |
| ------------------------------------------------- | ------------------------------------------ | --------------------------------------------------------------------------------------------------- |
| Koreksi hasil gugur merusak babak berikutnya      | Bagan tidak konsisten                      | Blokir koreksi jika pertandingan lanjutan sudah final dan gunakan transaksi.                        |
| Dua operator mengubah skor bersamaan              | Update terakhir menimpa tanpa diketahui    | Gunakan pengecekan versi/timestamp dan tampilkan konflik.                                           |
| Shuffle berubah setelah refresh                   | Admin mengunci susunan yang tidak ditinjau | Persist hasil shuffle terakhir di backend.                                                          |
| Klasemen drift akibat update inkremental          | Poin/statistik salah                       | Hitung ulang dari seluruh pertandingan final setiap ada perubahan.                                  |
| Jadwal round-robin memiliki pasangan ganda/hilang | Lomba tidak valid                          | Uji jumlah pertandingan dan keunikan pasangan secara otomatis.                                      |
| SQLite menerima banyak write bersamaan            | Request skor terkunci/lambat               | Transaksi singkat, index yang tepat, dan retry terkontrol; evaluasi DB server jika skala meningkat. |
| Operator mengakses lomba lain lewat URL langsung  | Kebocoran/perubahan data                   | Policy backend berdasarkan role dan penugasan.                                                      |

## 21. Pengembangan Setelah MVP

Prioritas berikut dapat dipertimbangkan setelah MVP stabil:

1. Riwayat audit lengkap untuk setiap perubahan skor.
2. Arsip dan pencarian lomba selesai pada halaman publik.
3. Tie-breaker klasemen yang dapat dikonfigurasi.
4. Reset hasil sistem gugur secara aman dari pertandingan terakhir ke belakang.
5. Penjadwalan tanggal, waktu, tempat, dan lapangan.
6. Impor peserta dan ekspor hasil.
7. Live score dan pembaruan real-time.
8. Format grup dilanjutkan fase gugur.
9. Statistik pemain dan pencetak skor.

## 22. Asumsi yang Perlu Disetujui Product Owner

Dokumen ini dapat langsung menjadi dasar MVP dengan asumsi berikut:

1. **Kompetisi penuh** berarti setiap pasangan bertemu dua kali, sedangkan **setengah kompetisi** berarti satu kali.
2. Poin menang/seri/kalah hanya berlaku pada format klasemen, bukan sistem gugur.
3. Sistem gugur MVP hanya satu pertandingan per pasangan dan single-elimination.
4. Skor yang sama pada sistem gugur diselesaikan dengan pilihan pemenang tie-break oleh operator.
5. Tidak ada proses unlock lomba pada MVP.
6. Lomba aktif otomatis dipublikasikan setelah dikunci; tidak ada status publish terpisah.
7. Registrasi akun publik dinonaktifkan dan akun operator dikelola admin.
8. Peringkat yang tetap imbang setelah empat kriteria memperoleh posisi yang sama.

Jika salah satu asumsi berubah, bagian aturan pertandingan, lifecycle, model data, dan kriteria penerimaan terkait harus diperbarui sebelum implementasi.
