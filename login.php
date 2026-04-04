<?php
// ============================================================
// FILE: login.php
// FUNGSI: Halaman login untuk semua pengguna (admin & agen)
// ============================================================

// Mulai sesi PHP agar bisa menyimpan data login di $_SESSION
session_start();

// Jika user sudah login, langsung arahkan ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php"); // Ke dashboard admin
    } else {
        header("Location: agen/dashboard.php");  // Ke dashboard agen
    }
    exit(); // Hentikan eksekusi kode setelah redirect
}

// Include file koneksi database
require 'koneksi.php';

// Variabel untuk menampung pesan error
$error = '';

// -----------------------------------------------------------
// Proses login: hanya dijalankan jika form di-submit (method POST)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form dan bersihkan dari spasi berlebih
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validasi: pastikan field tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan password tidak boleh kosong!";
    } else {
        // Hash password yang diinput menggunakan MD5
        // (harus cocok dengan cara kita menyimpan password di database)
        $password_hash = md5($password);

        // Buat query SQL untuk mencari user berdasarkan username dan password
        $username_aman = mysqli_real_escape_string($koneksi, $username);

        $query = "SELECT * FROM users WHERE username = '$username_aman' AND password = '$password_hash' LIMIT 1";
        $result = mysqli_query($koneksi, $query);

        // Cek apakah ada data yang cocok
        if (mysqli_num_rows($result) == 1) {
            // Login berhasil: simpan data user ke dalam sesi
            $user = mysqli_fetch_assoc($result); // Ambil data sebagai array asosiatif

            $_SESSION['user_id'] = $user['id'];          // Simpan ID user
            $_SESSION['nama_lengkap'] = $user['nama_lengkap']; // Simpan nama
            $_SESSION['role'] = $user['role'];         // Simpan role (admin/agen)

            // Arahkan user ke halaman yang sesuai berdasarkan role-nya
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: agen/dashboard.php");
            }
            exit();
        } else {
            // Jika tidak ada yang cocok, tampilkan pesan error
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Karyawan</title>
    <!-- Bootstrap 5 via CDN untuk styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Latar belakang dengan gradasi warna biru-ungu */
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Card login dengan efek kaca (glassmorphism) */
        .login-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        /* Teks berwarna putih di atas latar gelap */
        .login-card h2,
        .login-card label {
            color: #e0e0e0;
        }

        /* Styling input form */
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 8px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #4e9af1;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(78, 154, 241, 0.25);
        }

        /* Placeholder teks menjadi abu-abu terang */
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        /* Tombol login */
        .btn-login {
            background: linear-gradient(90deg, #4e9af1, #3a7bd5);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: linear-gradient(90deg, #3a7bd5, #2c5fb5);
            transform: translateY(-2px);
            transition: all 0.2s;
        }

        .logo-icon {
            font-size: 3rem;
            color: #4e9af1;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <!-- Header / Judul halaman login -->
        <div class="text-center mb-4">
            <i class="bi bi-building-check logo-icon"></i>
            <h2 class="fw-bold mt-2">Sistem Manajemen</h2>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">Karyawan & Penjualan</p>
        </div>

        <!-- Tampilkan pesan error jika ada -->
        <?php if ($error): ?>
            <div class="alert alert-danger py-2" style="font-size:0.875rem;">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Form login: action dikosongkan agar submit ke halaman ini sendiri -->
        <form method="POST" action="">
            <!-- Input Username -->
            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold">
                    <i class="bi bi-person me-1"></i> Username
                </label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required>
            </div>

            <!-- Input Password -->
            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <input type="password" class="form-control" id="password" name="password"
                    placeholder="Masukkan password" required>
            </div>

            <!-- Tombol Submit -->
            <div class="d-grid">
                <button type="submit" class="btn btn-login btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
                </button>
            </div>
        </form>

    </div>

    <!-- Bootstrap JS (diperlukan untuk komponen interaktif) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>