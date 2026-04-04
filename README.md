# Sistem Manajemen Karyawan dan Penjualan

> **Mini-Project PHP Native & MySQL** | Dibuat untuk keperluan presentasi akademik

---

## 📋 Deskripsi Proyek

Sistem Manajemen Karyawan dan Penjualan adalah aplikasi web sederhana berbasis **PHP Native** (prosedural) dan **MySQL**. Sistem ini dirancang untuk mensimulasikan alur kerja antara **Admin** dan **Agen** dalam sebuah perusahaan yang menjual 1 jenis produk.

---

## 🗂️ Struktur Folder & File

```
PresentasiKarya/
│
├── index.php           ← Entry point: redirect otomatis ke login/dashboard
├── login.php           ← Halaman login untuk semua pengguna
├── logout.php          ← Menghapus sesi dan kembali ke login
├── koneksi.php         ← Konfigurasi dan koneksi database MySQL
├── setup_db.php        ← Script otomatis buat database & tabel (jalankan 1x)
│
├── admin/              ← Folder khusus halaman Admin
│   ├── cek_sesi.php    ← Guard: cek apakah yang akses adalah admin
│   ├── navbar.php      ← Komponen sidebar navigasi admin
│   ├── dashboard.php   ← Dashboard statistik kinerja
│   ├── kelola_stok.php ← Tambah stok & approve request stok agen
│   ├── kelola_agen.php ← Tambah & lihat daftar agen
│   └── transaksi.php   ← Lihat & approve/reject transaksi penjualan
│
└── agen/               ← Folder khusus halaman Agen
    ├── cek_sesi.php    ← Guard: cek apakah yang akses adalah agen
    ├── navbar.php      ← Komponen sidebar navigasi agen
    ├── dashboard.php   ← Dashboard ringkasan stok & penjualan
    ├── request_stok.php← Ajukan permintaan stok ke admin
    ├── penjualan.php   ← Input transaksi penjualan baru
    └── riwayat.php     ← Lihat riwayat semua transaksi
```

---

## 🗄️ Database & Query SQL

### Nama Database: `presentasi_karya`

> Buat database ini dengan menjalankan `setup_db.php` di browser sekali saja,
> atau gunakan query berikut di phpMyAdmin / MySQL CLI.

---

### Tabel 1: `users` — Data pengguna (admin & agen)

```sql
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,          -- Disimpan dalam hash MD5
    role         ENUM('admin', 'agen') NOT NULL, -- Tipe akun
    alamat       TEXT,                           -- Khusus agen
    nik          VARCHAR(20),                    -- NIK KTP, khusus agen
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Penjelasan:** Tabel ini menyimpan seluruh akun pengguna. Kolom `role` membedakan antara admin dan agen. Kolom `username` bersifat `UNIQUE` sehingga tidak boleh ada dua akun dengan username yang sama.

---

### Tabel 2: `produk` — Produk tunggal perusahaan

```sql
CREATE TABLE produk (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk  VARCHAR(100) NOT NULL,
    stok         INT DEFAULT 0,            -- Stok global di tangan admin
    harga        DECIMAL(10,2) DEFAULT 0   -- Harga satuan
);
```

**Penjelasan:** Karena perusahaan hanya punya 1 jenis produk, tabel ini hanya berisi 1 baris data. Stok di sini adalah stok "induk" yang dikelola admin sebelum didistribusikan ke agen.

---

### Tabel 3: `stok_agen` — Stok yang dimiliki setiap agen

```sql
CREATE TABLE stok_agen (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    agen_id  INT NOT NULL,
    stok     INT DEFAULT 0,
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Penjelasan:** Setiap agen memiliki stok sendiri. Stok ini berkurang saat agen melakukan penjualan dan bertambah saat admin menyetujui request stok. `ON DELETE CASCADE` berarti jika agen dihapus, data stoknya ikut terhapus otomatis.

---

### Tabel 4: `request_stok` — Permintaan stok dari agen ke admin

```sql
CREATE TABLE request_stok (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    agen_id    INT NOT NULL,
    jumlah     INT NOT NULL,
    status     ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    catatan    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Penjelasan:** Agen mengajukan permintaan tambahan stok melalui tabel ini. Admin akan mengubah status dari `pending` menjadi `approved` atau `rejected`. Saat disetujui, stok admin dikurangi dan stok agen ditambah.

---

### Tabel 5: `transaksi` — Penjualan produk oleh agen

```sql
CREATE TABLE transaksi (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    agen_id          INT NOT NULL,
    nama_pembeli     VARCHAR(100) NOT NULL,
    jumlah           INT NOT NULL,
    total_harga      DECIMAL(10,2) NOT NULL,
    bukti_transaksi  TEXT NOT NULL,  -- Berupa teks: nomor referensi, kode transfer, dll
    status           ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Penjelasan:** Setiap transaksi penjualan yang dilakukan agen disimpan di sini. Status awal selalu `pending`. Stok agen langsung dikurangi saat transaksi dibuat. Jika admin menolak, stok dikembalikan ke agen.

---

### Relasi Antar Tabel (ERD Sederhana)

```
users (id) ─────┬──── stok_agen (agen_id)
                ├──── request_stok (agen_id)
                └──── transaksi (agen_id)

produk (id) ──── (diakses langsung, tidak ada relasi foreign key eksplisit)
```

---

## 🚀 Cara Menjalankan

### Prasyarat
- XAMPP / Laragon / WAMP terinstal (PHP 7.4+ & MySQL)
- Browser modern (Chrome, Firefox, Edge)

### Langkah-langkah

1. **Salin folder proyek** ke dalam direktori web server:
   - XAMPP: `C:/xampp/htdocs/PresentasiKarya/`
   - Laragon: `C:/laragon/www/PresentasiKarya/`

2. **Sesuaikan kredensial database** di file `koneksi.php`:
   ```php
   define('DB_USER', 'root');  // Ganti jika berbeda
   define('DB_PASS', '');      // Isi password MySQL jika ada
   ```

3. **Buat database secara otomatis** dengan membuka browser:
   ```
   http://localhost/PresentasiKarya/setup_db.php
   ```

4. **Akses aplikasi** melalui:
   ```
   http://localhost/PresentasiKarya/
   ```

---

## 🔐 Akun Default

| Role  | Username | Password |
|-------|----------|----------|
| Admin | `admin`  | `admin123` |

> Agen dapat ditambahkan melalui panel Admin → menu **Kelola Agen**.

---

## 🔄 Alur Program

### Alur Admin

```
Login → Dashboard
   ├── Kelola Stok
   │     ├── Tambah stok produk utama (langsung update tabel produk)
   │     └── Approve/Reject request stok agen
   │           ├── Approve → stok produk berkurang, stok agen bertambah
   │           └── Reject  → status diubah, stok tidak berubah
   │
   ├── Kelola Agen
   │     ├── Tambah agen baru (input: nama, NIK, alamat, username, password)
   │     └── Hapus agen (data terkait ikut terhapus via CASCADE)
   │
   └── Transaksi
         ├── Approve → status transaksi = approved
         └── Reject  → status = rejected, stok dikembalikan ke agen
```

### Alur Agen

```
Login → Dashboard (lihat stok & statistik penjualan)
   ├── Request Stok
   │     └── Agen ajukan jumlah → status pending → admin approve
   │
   ├── Penjualan
   │     ├── Agen isi: nama pembeli, jumlah, bukti transaksi (teks)
   │     ├── Stok agen langsung berkurang
   │     └── Status transaksi = pending sampai admin approve
   │
   └── Riwayat
         └── Lihat semua transaksi beserta statusnya
```

---

## 🛡️ Fitur Keamanan (Sederhana)

| Fitur | Implementasi |
|-------|-------------|
| **Password Hashing** | MD5 satu arah (cukup untuk belajar) |
| **Session Guard** | `cek_sesi.php` di setiap folder memastikan hanya role yang tepat yang bisa akses |
| **SQL Injection Prevention** | `mysqli_real_escape_string()` untuk semua input yang dimasukkan ke query |
| **XSS Prevention** | `htmlspecialchars()` untuk semua output data ke HTML |

---

## 💻 Teknologi yang Digunakan

| Teknologi | Keterangan |
|-----------|-----------|
| **PHP Native** | Versi 7.4+ dengan pendekatan prosedural (mysqli) |
| **MySQL** | Database relasional |
| **Bootstrap 5** | Framework CSS via CDN untuk UI responsif |
| **Bootstrap Icons** | Library ikon via CDN |
| **JavaScript (Vanilla)** | Preview total harga real-time di halaman penjualan agen |

---

## 📚 Konsep PHP yang Dipelajari

1. **`session_start()` & `$_SESSION`** — Menyimpan data login pengguna antar halaman
2. **`mysqli_connect()`** — Menghubungkan PHP ke database MySQL
3. **`mysqli_query()`** — Menjalankan query SQL dari PHP
4. **`mysqli_fetch_assoc()`** — Mengambil baris hasil query sebagai array asosiatif
5. **`mysqli_num_rows()`** — Menghitung jumlah baris hasil query
6. **`$_POST` & `$_GET`** — Menerima data dari form (POST) dan URL (GET)
7. **`header("Location: ...")`** — Redirect pengguna ke halaman lain
8. **`md5()`** — Hash password sebelum disimpan ke database
9. **`mysqli_real_escape_string()`** — Mencegah SQL Injection
10. **`htmlspecialchars()`** — Mencegah serangan XSS saat menampilkan data

---

*Dibuat sebagai mini-project mata kuliah Pemrograman Web*
