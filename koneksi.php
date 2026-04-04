<?php
// ============================================================
// FILE: koneksi.php
// FUNGSI: Menghubungkan aplikasi ke database MySQL
// Semua file PHP lain akan me-include file ini
// ============================================================

// Definisikan konstanta koneksi database
define('DB_HOST', 'localhost');   // Alamat server database
define('DB_USER', 'belajarphp');        // Username database (ganti sesuai milik kamu)
define('DB_PASS', '1379');            // Password database (ganti sesuai milik kamu)
define('DB_NAME', 'presentasi_karya'); // Nama database yang akan digunakan

// Buat koneksi menggunakan mysqli (MySQL Improved)
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi berhasil atau tidak
if (!$koneksi) {
    // Jika gagal konek, hentikan program dan tampilkan pesan error
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set character set ke UTF-8 agar bisa menampilkan karakter Indonesia
mysqli_set_charset($koneksi, "utf8");
?>