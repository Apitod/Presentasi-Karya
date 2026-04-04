<?php
// ============================================================
// FILE: agen/cek_sesi.php
// FUNGSI: Guard/penjaga halaman - hanya agen yang boleh akses
// ============================================================

session_start();

// Cek apakah user sudah login DAN role-nya adalah 'agen'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agen') {
    // Jika bukan agen, arahkan ke halaman login
    header("Location: ../login.php");
    exit();
}
?>