<?php
// FILE: admin/kelola_agen.php
// FUNGSI: Admin dapat menambah agen baru dan melihat daftar agen

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$pesan = '';

// -----------------------------------------------------------
// PROSES: Menambah agen baru ke database
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_agen'])) {
    // Ambil dan bersihkan data dari form
    $nama = trim($_POST['nama_lengkap']);
    $alamat = trim($_POST['alamat']);
    $nik = trim($_POST['nik']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validasi: semua field harus diisi
    if (empty($nama) || empty($username) || empty($password) || empty($nik)) {
        $pesan = ['type' => 'danger', 'text' => 'Semua field wajib diisi!'];
    } else {
        // Cek apakah username sudah digunakan orang lain
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT id FROM users WHERE username = '$username_aman'"
        ));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '$username' sudah digunakan!"];
        } else {
            // Aman: hash password dan simpan agen baru ke database
            $password_hash = md5($password);
            $nama_aman = mysqli_real_escape_string($koneksi, $nama);
            $alamat_aman = mysqli_real_escape_string($koneksi, $alamat);
            $nik_aman = mysqli_real_escape_string($koneksi, $nik);

            $query = "INSERT INTO users (nama_lengkap, username, password, role, alamat, nik) 
                      VALUES ('$nama_aman', '$username_aman', '$password_hash', 'agen', '$alamat_aman', '$nik_aman')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Agen '$nama' berhasil ditambahkan!"];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal menambah agen: ' . mysqli_error($koneksi)];
            }
        }
    }
}

// -----------------------------------------------------------
// PROSES: Hapus agen (opsional tapi berguna untuk testing)
// -----------------------------------------------------------
if (isset($_GET['hapus'])) {
    $hapus_id = (int) $_GET['hapus'];
    // Hapus dari tabel users (data stok_agen terhapus otomatis via CASCADE)
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $hapus_id AND role = 'agen'");
    $pesan = ['type' => 'warning', 'text' => 'Agen berhasil dihapus.'];
}

// Ambil seluruh daftar agen beserta jumlah stok yang mereka miliki
$daftar_agen = mysqli_query($koneksi, "
    SELECT u.*, 
           COALESCE(sa.stok, 0) AS stok_dimiliki
    FROM users u
    LEFT JOIN stok_agen sa ON u.id = sa.agen_id
    WHERE u.role = 'agen'
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Agen - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .page-title {
            font-weight: 700;
            color: #1a1a2e;
        }

        /* Animasi untuk modal yang muncul */
        .modal.fade .modal-dialog {
            transform: translateY(-20px);
        }

        .modal.show .modal-dialog {
            transform: translateY(0);
            transition: transform 0.3s;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header dengan tombol Tambah Agen -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="page-title mb-1"><i class="bi bi-people me-2" style="color:#4e9af1;"></i> Kelola Agen
                    </h3>
                    <p class="text-muted small mb-0">Kelola data agen dan akun mereka.</p>
                </div>
                <!-- Tombol ini membuka modal form tambah agen -->
                <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambahAgen">
                    <i class="bi bi-person-plus me-1"></i> Tambah Agen Baru
                </button>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tabel Daftar Agen -->
            <div class="card shadow-sm border-0">
                <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff;">
                    <i class="bi bi-table me-1"></i> Daftar Agen Terdaftar
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>id</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>NIK</th>
                                    <th>Alamat</th>
                                    <th class="text-center">Stok Dimiliki</th>
                                    <th class="text-center">Hapus Akun?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                // Loop menampilkan setiap agen dari hasil query
                                while ($agen = mysqli_fetch_assoc($daftar_agen)):
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo $no++; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-person-circle me-1" style="color:#4e9af1;"></i>
                                            <strong>
                                                <?php echo htmlspecialchars($agen['nama_lengkap']); ?>
                                            </strong>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($agen['username']); ?></code></td>
                                        <td>
                                            <?php echo htmlspecialchars($agen['nik'] ?: '-'); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($agen['alamat'] ?: '-'); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <!-- Tampilkan stok agen dengan warna berbeda berdasarkan jumlah -->
                                            <span
                                                class="badge <?php echo $agen['stok_dimiliki'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $agen['stok_dimiliki']; ?> unit
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <!-- Tombol hapus agen -->
                                            <a href="?hapus=<?php echo $agen['id']; ?>" class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Hapus agen <?php echo htmlspecialchars($agen['nama_lengkap']); ?>? Semua data terkait juga akan terhapus!')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if (mysqli_num_rows($daftar_agen) == 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-person-x" style="font-size:1.5rem;"></i>
                                            <p class="mt-2 mb-0">Belum ada agen yang terdaftar.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- -----------------------------------------------
     MODAL: Form Tambah Agen Baru
     Modal muncul sebagai popup di atas halaman
     ----------------------------------------------- -->
    <div class="modal fade" id="modalTambahAgen" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:#1a1a2e; color:#fff;">
                    <h5 class="modal-title" id="modalLabel">
                        <i class="bi bi-person-plus me-2"></i> Form Tambah Agen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Form dikirim ke halaman ini sendiri menggunakan method POST -->
                <form method="POST" action="">
                    <div class="modal-body">
                        <!-- Field Nama Lengkap -->
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label small fw-semibold">Nama Lengkap <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required
                                placeholder="Nama lengkap agen">
                        </div>
                        <!-- Field NIK -->
                        <div class="mb-3">
                            <label for="nik" class="form-label small fw-semibold">NIK (KTP) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nik" name="nik" required
                                placeholder="16 digit NIK" maxlength="16">
                        </div>
                        <!-- Field Alamat -->
                        <div class="mb-3">
                            <label for="alamat" class="form-label small fw-semibold">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="2"
                                placeholder="Alamat lengkap agen"></textarea>
                        </div>
                        <hr>
                        <!-- Field Username (untuk login) -->
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-semibold">Username <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required
                                placeholder="Username untuk login">
                        </div>
                        <!-- Field Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required
                                placeholder="Minimal 6 karakter">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <!-- name="tambah_agen" adalah penanda untuk kondisi POST di atas -->
                        <button type="submit" name="tambah_agen" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Agen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Jika ada pesan error (dari validasi), buka kembali modal otomatis
    <?php if ($pesan && $pesan['type'] == 'danger' && isset($_POST['tambah_agen'])): ?>
        var modal = new bootstrap.Modal(document.getElementById('modalTambahAgen'));
            modal.show();
    <?php endif; ?>
    </script>
</body>

</html>