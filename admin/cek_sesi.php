<?php
// ============================================================
// FILE: admin/cek_sesi.php
// FUNGSI: Guard/penjaga halaman - hanya admin & TL yang boleh akses
//
// PEMBARUAN v2: Sekarang menerima dua role: 'admin' dan 'tl'.
// Team Leader (TL) diizinkan masuk ke area admin, namun
// akses mereka dibatasi di masing-masing halaman sesuai kebutuhan.
// ============================================================

// Mulai sesi untuk mengakses data login pengguna
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login sama sekali, arahkan ke halaman login
    header("Location: ../login.php");
    exit();
}

// Cek apakah role-nya adalah 'admin'
// Jika bukan, tolak masuk
if ($_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'tl') {
        header("Location: ../tl/dashboard.php");
    } else {
        header("Location: ../agen/dashboard.php");
    }
    exit();
}
?>