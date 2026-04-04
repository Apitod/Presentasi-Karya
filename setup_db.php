<?php
// ============================================================
// FILE: setup_db.php
// FUNGSI: Membuat database dan tabel secara otomatis
// Jalankan file ini SEKALI saja di browser untuk setup awal
// Contoh: http://localhost/PresentasiKarya/setup_db.php
// ============================================================

// Koneksi ke MySQL tanpa memilih database dulu
$conn = mysqli_connect('localhost', 'belajarphp', '1379');

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// -----------------------------------------------------------
// LANGKAH 1: Buat database jika belum ada
// -----------------------------------------------------------
$sql_create_db = "CREATE DATABASE IF NOT EXISTS presentasi_karya CHARACTER SET utf8 COLLATE utf8_general_ci";
mysqli_query($conn, $sql_create_db);

// Pilih database yang baru dibuat
mysqli_select_db($conn, 'presentasi_karya');
mysqli_set_charset($conn, "utf8");

// -----------------------------------------------------------
// LANGKAH 2: Buat tabel 'users' (untuk admin dan agen)
// -----------------------------------------------------------
// Tabel ini menyimpan data akun pengguna sistem
$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID unik setiap user
    nama_lengkap VARCHAR(100) NOT NULL,      -- Nama lengkap user
    username VARCHAR(50) NOT NULL UNIQUE,    -- Username untuk login (harus unik)
    password VARCHAR(255) NOT NULL,          -- Password (akan disimpan sebagai hash MD5)
    role ENUM('admin', 'agen') NOT NULL,     -- Tipe akun: admin atau agen
    alamat TEXT,                             -- Alamat (khusus agen)
    nik VARCHAR(20),                         -- NIK KTP (khusus agen)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Waktu akun dibuat
)";
mysqli_query($conn, $sql_users);

// -----------------------------------------------------------
// LANGKAH 3: Buat tabel 'produk' (hanya 1 produk utama)
// -----------------------------------------------------------
// Tabel ini menyimpan data produk tunggal beserta stok globalnya
$sql_produk = "
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID produk
    nama_produk VARCHAR(100) NOT NULL,       -- Nama produk
    stok INT DEFAULT 0,                      -- Jumlah stok yang tersedia
    harga DECIMAL(10,2) DEFAULT 0            -- Harga satuan produk
)";
mysqli_query($conn, $sql_produk);

// -----------------------------------------------------------
// LANGKAH 4: Buat tabel 'stok_agen' (stok milik setiap agen)
// -----------------------------------------------------------
// Setiap agen punya stok sendiri yang diterima dari admin
$sql_stok_agen = "
CREATE TABLE IF NOT EXISTS stok_agen (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID record
    agen_id INT NOT NULL,                    -- ID agen pemilik stok (relasi ke tabel users)
    stok INT DEFAULT 0,                      -- Jumlah stok yang dimiliki agen
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_stok_agen);

// -----------------------------------------------------------
// LANGKAH 5: Buat tabel 'request_stok' (permintaan stok dari agen)
// -----------------------------------------------------------
// Agen mengajukan request, admin yang menyetujui
$sql_request = "
CREATE TABLE IF NOT EXISTS request_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID request
    agen_id INT NOT NULL,                    -- ID agen yang mengajukan request
    jumlah INT NOT NULL,                     -- Jumlah stok yang diminta
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', -- Status request
    catatan TEXT,                            -- Catatan tambahan dari agen
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Waktu request dibuat
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_request);

// -----------------------------------------------------------
// LANGKAH 6: Buat tabel 'transaksi' (penjualan oleh agen)
// -----------------------------------------------------------
// Menyimpan setiap transaksi penjualan yang dilakukan agen
$sql_transaksi = "
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID transaksi
    agen_id INT NOT NULL,                    -- ID agen yang melakukan penjualan
    nama_pembeli VARCHAR(100) NOT NULL,      -- Nama pelanggan/pembeli
    jumlah INT NOT NULL,                     -- Jumlah produk yang terjual
    total_harga DECIMAL(10,2) NOT NULL,      -- Total harga penjualan
    bukti_transaksi TEXT NOT NULL,           -- Bukti transaksi berupa teks (nomor referensi, dll)
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', -- Status persetujuan admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Waktu transaksi dibuat
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_transaksi);

// -----------------------------------------------------------
// LANGKAH 7: Masukkan data awal (seeding)
// -----------------------------------------------------------

// Buat akun admin default jika belum ada
$cek_admin = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
if (mysqli_num_rows($cek_admin) == 0) {
    // Password 'admin123' diubah ke hash MD5 sebelum disimpan
    $password_hash = md5('admin123');
    mysqli_query($conn, "INSERT INTO users (nama_lengkap, username, password, role) 
                         VALUES ('Administrator', 'admin', '$password_hash', 'admin')");
}

// Masukkan data produk default jika tabel masih kosong
$cek_produk = mysqli_query($conn, "SELECT id FROM produk");
if (mysqli_num_rows($cek_produk) == 0) {
    mysqli_query($conn, "INSERT INTO produk (nama_produk, stok, harga) 
                         VALUES ('Produk Unggulan', 100, 205000)");
}

echo "
<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Setup Database</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light d-flex align-items-center justify-content-center' style='min-height:100vh'>
    <div class='card shadow-sm p-5 text-center' style='max-width:500px;width:100%'>
        <div class='mb-3'>
            <span style='font-size:4rem'>✅</span>
        </div>
        <h3 class='fw-bold text-success'>Database Berhasil Dibuat!</h3>
        <p class='text-muted mt-2'>Semua tabel telah berhasil dibuat di database <strong>db_karyawan</strong>.</p>
        <hr>
        <p class='small'>Akun default Admin:<br>
            <strong>Username:</strong> admin<br>
            <strong>Password:</strong> admin123
        </p>
        <a href='login.php' class='btn btn-primary mt-3'>Pergi ke Halaman Login &rarr;</a>
    </div>
</body>
</html>
";

// Tutup koneksi
mysqli_close($conn);
?>