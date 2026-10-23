# SPEC: PHP CRUD Scaffolder (CLI)

> Dokumen acuan untuk membuat tool. Nama package sementara: `create-php-crud` (cek ketersediaan di npm). Dokumen ini dipakai bersama **basecode** (project PHP contoh yang sudah jadi dan diuji manual). Baca bagian 14 sebelum menulis kode.

## 1. Tujuan dan konteks

CLI interaktif (dijalankan lewat `npx`) yang membuat codebase PHP native berisi CRUD, dengan auth opsional, berdasarkan struktur database MySQL yang **sudah ada**. Dipakai untuk ujikom dan tugas kuliah, hanya di localhost, tanpa layanan berbayar, dan tanpa README. Tool berjalan lokal dan tidak mengirim data ke mana pun.

**Prinsip tampilan CLI (wajib beginner-friendly dan rapi):**
- Satu pertanyaan per baris. Penjelasan singkat di bawah pilihan hanya jika perlu.
- Pakai contoh nyata (`admin123`, `localhost`), bukan istilah teknis.
- Nilai default selalu ditampilkan dalam kurung, supaya cukup menekan Enter.
- Peringatan (⚠) hanya untuk hal yang bisa membuat pemula gagal, dan selalu menyebut apa yang harus dilakukan.
- Pesan error berbahasa Indonesia dan menyebut solusinya (contoh: "Koneksi gagal. Pastikan MySQL sudah menyala, lalu cek host dan password.").
- Jargon (FK, PDO, hash) tidak muncul tanpa penjelasan satu kalimat.

## 2. Cakupan

**Termasuk:**
- CRUD per tabel yang dipilih: daftar, tambah, ubah, hapus.
- Search opsional per menu.
- Dropdown otomatis untuk kolom foreign key (FK satu kolom).
- Auth **opsional**: login, logout, halaman terproteksi (sesi PHP), register opsional, daftar role, menu Pengguna opsional.
- Pembatasan akses sederhana per menu berdasarkan role.

**Tidak termasuk:**
- FK komposit (lebih dari satu kolom): beri peringatan, lalu lewati.
- Relasi many-to-many (tabel pivot).
- Tabel tanpa primary key: beri peringatan, lalu lewati.
- Tipe kolom BLOB, JSON, SET: beri peringatan, lalu lewati.
- Styling (hanya CSS minimal untuk jarak antar elemen), framework PHP, ORM, JavaScript frontend.
- Pagination, upload file, hak akses rinci per aksi (pembatasan hanya per menu).
- Pembuatan tabel akun (pengguna wajib sudah punya tabelnya).
- README, `.env`, CSRF token (keputusan sadar karena hanya localhost dan ujikom).

## 3. Stack

- **Generator:** Node.js, dijalankan lewat `npx`.
- **Hasil generate:** PHP native, OOP class sederhana, PDO dengan prepared statement, halaman server-rendered.
- **Versi PHP minimum:** 7.4 (kode juga harus jalan di PHP 8.x).
- **Database:** MySQL/MariaDB (InnoDB).

## 4. Alur CLI

Urutan pertanyaan. Jenis jawaban: **teks** (ketik, Enter memakai default), **password** (ketik, tampilan disembunyikan), **ya/tidak**, **pilih satu** (panah + Enter), **pilih banyak** (panah, spasi mencentang, Enter).

### 4.1 Lokasi project
1. Lokasi project: **teks**, default `.`.
2. Jika folder tujuan sudah ada dan berisi file: **pilih satu**
   - Timpa file hasil generate (file lain di folder tidak dihapus). Wajib konfirmasi **ya/tidak**, default Tidak.
   - Buat project baru di dalam folder tersebut sebagai `<nama-project>-v2`. Nama project diambil dari nama folder lokasi (jika lokasi `.`, pakai nama folder saat ini). Jika sudah ada, naik ke `-v3`, dan seterusnya.
   - Batal.
3. Jika folder kosong atau belum ada: langsung dipakai.

### 4.2 Database
Host (**teks**, default `localhost`), port (**teks**, default `3306`), user (**teks**, default `root`), password (**password**, default kosong, boleh dikosongkan), nama database (**teks**). Koneksi diuji otomatis; jika gagal, tampilkan pesan solusi dan ulangi pertanyaan. Jika berhasil, tampilkan ringkasan (jumlah tabel dan relasi yang ditemukan).

### 4.3 Auth
1. **"Perlu login?"** (**ya/tidak**). Saat ini tampilkan peringatan:
   `⚠ Pastikan sudah memiliki tabel untuk menyimpan akun pengguna.`
   - Tidak: lewati seluruh 4.3. Tidak ada `config/auth.php`, `login.php`, `logout.php`, `register.php`, `auth_check.php`, dan menu Pengguna.
   - Ya: lanjut.
2. **Tabel akun** (**pilih satu** dari daftar tabel), lalu **kolom username**, **kolom password**, dan **kolom role** (masing-masing **pilih satu**). Kolom role berupa teks langsung (misalnya `admin`, `kasir`), bukan `role_id`.
3. **Password disimpan sebagai** (**pilih satu**):
   ```
   ? Password akun disimpan sebagai:
     ❯ Teks biasa
         Terlihat apa adanya di database, contoh: admin123
         Mudah, bisa diisi manual lewat phpMyAdmin. Kurang aman.
       Di-hash
         Berubah jadi kode acak di database, contoh: $2y$10$wH8QzK...
         Lebih aman, tapi akun harus dibuat lewat aplikasi (tidak bisa diisi manual).
   ```
   Default: Teks biasa. Peringatan tambahan:
   - Pilih hash dan panjang kolom password kurang dari 60 karakter: `⚠ Kolom password terlalu pendek untuk hash (disarankan VARCHAR(255)).`
   - Pilih hash dan tabel akun sudah berisi data: `⚠ Akun lama dengan password teks biasa tidak akan bisa login.`
4. **Role apa saja?** (**teks**, dipisah koma, minimal 1, contoh `admin, kasir`). Nama role divalidasi: huruf kecil, angka, dan underscore.
5. **"Perlu halaman Register?"** (**ya/tidak**)
   - Ya: **"Register untuk role apa?"** (**pilih banyak**). Dilewati jika hanya 1 role (otomatis). Tampilkan peringatan: `⚠ Siapa pun bisa membuat akun dengan role yang kamu pilih.`
   - Hanya 1 role terpilih: form register **tanpa** pilihan role (role diisi otomatis). Lebih dari 1: form register berisi dropdown role, hanya role yang dipilih di sini.
6. **"Perlu menu Pengguna (kelola akun)?"** (**ya/tidak**). Jika ya, menu Pengguna ditambahkan sebagai menu CRUD untuk tabel akun (akses per role ditentukan di 4.4). Menu ini **bukan** bagian dari halaman register.
7. **Akun awal** (**pilih satu**), pilihan bergantung jawaban password:
   - Teks biasa:
     ```
     ? Akun awal:
       ❯ Dibuat otomatis oleh script ini
         Manual lewat phpMyAdmin
     ```
   - Di-hash:
     ```
     ? Akun awal:
       ❯ Lewat seed_user.php
           ⚠ Sulit untuk pemula. Harus dijalankan lewat terminal: php database/seed_user.php
         Tidak perlu (sudah punya akun, atau buat lewat halaman Register)
     ```
   - **Otomatis:** tanyakan username (**teks**), password (**password**), role (**pilih satu**, dilewati jika hanya 1 role), dan nilai kolom lain yang `NOT NULL` tanpa default (satu per satu). Lalu `INSERT` langsung ke tabel akun. Jika username sudah ada, minta username lain.
   - **Manual lewat phpMyAdmin:** tidak ada pertanyaan; pesan akhir menampilkan petunjuk tabel, kolom, dan contoh nilai (misalnya `admin1`, `admin123`, `admin`).
   - **seed_user.php:** file dibuat (lihat 7); pengguna menjalankannya sendiri.
   - **Tidak perlu:** tidak ada tindakan. Jika Register aktif, pesan akhir menyarankan membuat akun pertama lewat halaman Register di browser.

### 4.4 Menu
1. **Pilih tabel yang jadi menu** (**pilih banyak**). Tabel akun **tidak** muncul di daftar ini. Menu Pengguna (jika diaktifkan) ikut sebagai pilihan menu.
2. **Akses menu per role** (hanya jika login aktif dan ada lebih dari satu role): untuk **setiap role**, "Menu apa saja yang bisa diakses role `<nama-role>`?" (**pilih banyak**). Jika hanya 1 role, dilewati (semua menu bisa diakses).
   - Menu yang tidak dipilih role mana pun: peringatan "menu ini tidak bisa diakses siapa pun" dan minta konfirmasi.
   - Role tanpa menu sama sekali: peringatan dan minta konfirmasi.
3. **Cek bentrok nama:** jika nama folder menu (dari nama tabel) sama dengan nama yang dilarang (bagian 9), tampilkan peringatan dan minta nama folder lain, misalnya:
   `⚠ Tabel "config" bentrok dengan folder bawaan. Ganti nama folder menu menjadi: (config_data)`

### 4.5 Detail per menu (diulang untuk tiap menu)
- Kolom yang ditampilkan di daftar (**pilih banyak**, default semua tercentang).
- Perlu fitur search? (**ya/tidak**). Jika ya, kolom yang dicari (**pilih banyak**).
- Untuk tiap kolom FK: kolom label di dropdown (**pilih satu**). Tebakan awal otomatis: `nama`, `name`, `title`, `label`, atau kolom teks pertama. Jika beberapa menu merujuk tabel yang sama, pertanyaan ini hanya diajukan **sekali** dan jawabannya dipakai ulang.

### 4.6 Ringkasan dan konfirmasi
Tampilkan ringkasan (lokasi, auth, role, menu, relasi). Konfirmasi generate (**ya/tidak**). Semua file baru ditulis **hanya setelah** konfirmasi ini, jadi `Ctrl+C` di tengah jalan tidak meninggalkan file setengah jadi.

### 4.7 Pesan akhir
- Lokasi project dan cara menjalankan.
- Jika `php` terdeteksi (dicek lewat `php -v`): tampilkan `php -S localhost:8000`.
- Jika `php` tidak terdeteksi: "Letakkan folder di `htdocs` (XAMPP) atau `www` (Laragon), lalu buka `http://localhost/<nama-folder>`."
- Jika memilih `seed_user.php`: tampilkan perintahnya. Jika `php` tidak terdeteksi, tambahkan: "Kalau muncul 'php is not recognized', pakai path lengkap php.exe dari XAMPP atau Laragon, contoh: `<lokasi-xampp>\php\php.exe database\seed_user.php`".
- Jika memilih manual lewat phpMyAdmin: petunjuk tabel, kolom, dan contoh nilai.
- Satu baris pengingat: jangan unggah `db.php` ke GitHub publik (berisi kredensial database).

## 5. Pembacaan struktur database

- Tabel dan kolom: `information_schema.COLUMNS` (tipe, panjang, `IS_NULLABLE`, `COLUMN_DEFAULT`, `EXTRA`, nilai ENUM).
- Primary key dan kolom unik: `information_schema.KEY_COLUMN_USAGE` dan `TABLE_CONSTRAINTS`.
- Relasi:
  ```sql
  SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME,
         REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL;
  ```
- Aturan hapus relasi: `information_schema.REFERENTIAL_CONSTRAINTS` (`DELETE_RULE`).
- Kelompokkan hasil relasi per `CONSTRAINT_NAME`. Jika satu constraint punya lebih dari satu kolom, anggap FK komposit: beri peringatan dan lewati.

## 6. Aturan relasi

- FK satu kolom menjadi `<select>` di form tambah dan ubah. Opsi diambil dari tabel referensi (`SELECT id, <kolom_label> FROM <tabel>`).
- Halaman daftar memakai `LEFT JOIN` agar yang tampil adalah label, bukan angka.
- Satu tabel boleh punya banyak FK; masing-masing diproses sendiri.
- Jika dua FK mengarah ke tabel yang sama, pakai alias unik dari nama kolom FK (contoh: `ref_pengirim_id`, `ref_penerima_id`).
- FK ke diri sendiri (misalnya `parent_id`) menjadi dropdown ke tabel yang sama; boleh kosong jika kolomnya nullable.
- Search ikut mencari di kolom label hasil JOIN jika kolom itu dipilih.

### 6.1 Tipe kolom menjadi input form
Input form mengikuti tipe kolom di database.

| Tipe di DB | Input di form |
|---|---|
| VARCHAR, CHAR | teks (panjang maksimal mengikuti DB) |
| TEXT | textarea |
| INT, BIGINT, SMALLINT | angka |
| TINYINT(1) | checkbox |
| DECIMAL, FLOAT, DOUBLE | angka dengan desimal |
| DATE | tanggal |
| DATETIME, TIMESTAMP | tanggal dan jam |
| TIME | jam |
| ENUM | dropdown dari nilai ENUM |
| Kolom FK | dropdown relasi |
| BLOB, JSON, SET | dilewati dengan peringatan |

Aturan tambahan:
- Kolom `NOT NULL` tanpa default: wajib diisi. Kolom yang boleh kosong disimpan sebagai `NULL` jika dikosongkan.
- Kolom `AUTO_INCREMENT` dan kolom berdefault `CURRENT_TIMESTAMP` (misalnya `created_at`) tidak muncul di form.
- Kolom `UNIQUE`: jika duplikat, tampilkan pesan "data sudah ada", bukan error mentah.
- Kolom password (di menu Pengguna) memakai input password.
- Validasi dilakukan di server, bukan hanya di HTML.

### 6.2 Hapus data yang masih dipakai
- Jika penghapusan gagal karena FK (`PDOException` kode `23000`), tampilkan: "Data tidak bisa dihapus, masih dipakai pada menu `<Nama Menu>`." Nama menu disusun saat generate dari relasi yang terbaca.
- Jika FK memakai `ON DELETE CASCADE`, tampilkan konfirmasi sebelum hapus: "Data terkait di menu `<Nama Menu>` juga akan terhapus."

## 7. Struktur folder hasil generate

Struktur akhir mengikuti basecode:

```
<nama-project>/
├─ db.php                # kredensial database, class Database (PDO)
├─ index.php             # redirect ke login atau dashboard
├─ login.php             # hanya jika login = ya
├─ logout.php            # hanya jika logout = ya
├─ register.php          # hanya jika register = ya
├─ dashboard.php         # daftar menu sesuai role
├─ includes/
│  ├─ header.php
│  ├─ footer.php
│  └─ auth_check.php     # hanya jika login = ya
├─ config/
│  └─ auth.php           # hanya jika login = ya
├─ crud/                 # folder khusus penampung file CRUD per tabel
│  ├─ <nama_tabel>.php   # file single-file CRUD (list, add, edit, delete dalam 1 file)
│  └─ ...
├─ database/
│  └─ seed_user.php      # hanya jika password di-hash dan memilih seed_user.php
└─ assets/
   └─ style.css          # CSS minimal
```

**`config/auth.php` (hanya jika login = ya)** adalah satu-satunya sumber untuk: nama tabel akun, nama kolom username/password/role, mode password (teks biasa atau hash), daftar role, role yang boleh register, dan peta role ke menu yang boleh diakses. Contoh peta: `['admin' => ['pesanan', 'produk', 'users'], 'kasir' => ['pesanan']]`.

**Aturan auth di kode hasil generate:**
- Login: mode teks biasa dibandingkan dengan `hash_equals()`; mode hash memakai `password_verify()`.
- Simpan akun baru (register dan menu Pengguna): mode hash memakai `password_hash($password, PASSWORD_DEFAULT)`; mode teks biasa disimpan apa adanya.
- Di menu Pengguna, kolom password dikosongkan saat mengubah berarti password lama tidak diganti.
- `includes/auth_check.php` membandingkan nama menu halaman dengan role di sesi berdasarkan `config/auth.php`; jika tidak cocok, tampilkan "akses ditolak". Dashboard hanya menampilkan menu sesuai role.
- Satu folder menu dipakai bersama semua role; tidak ada folder terpisah per role.
- Menu Pengguna hanya dibuat jika dipilih, dan hanya bisa diakses role yang memilihnya di 4.4.

## 8. Aturan kode PHP hasil generate

- PHP native, tanpa framework dan tanpa dependency.
- Semua query memakai **PDO prepared statement**. Dilarang menggabungkan input ke string SQL.
- Nama tabel dan kolom dibungkus backtick di SQL.
- Output ke HTML selalu lewat `htmlspecialchars()`.
- Sesi: `session_start()`, regenerasi ID sesi saat login.
- Form tambah, ubah, dan hapus memakai method POST; hapus meminta konfirmasi.
- Penamaan: folder dan file `snake_case`; class `PascalCase` dengan akhiran `Model` (misalnya `mata_kuliah` menjadi `MataKuliahModel`); label menu dari nama tabel (misalnya "Mata Kuliah"). Nama tabel tidak diubah ke bentuk tunggal.
- Kompatibel PHP 7.4. **Hindari:** `match`, named arguments, constructor property promotion, `?->`, union types, `str_contains()`, `str_starts_with()`, `str_ends_with()`, enum, `readonly`. Deklarasikan semua properti class (properti dinamis deprecated di PHP 8.2).
- Komentar singkat di bagian penting agar mudah dipahami dan dijelaskan saat ujian.

## 9. Nama yang dilarang untuk folder menu

Folder menu tidak boleh bernama: `includes`, `assets`, `config`, `database`, `login`, `logout`, `register`, `dashboard`, `index`, `db`. Jika nama tabel sama dengan salah satunya, tampilkan peringatan (lihat 4.4 langkah 3) dan minta nama folder lain. Nama class tidak bentrok karena akhiran `Model` dan class koneksi bernama `Database`.

## 10. Keamanan generator (disederhanakan, hanya localhost)

- Validasi nama tabel, kolom, dan role dengan whitelist `^[a-zA-Z_][a-zA-Z0-9_]*$` sebelum dipakai di template.
- Escape nilai string yang disisipkan ke template PHP, termasuk password database di `db.php` (kutip tunggal dan backslash).
- Normalisasi path tujuan, tolak `../`, jangan ikuti symlink.
- Konfirmasi sebelum menimpa; hanya file hasil generate yang ditimpa.
- Password: input disembunyikan, tidak dijadikan argumen CLI, tidak ditulis ke log atau pesan error.
- Tidak ada telemetri dan tidak ada panggilan jaringan selain ke database yang diisi pengguna.
- Tanpa script `postinstall` atau `preinstall`.
- Pesan error ringkas, tanpa stack trace yang memuat host atau password.

## 11. Struktur kode generator

```
create-php-crud/
├─ package.json          # bin: { "create-php-crud": "dist/cli.js" }
├─ src/
│  ├─ cli.js             # titik masuk, urutan alur
│  ├─ prompts.js         # semua pertanyaan interaktif
│  ├─ db.js              # koneksi dan pembacaan schema
│  ├─ schema.js          # olah hasil schema jadi struktur data (tabel, kolom, relasi)
│  ├─ generator.js       # isi template dan tulis file
│  ├─ validate.js        # validasi nama dan path
│  └─ templates/         # template file PHP dengan placeholder {{NAMA}}
```

- **Template:** placeholder biasa seperti `{{TABLE}}`. Bagian yang berulang (misalnya satu input per kolom) disusun dulu di kode JS lalu dimasukkan ke placeholder seperti `{{FORM_INPUTS}}`. Tidak memakai `ejs`.
- **Dependency yang diizinkan:** `@clack/prompts` (atau `prompts`), `mysql2`, `fs-extra`. Tidak boleh menambah yang lain tanpa alasan tertulis. Tidak ada library hash (hash hanya dilakukan oleh PHP).
- Dijalankan lewat `npx`; package dan dependency diunduh ke cache sementara, tidak meninggalkan `node_modules` atau `package.json` di folder pengguna.

## 12. Contoh input dan output

**Skema contoh:**
```sql
CREATE TABLE pelanggan (id INT PRIMARY KEY AUTO_INCREMENT, nama VARCHAR(100));
CREATE TABLE produk    (id INT PRIMARY KEY AUTO_INCREMENT, nama VARCHAR(100), harga INT);
CREATE TABLE pesanan (
  id INT PRIMARY KEY AUTO_INCREMENT,
  pelanggan_id INT, produk_id INT, jumlah INT,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id),
  FOREIGN KEY (produk_id) REFERENCES produk(id)
);
```

**Pilihan pengguna:** menu `pesanan`; kolom ditampilkan `pelanggan_id`, `produk_id`, `jumlah`; search aktif pada `jumlah`.

**Hasil yang diharapkan:**
- Halaman daftar menampilkan nama pelanggan dan nama produk (hasil JOIN), bukan ID.
- Form tambah punya dua `<select>` (pelanggan, produk) dan satu input angka (jumlah).
- Kolom `id` tidak muncul di form tambah.
- Menghapus pelanggan yang masih dipakai di `pesanan` menampilkan: "Data tidak bisa dihapus, masih dipakai pada menu Pesanan."

## 13. Tahapan pengerjaan dan pengujian

1. **Basecode lebih dulu:** project PHP contoh (login, 3 menu CRUD, relasi dropdown) yang dites manual di XAMPP atau Laragon.
2. Kerangka CLI dan seluruh pertanyaan (tanpa generate).
3. Koneksi DB dan pembacaan schema, termasuk relasi.
4. Ubah basecode menjadi template dengan placeholder.
5. Generate multi-menu, search, dan dropdown relasi.
6. Auth: login, register, role, menu Pengguna, dan pilihan akun awal.
7. Penanganan kasus khusus (FK komposit, tabel tanpa PK, alias FK ganda, bentrok nama, hapus data yang dipakai relasi).
8. Uji akhir.

Selesaikan dan uji satu tahap sebelum lanjut.

**Pengujian:**
- **Uji kesamaan (golden test):** jalankan generator dengan jawaban yang sama persis dengan yang membentuk basecode (database contoh yang sama), lalu bandingkan hasilnya dengan basecode memakai `diff`. Hasil harus sama persis; jika berbeda, template salah.
- **Skenario minimal:** tanpa login; 1 role; 2 role; tanpa FK; dua FK ke tabel yang sama; password teks biasa; password hash; akun awal otomatis; akun awal `seed_user.php`.
- Uji hasil generate di PHP 7.4 dan PHP 8.x terbaru.

## 14. Aturan untuk AI yang menulis kode

- Jadikan basecode sebagai referensi utama; ubah menjadi template dengan placeholder, jangan menulis ulang dari nol dan jangan "memperbaiki" gaya kodenya.
- Jangan menambah dependency di luar daftar pada bagian 11.
- Jangan memakai framework PHP atau library frontend.
- Jangan menjalankan `npm install` dari dalam generator.
- Jangan membuat fitur di luar cakupan pada bagian 2.
- Jangan membuat README atau `.env`.
- Teks CLI mengikuti prinsip beginner-friendly di bagian 1.
- Setiap fungsi baru disertai contoh input dan output singkat agar mudah dicek.
- Jika ada keputusan yang belum tertulis di dokumen ini, tanyakan dulu, jangan menebak.

## 15. Hal yang ditentukan saat basecode jadi

- Apakah folder menu langsung di root project (seperti rancangan di bagian 7) atau di dalam `pages/`. Jika basecode memakai `pages/`, daftar nama terlarang di bagian 9 bisa disederhanakan.
- Susunan akhir folder dan nama file; bagian 7 diperbarui mengikuti basecode.
