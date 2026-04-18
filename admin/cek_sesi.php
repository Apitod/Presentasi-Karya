<?php

session_start();

// untk cek apakah login mi si admin klo belum ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ini utk cek credential
if ($_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'tl') {
        header("Location: ../tl/dashboard.php");
    } else {
        header("Location: ../agen/dashboard.php");
    }
    exit();
}
?>