<?php
session_start();

// Cek apakah user sudah login DAN role-nya adalah 'agen'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agen') {
    header("Location: ../login.php");
    exit();
}
?>