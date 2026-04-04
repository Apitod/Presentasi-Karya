<?php
// ============================================================
// FILE: admin/cek_sesi.php
// FUNGSI: Guard/penjaga halaman - hanya admin yang boleh akses
// File ini di-include di setiap halaman admin
// ============================================================

// Mulai sesi untuk mengakses data login pengguna
session_start();

// Cek apakah user sudah login DAN apakah role-nya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Jika belum login atau bukan admin, arahkan ke halaman login
    header("Location: ../login.php");
    exit(); // Hentikan eksekusi agar kode di bawahnya tidak jalan
}
?>