<?php
// ============================================================
// FILE: admin/edit_agen.php
// FUNGSI: Admin mengedit data profil agen (nama, NIK, alamat,
//         username, password, dan Team Leader).
//
// PEMBARUAN v2: Tambah dropdown untuk mengubah TL atasan agen.
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

// Hanya Admin yang boleh mengedit agen
if ($_SESSION['role'] !== 'admin') {
    header('Location: kelola_agen.php');
    exit;
}

$pesan = '';

// Ambil ID agen dari parameter URL (contoh: edit_agen.php?id=3)
if (!isset($_GET['id'])) {
    header('Location: kelola_agen.php');
    exit;
}

$agen_id = (int) $_GET['id']; // Paksa ke integer untuk keamanan

// Ambil data agen yang akan diedit (pastikan role-nya memang 'agen')
$agen = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $agen_id AND role = 'agen'"
));

// Jika ID tidak ditemukan atau bukan agen, kembalikan ke daftar
if (!$agen) {
    header('Location: kelola_agen.php');
    exit;
}

// Ambil daftar semua TL untuk dropdown pilihan
$daftar_tl = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users WHERE role = 'tl' ORDER BY nama_lengkap ASC");

// -----------------------------------------------------------
// PROSES: Simpan perubahan data agen ke database
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama          = trim($_POST['nama_lengkap']);
    $alamat        = trim($_POST['alamat']);
    $nik           = trim($_POST['nik']);
    $username      = trim($_POST['username']);
    $password_baru = trim($_POST['password_baru']);
    $tl_id         = (int) $_POST['tl_id']; // BARU: ID TL yang dipilih admin

    if (empty($nama) || empty($username) || empty($nik)) {
        $pesan = ['type' => 'danger', 'text' => 'Nama, NIK, dan Username wajib diisi!'];
    } else {
        // Pastikan username tidak dipakai user LAIN (kecuali dirinya sendiri)
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

            // Tentukan nilai tl_id: jika admin memilih "Tanpa TL", simpan NULL
            $tl_sql = ($tl_id > 0) ? $tl_id : 'NULL';

            // Jika admin mengisi password baru, sertakan dalam query UPDATE
            // Jika tidak, password lama tetap dipakai (tidak diubah)
            if (!empty($password_baru)) {
                $pass_hash = md5($password_baru);
                $query = "UPDATE users SET
                            nama_lengkap = '$nama_aman',
                            alamat       = '$alamat_aman',
                            nik          = '$nik_aman',
                            username     = '$username_aman',
                            password     = '$pass_hash',
                            tl_id        = $tl_sql
                          WHERE id = $agen_id AND role = 'agen'";
            } else {
                // Tidak update password, hanya data profil + tl_id
                $query = "UPDATE users SET
                            nama_lengkap = '$nama_aman',
                            alamat       = '$alamat_aman',
                            nik          = '$nik_aman',
                            username     = '$username_aman',
                            tl_id        = $tl_sql
                          WHERE id = $agen_id AND role = 'agen'";
            }

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => 'Data agen berhasil diperbarui!'];
                // Refresh data agen setelah update agar form menampilkan data terbaru
                $agen = mysqli_fetch_assoc(mysqli_query(
                    $koneksi,
                    "SELECT * FROM users WHERE id = $agen_id"
                ));
                // Reset resource TL agar bisa di-loop ulang
                mysqli_data_seek($daftar_tl, 0);
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal memperbarui data: ' . mysqli_error($koneksi)];
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
                <a href="kelola_agen.php" class="btn btn-sm btn-outline-secondary rounded-3 mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Agen
                </a>
                <h3 class="page-title mb-1">
                    <i class="bi bi-person-gear me-2 text-primary"></i> Edit Profil Agen
                </h3>
                <p class="text-muted small mb-0">
                    Mengedit data akun: <strong><?php echo htmlspecialchars($agen['nama_lengkap']); ?></strong>
                </p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show shadow-sm border-0 rounded-3">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header fw-semibold border-0 rounded-top-4" style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-pencil-square me-2"></i> Form Edit Agen
                        </div>
                        <div class="card-body p-4">
                            <!-- action tetap bawa ?id=X agar PHP tahu agen mana yang diedit -->
                            <form method="POST" action="?id=<?php echo $agen_id; ?>">

                                <!-- SEKSI: Data Profil -->
                                <p class="text-muted small fw-bold text-uppercase mb-3 border-bottom pb-2">
                                    <i class="bi bi-person me-1"></i> Data Profil
                                </p>

                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label small fw-semibold">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control rounded-3" id="nama_lengkap" name="nama_lengkap"
                                        value="<?php echo htmlspecialchars($agen['nama_lengkap']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="nik" class="form-label small fw-semibold">
                                        NIK (KTP) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control rounded-3" id="nik" name="nik"
                                        value="<?php echo htmlspecialchars($agen['nik'] ?? ''); ?>"
                                        maxlength="16" required>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label small fw-semibold">Alamat</label>
                                    <textarea class="form-control rounded-3" id="alamat" name="alamat" rows="2"><?php
                                        echo htmlspecialchars($agen['alamat'] ?? '');
                                    ?></textarea>
                                </div>

                                <!-- BARU: Dropdown pilih Team Leader -->
                                <div class="mb-3">
                                    <label for="tl_id" class="form-label small fw-semibold">
                                        <i class="bi bi-person-badge me-1"></i> Team Leader Atasan
                                    </label>
                                    <select class="form-select rounded-3" id="tl_id" name="tl_id">
                                        <option value="0">-- Tanpa Team Leader --</option>
                                        <?php while ($tl = mysqli_fetch_assoc($daftar_tl)): ?>
                                            <option value="<?php echo $tl['id']; ?>"
                                                <?php echo ($agen['tl_id'] == $tl['id']) ? 'selected' : ''; ?>>
                                                <!-- 'selected' menandai opsi yang sudah tersimpan sebelumnya -->
                                                <?php echo htmlspecialchars($tl['nama_lengkap']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">Pilih TL jika agen ini ada dalam tim tertentu.</div>
                                </div>

                                <hr class="my-4">

                                <!-- SEKSI: Data Akun Login -->
                                <p class="text-muted small fw-bold text-uppercase mb-3 border-bottom pb-2">
                                    <i class="bi bi-key me-1"></i> Data Akun Login
                                </p>

                                <div class="mb-3">
                                    <label for="username" class="form-label small fw-semibold">
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control rounded-3" id="username" name="username"
                                        value="<?php echo htmlspecialchars($agen['username']); ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="password_baru" class="form-label small fw-semibold">Password Baru</label>
                                    <input type="password" class="form-control rounded-3" id="password_baru" name="password_baru"
                                        placeholder="Kosongkan jika tidak ingin mengganti password">
                                    <div class="form-text">
                                        Isi hanya jika ingin mengganti password agen ini.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                                    <i class="bi bi-save me-2"></i> Simpan Perubahan
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
