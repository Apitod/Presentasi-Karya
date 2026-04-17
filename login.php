<?php
// ============================================================
// FILE: login.php
// FUNGSI: Halaman login yang bersih dan fungsional.
// ============================================================
session_start();

// Redirect otomatis jika user sudah memiliki session aktif
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') { header("Location: admin/dashboard.php"); }
    elseif ($_SESSION['role'] == 'tl') { header("Location: tl/dashboard.php"); }
    else { header("Location: agen/dashboard.php"); }
    exit();
}

require 'koneksi.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = md5(trim($_POST['password']));

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') { header("Location: admin/dashboard.php"); }
        elseif ($user['role'] == 'tl') { header("Location: tl/dashboard.php"); }
        else { header("Location: agen/dashboard.php"); }
        exit();
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { max-width: 400px; width: 100%; padding: 2.5rem; background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .btn-primary { background: #0d6efd; border: none; padding: 12px; font-weight: 600; }
        .form-control { padding: 12px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h1 class="h3 fw-bold">Login</h1>
            <p class="text-muted small">Masuk ke Sistem Manajemen Karyawan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>
        <div class="text-center mt-4 text-muted small">
            &copy; <?php echo date('Y'); ?> Sistem Internal
        </div>
    </div>
</body>
</html>