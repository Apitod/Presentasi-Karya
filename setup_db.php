<?php
$conn = mysqli_connect('localhost', 'belajarphp', '1379');

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}


$sql_create_db = "CREATE DATABASE IF NOT EXISTS presentasi_karya CHARACTER SET utf8 COLLATE utf8_general_ci";
mysqli_query($conn, $sql_create_db);

mysqli_select_db($conn, 'presentasi_karya');
mysqli_set_charset($conn, "utf8");


$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('admin', 'tl', 'agen') NOT NULL, -- BARU: tambah role 'tl' untuk Team Leader
    tl_id        INT NULL,                             -- BARU: ID Team Leader yang menaungi agen (NULL jika tidak ada)
    poin         INT DEFAULT 0,                        -- BARU: poin reward yang dimiliki oleh TL
    alamat       TEXT,
    nik          VARCHAR(20),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql_users);


mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tl', 'agen') NOT NULL");

$cek_tl_id = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'presentasi_karya'
       AND TABLE_NAME   = 'users'
       AND COLUMN_NAME  = 'tl_id'"
));
if (!$cek_tl_id) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN tl_id INT NULL AFTER role");
}

$cek_poin = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'presentasi_karya'
       AND TABLE_NAME   = 'users'
       AND COLUMN_NAME  = 'poin'"
));
if (!$cek_poin) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN poin INT DEFAULT 0 AFTER tl_id");
}


// buat tabel produk
$sql_produk = "
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID produk
    nama_produk VARCHAR(100) NOT NULL,       -- Nama produk
    stok INT DEFAULT 0,                      -- Jumlah stok yang tersedia
    harga DECIMAL(10,2) DEFAULT 0            -- Harga satuan produk
)";
mysqli_query($conn, $sql_produk);


// buat tabel stok_agen
$sql_stok_agen = "
CREATE TABLE IF NOT EXISTS stok_agen (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID record
    agen_id INT NOT NULL,                    -- ID agen pemilik stok (relasi ke tabel users)
    stok INT DEFAULT 0,                      -- Jumlah stok yang dimiliki agen
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_stok_agen);


// buat tabel request_stok
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


// buat tabel transaksi
$sql_transaksi = "
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- ID transaksi
    agen_id INT NOT NULL,                    -- ID agen yang melakukan penjualan
    nama_pembeli VARCHAR(100) NOT NULL,      -- Nama pelanggan/pembeli
    jumlah INT NOT NULL,                     -- Jumlah produk yang terjual
    total_harga DECIMAL(10,2) NOT NULL,      -- Total harga penjualan
    bukti_transaksi VARCHAR(255) NOT NULL,   -- Nama file gambar bukti transaksi (disimpan di folder uploads/)
    status ENUM('pending_tl', 'pending_admin', 'approved', 'rejected') DEFAULT 'pending_tl', -- Status persetujuan bertingkat
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Waktu transaksi dibuat
    FOREIGN KEY (agen_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_transaksi);


// migrasi tabel transaksi
mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN bukti_transaksi VARCHAR(255) NOT NULL");
// Catatan: Query ini aman dijalankan berulang kali (tidak merusak data)



// Migrasi untuk status transaksi (menambahkan pending_tl dan pending_admin)
mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN status ENUM('pending_tl', 'pending_admin', 'approved', 'rejected') DEFAULT 'pending_tl'");


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

// tampilan sukses
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

// tutup koneksi
mysqli_close($conn);
?>