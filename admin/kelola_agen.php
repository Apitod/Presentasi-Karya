<?php

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$pesan = '';

// proses simpan agen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_agen']) && $is_admin) {
    $nama     = trim($_POST['nama_lengkap']);
    $alamat   = trim($_POST['alamat']);
    $nik      = trim($_POST['nik']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $tl_id    = (int) $_POST['tl_id'];

    if (empty($nama) || empty($username) || empty($password) || empty($nik)) {
        $pesan = ['type' => 'danger', 'text' => 'Semua kolom wajib diisi!'];
    } else {
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_aman'"));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '$username' sudah terdaftar!"];
        } else {
            $password_hash = md5($password);
            $nama_aman     = mysqli_real_escape_string($koneksi, $nama);
            $alamat_aman   = mysqli_real_escape_string($koneksi, $alamat);
            $nik_aman      = mysqli_real_escape_string($koneksi, $nik);
            $tl_sql = $tl_id;

            $query = "INSERT INTO users (nama_lengkap, username, password, role, tl_id, alamat, nik) 
                      VALUES ('$nama_aman', '$username_aman', '$password_hash', 'agen', $tl_sql, '$alamat_aman', '$nik_aman')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Agen '$nama' berhasil ditambahkan!"];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal menambah agen: ' . mysqli_error($koneksi)];
            }
        }
    }
}

// proses hapus agen
if (isset($_GET['hapus']) && $is_admin) {
    $hapus_id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $hapus_id AND role = 'agen'");
    $pesan = ['type' => 'warning', 'text' => 'Data agen telah dihapus.'];
}

// data team leader
$daftar_tl = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users WHERE role = 'tl' ORDER BY nama_lengkap ASC");

// data agen
if ($is_admin) {
    $where_agen = "WHERE u.role = 'agen'";
} else {
    $tl_session_id = (int) $_SESSION['user_id'];
    $where_agen    = "WHERE u.role = 'agen' AND u.tl_id = $tl_session_id";
}

$daftar_agen = mysqli_query($koneksi, "
    SELECT u.*, tl.nama_lengkap AS nama_tl 
    FROM users u
    LEFT JOIN users tl ON u.tl_id = tl.id
    $where_agen
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Agen | Sistem Manajemen</title>
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
                    <h3 class="fw-bold mb-1">Kelola Agen</h3>
                    <p class="text-muted small mb-0">Manajamen data agen dan penugasan Team Leader.</p>
                </div>
                <?php if ($is_admin): ?>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahAgen">
                    <i class="bi bi-person-plus me-1"></i> Tambah Agen
                </button>
                <?php endif; ?>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> py-2 small shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> <?php echo $pesan['text']; ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Lengkap</th>
                                    <th>NIK</th>
                                    <th>Username</th>
                                    <th>Team Leader</th>
                                    <?php if($is_admin): ?><th class="text-center">Aksi</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($agen = mysqli_fetch_assoc($daftar_agen)): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($agen['nama_lengkap']); ?></strong></td>
                                    <td class="small"><?php echo htmlspecialchars($agen['nik']); ?></td>
                                    <td><code class="text-primary"><?php echo htmlspecialchars($agen['username']); ?></code></td>
                                    <td>
                                        <?php if($agen['nama_tl']): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle"><?php echo $agen['nama_tl']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Belum Ada TL</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if($is_admin): ?>
                                    <td class="text-center">
                                        <a href="edit_agen.php?id=<?php echo $agen['id']; ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a>
                                        <a href="?hapus=<?php echo $agen['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus agen ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($daftar_agen) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted small">Tidak ada data agen ditemukan.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Agen -->
    <?php if ($is_admin): ?>
    <div class="modal fade" id="modalTambahAgen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Tambah Agen Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap Agen" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIK</label>
                        <input type="text" name="nik" class="form-control" placeholder="16 Digit NIK" required maxlength="16">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Team Leader</label>
                        <select name="tl_id" class="form-select" required>
                            <option value="" disabled selected>Pilih Team Leader</option>
                            <?php mysqli_data_seek($daftar_tl, 0); while($tl = mysqli_fetch_assoc($daftar_tl)): ?>
                                <option value="<?php echo $tl['id']; ?>"><?php echo $tl['nama_lengkap']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat Lengkap"></textarea>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Username untuk login" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password Dasar</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_agen" class="btn btn-primary fw-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>