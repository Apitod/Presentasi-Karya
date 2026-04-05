<?php
// ============================================================
// FILE: agen/profil.php
// FUNGSI: Agen dapat melihat dan mengedit profil sendiri
//         (nama, alamat, username, password)
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];
$pesan   = '';

// Ambil data agen yang sedang login
$agen = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $agen_id"
));

// -----------------------------------------------------------
// PROSES: Simpan perubahan profil
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama          = trim($_POST['nama_lengkap']);
    $alamat        = trim($_POST['alamat']);
    $username      = trim($_POST['username']);
    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);

    if (empty($nama) || empty($username)) {
        $pesan = ['type' => 'danger', 'text' => 'Nama dan username wajib diisi!'];
    } else {
        // Cek duplikasi username dengan user lain
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT id FROM users WHERE username = '$username_aman' AND id != $agen_id"
        ));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '$username' sudah digunakan!"];
        } else {
            $nama_aman   = mysqli_real_escape_string($koneksi, $nama);
            $alamat_aman = mysqli_real_escape_string($koneksi, $alamat);

            // Jika ingin ganti password, verifikasi password lama dulu
            if (!empty($password_baru)) {
                if (empty($password_lama)) {
                    $pesan = ['type' => 'danger', 'text' => 'Masukkan password lama untuk konfirmasi!'];
                } elseif (md5($password_lama) !== $agen['password']) {
                    $pesan = ['type' => 'danger', 'text' => 'Password lama tidak sesuai!'];
                } else {
                    // Password lama benar, update dengan password baru
                    $pass_hash = md5($password_baru);
                    $query = "UPDATE users SET 
                                nama_lengkap = '$nama_aman',
                                alamat       = '$alamat_aman',
                                username     = '$username_aman',
                                password     = '$pass_hash'
                              WHERE id = $agen_id";
                    mysqli_query($koneksi, $query);
                    // Update nama di sesi agar navbar langsung berubah
                    $_SESSION['nama_lengkap'] = $nama;
                    $pesan = ['type' => 'success', 'text' => 'Profil dan password berhasil diperbarui!'];
                }
            } else {
                // Tidak ganti password, hanya update data profil
                $query = "UPDATE users SET 
                            nama_lengkap = '$nama_aman',
                            alamat       = '$alamat_aman',
                            username     = '$username_aman'
                          WHERE id = $agen_id";
                mysqli_query($koneksi, $query);
                $_SESSION['nama_lengkap'] = $nama;
                $pesan = ['type' => 'success', 'text' => 'Profil berhasil diperbarui!'];
            }

            // Refresh data setelah update
            $agen = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $agen_id"));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-4">
                <h3 class="page-title mb-1">
                    <i class="bi bi-person-circle me-2" style="color:#4fd1c5;"></i> Profil Saya
                </h3>
                <p class="text-muted small mb-0">Kelola informasi akun Anda.</p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background:#0f3460; color:#fff;">
                            <i class="bi bi-pencil-square me-2"></i> Edit Profil
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">

                                <!-- Data Profil -->
                                <p class="text-muted small fw-semibold mb-2">DATA PROFIL</p>
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label small fw-semibold">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_lengkap"
                                        name="nama_lengkap"
                                        value="<?php echo htmlspecialchars($agen['nama_lengkap']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label small fw-semibold">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?php
                                        echo htmlspecialchars($agen['alamat'] ?? '');
                                    ?></textarea>
                                </div>

                                <hr>

                                <!-- Data Akun -->
                                <p class="text-muted small fw-semibold mb-2">DATA AKUN LOGIN</p>
                                <div class="mb-3">
                                    <label for="username" class="form-label small fw-semibold">
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($agen['username']); ?>" required>
                                </div>

                                <hr>

                                <!-- Ganti Password (Opsional) -->
                                <p class="text-muted small fw-semibold mb-2">GANTI PASSWORD <span class="fw-normal">(opsional)</span></p>
                                <div class="mb-3">
                                    <label for="password_lama" class="form-label small fw-semibold">
                                        Password Lama
                                    </label>
                                    <input type="password" class="form-control" id="password_lama"
                                        name="password_lama" placeholder="Wajib diisi jika ingin ganti password">
                                </div>

                                <div class="mb-4">
                                    <label for="password_baru" class="form-label small fw-semibold">
                                        Password Baru
                                    </label>
                                    <input type="password" class="form-control" id="password_baru"
                                        name="password_baru" placeholder="Kosongkan jika tidak ingin ganti">
                                    <div class="form-text">
                                        Isi kedua field di atas hanya jika ingin mengganti password.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100"
                                    style="background:#0f3460; border:none;">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
