<?php
define('DB_HOST', 'localhost');   
define('DB_USER', 'belajarphp');        
define('DB_PASS', '1379');            
define('DB_NAME', 'presentasi_karya'); 

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8");
?>