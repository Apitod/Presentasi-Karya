<?php
// ============================================================
// FILE: login.php
// FUNGSI: Halaman login modern sesuai referensi UI/UX.
// ============================================================

session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'tl') {
        header("Location: tl/dashboard.php");
    } else {
        header("Location: agen/dashboard.php");
    }
    exit();
}

require 'koneksi.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Credentials cannot be empty.";
    } else {
        $password_hash = md5($password);
        $username_aman = mysqli_real_escape_string($koneksi, $username);

        $query = "SELECT * FROM users WHERE username = '$username_aman' AND password = '$password_hash' LIMIT 1";
        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] == 'tl') {
                header("Location: tl/dashboard.php");
            } else {
                header("Location: agen/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Presentasi Karya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0061f2;
            --primary-bg: #f2f6fc;
            --text-dark: #1a1a1a;
            --text-muted: #69707a;
            --input-bg: #f1f3f5;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/login_bg.png') no-repeat center center;
            background-size: cover;
            opacity: 0.15;
            z-index: -1;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            text-align: center;
            z-index: 1;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            background: var(--primary-color);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 24px;
            box-shadow: 0 8px 16px rgba(0, 97, 242, 0.2);
        }

        .brand-name {
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 32px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            border-top: 4px solid var(--primary-color);
            padding: 40px;
            text-align: left;
        }

        .role-switcher {
            display: flex;
            background: #f1f3f5;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .role-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: 10px;
            transition: all 0.2s;
        }

        .role-btn.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #363d47;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-group-modern {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-modern i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-control-modern {
            width: 100%;
            padding: 14px 14px 14px 48px;
            background: var(--input-bg);
            border: 2px solid transparent;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .form-control-modern:focus {
            background: white;
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 97, 242, 0.1);
        }

        .btn-primary-modern {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0, 97, 242, 0.25);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 97, 242, 0.35);
        }

        .recover-link {
            display: block;
            text-align: right;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            text-transform: uppercase;
            margin-top: -12px;
            margin-bottom: 24px;
        }

        .footer-text {
            margin-top: 40px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .alert-modern {
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="brand-logo">
            <i class="bi bi-book-half"></i>
        </div>
        <h1 class="brand-name">Presentasi Karya</h1>
        <p class="brand-sub">Editorial Management Portal</p>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern">
                    <i class="bi bi-exclamation-circle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <label class="form-label">Access Role</label>
            <div class="role-switcher">
                <button type="button" class="role-btn active" id="btnAdmin">Admin</button>
                <button type="button" class="role-btn" id="btnAgent">Agent</button>
            </div>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group-modern">
                        <i class="bi bi-person"></i>
                        <input type="text" name="username" class="form-control-modern" placeholder="Enter your username" required autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group-modern">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" name="password" class="form-control-modern" placeholder="••••••••" required>
                    </div>
                </div>

                <a href="#" class="recover-link">Recover Credentials?</a>

                <button type="submit" class="btn-primary-modern">Login to System</button>
            </form>
        </div>

        <div class="footer-text">
            Academic Presentation Project<br>
            © <?php echo date('Y'); ?> Scholarly Curator Editorial. All rights reserved.
        </div>
    </div>

    <script>
        const btnAdmin = document.getElementById('btnAdmin');
        const btnAgent = document.getElementById('btnAgent');

        btnAdmin.addEventListener('click', () => {
            btnAdmin.classList.add('active');
            btnAgent.classList.remove('active');
        });

        btnAgent.addEventListener('click', () => {
            btnAgent.classList.add('active');
            btnAdmin.classList.remove('active');
        });
    </script>
</body>
</html>