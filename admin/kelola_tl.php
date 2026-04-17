<?php
// ============================================================
// FILE: admin/kelola_tl.php
// FUNGSI: Manajemen Team Leader - Panel Admin
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$pesan = '';

// Proses Simpan TL Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_tl'])) {
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($username) || empty($password)) {
        $pesan = ['type' => 'danger', 'text' => 'Semua identitas TL wajib diisi.'];
    } else {
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_aman'"));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '@$username' sudah digunakan."];
        } else {
            $password_hash = md5($password);
            $nama_aman     = mysqli_real_escape_string($koneksi, $nama);
            $query = "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama_aman', '$username_aman', '$password_hash', 'tl')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Team Leader '$nama' berhasil didaftarkan."];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal mendaftarkan TL: ' . mysqli_error($koneksi)];
            }
        }
    }
}

// Proses Hapus TL
if (isset($_GET['delete'])) {
    $hapus_id = (int) $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $hapus_id AND role = 'tl'");
    mysqli_query($koneksi, "UPDATE users SET tl_id = NULL WHERE tl_id = $hapus_id");
    $pesan = ['type' => 'warning', 'text' => 'Akun Team Leader dihapus. Agen terkait telah dilepaskan.'];
}

$daftar_tl = mysqli_query($koneksi, "
    SELECT tl.id, tl.nama_lengkap, tl.username, tl.poin,
           COUNT(agen.id) AS jumlah_agen
    FROM users tl
    LEFT JOIN users agen ON agen.tl_id = tl.id AND agen.role = 'agen'
    WHERE tl.role = 'tl'
    GROUP BY tl.id
    ORDER BY tl.poin DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Team Leader | Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Manajemen Team Leader</h3>
                    <p class="text-muted small mb-0">Kelola akun pimpinan tim dan struktur reward poin.</p>
                </div>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahTL">
                    <i class="bi bi-person-plus me-1"></i> Tambah TL
                </button>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> py-2 small shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> <?php echo $pesan['text']; ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Pimpinan</th>
                                    <th>Username</th>
                                    <th>Total Agen</th>
                                    <th>Poin Akumulasi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tl = mysqli_fetch_assoc($daftar_tl)): ?>
                                <tr>
                                    <td class="ps-4 fw-bold small"><?php echo $tl['nama_lengkap']; ?></td>
                                    <td><code class="text-primary"><?php echo $tl['username']; ?></code></td>
                                    <td><span class="badge bg-light text-dark border px-3"><?php echo $tl['jumlah_agen']; ?> Agen</span></td>
                                    <td class="fw-bold text-success"><?php echo number_format($tl['poin']); ?> Poin</td>
                                    <td class="text-center">
                                        <a href="?delete=<?php echo $tl['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus privilege kepemimpinan TL ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4 border-0 shadow-sm">
                <i class="bi bi-award me-2"></i> <strong>Sistem Reward:</strong> Team Leader mendapatkan <strong>+10 poin</strong> untuk setiap transaksi agen di bawahnya yang disetujui Admin.
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah TL -->
    <div class="modal fade" id="modalTambahTL" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Registrasi Team Leader Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap TL" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username Akun</label>
                        <input type="text" name="username" class="form-control" placeholder="Username login" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password Login</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="save_tl" class="btn btn-primary fw-bold">Simpan Data TL</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
