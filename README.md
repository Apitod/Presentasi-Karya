# Sistem Manajemen Karyawan dan Penjualan

> **Mini-Project PHP Native & MySQL** | Versi 2.0 — Multi-Level Management

---

## 📋 Deskripsi Proyek

Sistem Manajemen Karyawan dan Penjualan adalah aplikasi web sederhana berbasis **PHP Native** (prosedural) dan **MySQL**. Sistem ini dirancang untuk mensimulasikan alur kerja antara **Admin**, **Team Leader (TL)**, dan **Agen** dalam sebuah perusahaan yang menjual 1 jenis produk.

**Versi 2.0** menambahkan fitur:
- 🏅 **Multi-level Role**: Admin → Team Leader (TL) → Agen
- ⭐ **Sistem Reward Poin** untuk TL berdasarkan kinerja agennya
- 📬 **Notifikasi Telegram** otomatis saat agen request stok
- 🎨 **UI Overhaul** ke clean corporate portal (Bootstrap 5)

---

## 🗂️ Struktur Folder & File

```
PresentasiKarya/
│
├── index.php             ← Entry point: redirect otomatis ke login/dashboard
├── login.php             ← Halaman login untuk semua pengguna
├── logout.php            ← Menghapus sesi dan kembali ke login
├── koneksi.php           ← Konfigurasi dan koneksi database MySQL
├── setup_db.php          ← Script otomatis buat database & tabel (jalankan 1x)
│
├── admin/                ← Folder panel Admin & Team Leader
│   ├── cek_sesi.php      ← Guard: izinkan role 'admin' DAN 'tl'
│   ├── navbar.php        ← Sidebar navigasi (desktop + mobile offcanvas)
│   ├── navbar_content.php← Isi menu sidebar (ada logika tampil menu untuk role)
│   ├── dashboard.php     ← Dashboard statistik + chart + leaderboard poin TL
│   ├── kelola_stok.php   ← Tambah stok & approve request stok agen
│   ├── kelola_agen.php   ← Tambah & lihat daftar agen (dengan pilihan TL)
│   ├── kelola_tl.php     ← [BARU] Tambah & lihat daftar Team Leader
│   ├── edit_agen.php     ← Edit data agen
│   ├── transaksi.php     ← Lihat & approve/reject transaksi + logika poin TL
│   └── style.css         ← Clean corporate CSS (Inter font, stat-card, dll)
│
└── agen/                 ← Folder panel Agen
    ├── cek_sesi.php      ← Guard: hanya role 'agen' yang boleh akses
    ├── navbar.php        ← Sidebar navigasi agen
    ├── dashboard.php     ← Dashboard: stok, pendapatan, info TL atasan
    ├── request_stok.php  ← [DIPERBARUI] Ajukan stok + kirim notifikasi Telegram
    ├── penjualan.php     ← Input transaksi penjualan baru
    └── riwayat.php       ← Lihat riwayat semua transaksi
```

---

## 🏢 Hierarki Role

```
Admin
  └── Team Leader (TL)
        └── Agen
```

| Role | Area Akses | Keterangan |
|------|-----------|------------|
| **Admin** | Panel Admin (penuh) | Bisa tambah/hapus semua data, approve transaksi |
| **Team Leader (TL)** | Panel Admin (terbatas) | Hanya lihat. Tidak bisa approve/hapus |
| **Agen** | Panel Agen | Input penjualan, request stok |

---

## 🗄️ Database & Query SQL

### Nama Database: `presentasi_karya`

> Buat database ini dengan menjalankan `setup_db.php` di browser sekali saja,
> atau gunakan query berikut di phpMyAdmin / MySQL CLI.

---

### Tabel 1: `users` — Data pengguna (admin, TL & agen)

**DIPERBARUI di Versi 2.0:**

```sql
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,             -- Hash MD5
    role         ENUM('admin', 'tl', 'agen') NOT NULL, -- BARU: tambah role 'tl'
    tl_id        INT NULL,                          -- BARU: ID Team Leader atasan agen
    poin         INT DEFAULT 0,                     -- BARU: akumulasi poin reward TL
    alamat       TEXT,
    nik          VARCHAR(20),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Penjelasan perubahan kolom:**

| Kolom | Keterangan |
|-------|-----------|
| `role` | Diubah dari `ENUM('admin','agen')` menjadi `ENUM('admin','tl','agen')` untuk mendukung Team Leader |
| `tl_id` | Menyimpan ID User yang menjadi atasan (TL) dari agen. Nilai `NULL` berarti agen tidak punya TL |
| `poin` | Akumulasi poin reward TL. Bertambah +10 setiap kali transaksi agen di bawahnya disetujui admin |

**Query migrasi (untuk database lama v1):**
```sql
-- Ubah kolom role agar mendukung nilai 'tl'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tl', 'agen') NOT NULL;

-- Tambah kolom tl_id (relasi atasan agen)
ALTER TABLE users ADD COLUMN tl_id INT NULL AFTER role;

-- Tambah kolom poin reward untuk TL
ALTER TABLE users ADD COLUMN poin INT DEFAULT 0 AFTER tl_id;
```

> ✅ Query migrasi ini sudah otomatis dijalankan di dalam `setup_db.php`.

---

### Tabel 2: `produk` — Produk tunggal perusahaan

```sql
CREATE TABLE produk (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk  VARCHAR(100) NOT NULL,
    stok         INT DEFAULT 0,
    harga        DECIMAL(10,2) DEFAULT 0
);
```

---

### Tabel 3: `stok_agen` — Stok agen

```sql
CREATE TABLE stok_agen (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    agen_id  INT NOT NULL,
    stok     INT DEFAULT 0,
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### Tabel 4: `request_stok` — Permintaan stok agen ke admin

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

---

### Tabel 5: `transaksi` — Penjualan produk oleh agen

```sql
CREATE TABLE transaksi (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    agen_id          INT NOT NULL,
    nama_pembeli     VARCHAR(100) NOT NULL,
    jumlah           INT NOT NULL,
    total_harga      DECIMAL(10,2) NOT NULL,
    bukti_transaksi  VARCHAR(255) NOT NULL,
    status           ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### Relasi Antar Tabel

```
users (id) ─────┬──── stok_agen   (agen_id → users.id)
                ├──── request_stok (agen_id → users.id)
                ├──── transaksi    (agen_id → users.id)
                └──── users        (tl_id   → users.id)  ← relasi self-join (agen → TL)
```

---

## 🚀 Cara Menjalankan

### Prasyarat
- XAMPP / Laragon / WAMP terinstal (PHP 7.4+ & MySQL)
- Browser modern (Chrome, Firefox, Edge)
- Koneksi internet (untuk CDN Bootstrap & Chart.js)

### Langkah-langkah

1. **Salin folder proyek** ke dalam direktori web server:
   - XAMPP: `C:/xampp/htdocs/PresentasiKarya/`
   - Laragon: `C:/laragon/www/PresentasiKarya/`

2. **Sesuaikan kredensial database** di file `koneksi.php`:
   ```php
   define('DB_USER', 'root');  // Sesuaikan username MySQL kamu
   define('DB_PASS', '');      // Isi password jika ada
   ```

3. **Buat database secara otomatis** dengan membuka browser:
   ```
   http://localhost/PresentasiKarya/setup_db.php
   ```

4. **Akses aplikasi:**
   ```
   http://localhost/PresentasiKarya/
   ```

---

## 🔐 Akun Default

| Role  | Username | Password |
|-------|----------|----------|
| Admin | `admin`  | `admin123` |

> Team Leader dan Agen ditambahkan melalui panel Admin.

---

## 📬 Konfigurasi Notifikasi Telegram

File: `agen/request_stok.php`

1. Buka file tersebut, cari bagian:
   ```php
   $telegram_bot_token = 'ISI_TOKEN_BOT_KAMU_DI_SINI';
   $telegram_chat_id   = 'ISI_CHAT_ID_ADMIN_DI_SINI';
   ```

2. Ganti dengan token bot dan chat ID kamu. Cara mendapatkannya:
   - **Bot Token**: Buka Telegram → cari `@BotFather` → `/newbot`
   - **Chat ID**: Kirim pesan ke bot, lalu buka URL:
     `https://api.telegram.org/bot<TOKEN>/getUpdates`

3. Pastikan `allow_url_fopen = On` di `php.ini` (untuk `file_get_contents`).

---

## 🔄 Alur Program

### Alur Admin

```
Login → Dashboard
   ├── Kelola TL [BARU]
   │     ├── Tambah TL baru (nama, username, password)
   │     ├── Lihat daftar TL + jumlah agen + total poin
   │     └── Hapus TL (agen di bawahnya dibebaskan)
   │
   ├── Kelola Agen
   │     ├── Tambah agen + pilih TL atasan [BARU]
   │     └── Hapus agen
   │
   ├── Kelola Stok
   │     ├── Tambah stok produk utama
   │     └── Approve/Reject request stok agen
   │
   └── Transaksi
         ├── Approve → status = approved
         │     └── [BARU] Cek tl_id agen → jika ada TL: poin TL += 10
         └── Reject  → status = rejected, stok dikembalikan ke agen
```

### Alur Team Leader (TL) [BARU]

```
Login → Panel Admin (mode read-only)
   ├── Dashboard: melihat kinerja agennya sendiri
   ├── Kelola Agen: melihat daftar agen di bawahnya
   ├── Transaksi: melihat transaksi agen di bawahnya
   └── Sidebar: menampilkan total poin reward real-time
```

> **Sistem Reward Poin TL:**
> - Setiap 1 transaksi agen disetujui Admin = **+10 poin** untuk TL
> - Poin diakumulasi di kolom `poin` tabel `users`
> - Leaderboard poin TL ditampilkan di dashboard Admin

### Alur Agen

```
Login → Dashboard (lihat stok, pendapatan, nama TL atasan)
   ├── Request Stok
   │     ├── Agen isi jumlah & catatan → status pending
   │     └── [BARU] Notifikasi Telegram otomatis ke Admin
   │
   ├── Penjualan
   │     ├── Agen isi: nama pembeli, jumlah, upload bukti foto
   │     ├── Stok agen langsung berkurang
   │     └── Status transaksi = pending sampai admin approve
   │
   └── Riwayat
         └── Lihat semua transaksi beserta statusnya
```

---

## 🛡️ Fitur Keamanan

| Fitur | Implementasi |
|-------|-------------|
| **Password Hashing** | MD5 satu arah |
| **Session Guard** | `cek_sesi.php` di setiap folder, cek role secara ketat |
| **SQL Injection Prevention** | `mysqli_real_escape_string()` untuk semua input |
| **XSS Prevention** | `htmlspecialchars()` untuk semua output ke HTML |
| **Role-based Access** | Admin full-access; TL read-only; Agen portal sendiri |

---

## 💻 Teknologi yang Digunakan

| Teknologi | Keterangan |
|-----------|-----------|
| **PHP Native** | Versi 7.4+ prosedural (mysqli) |
| **MySQL** | Database relasional |
| **Bootstrap 5.3** | Framework CSS via CDN |
| **Bootstrap Icons** | Library ikon via CDN |
| **Chart.js 4.4** | Grafik bar performa agen |
| **Telegram Bot API** | Notifikasi via `file_get_contents()` |
| **Google Font: Inter** | Tipografi modern korporat |

---

## 📚 Konsep PHP yang Dipelajari

1. **`session_start()` & `$_SESSION`** — Menyimpan data login antar halaman
2. **`mysqli_connect()`** — Menghubungkan PHP ke MySQL
3. **`mysqli_query()`** — Menjalankan query SQL
4. **`mysqli_fetch_assoc()`** — Mengambil baris hasil query
5. **`$_POST` & `$_GET`** — Menerima data dari form dan URL
6. **`header("Location: ...")`** — Redirect ke halaman lain
7. **`md5()`** — Hash password
8. **`mysqli_real_escape_string()`** — Mencegah SQL Injection
9. **`htmlspecialchars()`** — Mencegah XSS
10. **`file_get_contents()`** — Memanggil URL eksternal (Telegram API)
11. **`urlencode()`** — Encode teks agar aman dikirim via URL
12. **`json_encode()`** — Konversi array PHP ke format JSON untuk Chart.js
13. **SQL JOIN** — Menggabungkan data dari beberapa tabel sekaligus
14. **SQL LEFT JOIN** — Menampilkan data meski relasi tidak ada (nullable)

---

*Dibuat sebagai mini-project mata kuliah Pemrograman Web — Versi 2.0*
