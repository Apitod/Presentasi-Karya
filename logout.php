<?php
// ============================================================
// FILE: logout.php
// FUNGSI: Menghapus sesi dan mengarahkan kembali ke halaman login
// ============================================================

// Mulai sesi agar bisa mengakses dan menghapusnya
session_start();

// Hapus semua data yang tersimpan dalam sesi
session_destroy();

// Arahkan pengguna kembali ke halaman login
header("Location: ../login.php");
exit();
?>