<?php
// ============================================================
// FILE: admin/edit_agen.php
// FUNGSI: Admin dapat mengedit data profil agen (nama, alamat,
//         NIK, username, dan password)
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$pesan = '';

// Ambil ID agen yang ingin diedit dari parameter URL
// Contoh URL: edit_agen.php?id=3
if (!isset($_GET['id'])) {
    // Jika tidak ada ID, kembali ke halaman kelola agen
    header('Location: kelola_agen.php');
    exit;
}

$agen_id = (int) $_GET['id'];

// Ambil data agen berdasarkan ID (pastikan role = 'agen')
$agen = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $agen_id AND role = 'agen'"
));

// Jika agen tidak ditemukan, kembali ke daftar
if (!$agen) {
    header('Location: kelola_agen.php');
    exit;
}

// -----------------------------------------------------------
// PROSES: Simpan perubahan data agen
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = trim($_POST['nama_lengkap']);
    $alamat   = trim($_POST['alamat']);
    $nik      = trim($_POST['nik']);
    $username = trim($_POST['username']);
    $password_baru = trim($_POST['password_baru']);

    if (empty($nama) || empty($username) || empty($nik)) {
        $pesan = ['type' => 'danger', 'text' => 'Nama, NIK, dan Username wajib diisi!'];
    } else {
        // Cek apakah username sudah dipakai user LAIN (bukan diri sendiri)
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT id FROM users WHERE username = '$username_aman' AND id != $agen_id"
        ));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '$username' sudah digunakan orang lain!"];
        } else {
            $nama_aman   = mysqli_real_escape_string($koneksi, $nama);
            $alamat_aman = mysqli_real_escape_string($koneksi, $alamat);
            $nik_aman    = mysqli_real_escape_string($koneksi, $nik);

            // Jika admin mengisi password baru, update password juga
            // Jika kosong, password lama tetap dipakai
            if (!empty($password_baru)) {
                $pass_hash = md5($password_baru);
                $query = "UPDATE users SET 
                            nama_lengkap = '$nama_aman',
                            alamat       = '$alamat_aman',
                            nik          = '$nik_aman',
                            username     = '$username_aman',
                            password     = '$pass_hash'
                          WHERE id = $agen_id AND role = 'agen'";
            } else {
                // Tidak update password
                $query = "UPDATE users SET 
                            nama_lengkap = '$nama_aman',
                            alamat       = '$alamat_aman',
                            nik          = '$nik_aman',
                            username     = '$username_aman'
                          WHERE id = $agen_id AND role = 'agen'";
            }

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => 'Data agen berhasil diperbarui!'];
                // Refresh data agen setelah update
                $agen = mysqli_fetch_assoc(mysqli_query(
                    $koneksi,
                    "SELECT * FROM users WHERE id = $agen_id"
                ));
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal memperbarui data!'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Agen - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header -->
            <div class="mb-4">
                <a href="kelola_agen.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Agen
                </a>
                <h3 class="page-title mb-1">
                    <i class="bi bi-person-gear me-2" style="color:#4e9af1;"></i> Edit Profil Agen
                </h3>
                <p class="text-muted small mb-0">
                    Mengedit data akun: <strong><?php echo htmlspecialchars($agen['nama_lengkap']); ?></strong>
                </p>
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
                        <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-pencil-square me-2"></i> Form Edit Agen
                        </div>
                        <div class="card-body">
                            <!-- action="" akan kirim POST ke halaman ini sendiri (plus tetap bawa ?id=X) -->
                            <form method="POST" action="?id=<?php echo $agen_id; ?>">

                                <!-- Data Profil -->
                                <p class="text-muted small fw-semibold mb-2">DATA PROFIL</p>
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label small fw-semibold">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                                        value="<?php echo htmlspecialchars($agen['nama_lengkap']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="nik" class="form-label small fw-semibold">
                                        NIK (KTP) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nik" name="nik"
                                        value="<?php echo htmlspecialchars($agen['nik'] ?? ''); ?>"
                                        maxlength="16" required>
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

                                <div class="mb-4">
                                    <label for="password_baru" class="form-label small fw-semibold">Password Baru</label>
                                    <input type="password" class="form-control" id="password_baru" name="password_baru"
                                        placeholder="Kosongkan jika tidak ingin mengganti password">
                                    <div class="form-text">
                                        Isi hanya jika ingin mengganti password agen ini.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
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
