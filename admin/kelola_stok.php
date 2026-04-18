<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');

$pesan = '';

// proses tambah stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_stok']) && $is_admin) {
    $jumlah = (int) $_POST['jumlah_stok'];

    if ($jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Jumlah stok harus lebih dari 0!'];
    } else {
        mysqli_query($koneksi, "UPDATE produk SET stok = stok + $jumlah WHERE id = 1");
        $pesan = ['type' => 'success', 'text' => "Stok berhasil ditambah sebanyak $jumlah unit!"];
    }
}
// proses setujui request
if (isset($_GET['approve_request']) && $is_admin) {
    $request_id = (int) $_GET['approve_request'];

    $req = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT * FROM request_stok WHERE id = $request_id AND status = 'pending'"
    ));

    if ($req) {
        $jumlah  = $req['jumlah'];
        $agen_id = $req['agen_id'];

        $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM produk WHERE id = 1"));

        if ($produk['stok'] < $jumlah) {
            $pesan = ['type' => 'danger', 'text' => "Stok produk tidak cukup! Tersisa {$produk['stok']} unit, request butuh $jumlah unit."];
        } else {
            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $jumlah WHERE id = 1");

            // Tambahkan stok ke akun agen yang mengajukan request
            // Cek dulu: apakah agen sudah punya record di tabel stok_agen atau belum?
            $cek_stok = mysqli_fetch_assoc(mysqli_query(
                $koneksi,
                "SELECT id FROM stok_agen WHERE agen_id = $agen_id"
            ));

            if ($cek_stok) {
                // Sudah punya record: UPDATE (tambahkan ke stok yang ada)
                mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
            } else {
                // Belum punya record: INSERT baru
                mysqli_query($koneksi, "INSERT INTO stok_agen (agen_id, stok) VALUES ($agen_id, $jumlah)");
            }

            // Ubah status request dari 'pending' menjadi 'approved'
            mysqli_query($koneksi, "UPDATE request_stok SET status = 'approved' WHERE id = $request_id");
            $pesan = ['type' => 'success', 'text' => "Request stok disetujui! Stok agen bertambah $jumlah unit."];
        }
    }
}


// proses tolak request
if (isset($_GET['reject_request']) && $is_admin) {
    $request_id = (int) $_GET['reject_request'];
    // Cukup ubah status menjadi 'rejected', stok tidak berubah
    mysqli_query($koneksi, "UPDATE request_stok SET status = 'rejected' WHERE id = $request_id");
    $pesan = ['type' => 'warning', 'text' => 'Request stok telah ditolak.'];
}

// data produk
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

// data request pending
$request_list = mysqli_query($koneksi, "
    SELECT r.*, u.nama_lengkap AS nama_pemohon, u.role
    FROM request_stok r
    JOIN users u ON r.agen_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- header -->
            <div class="mb-4">
                <h3 class="page-title mb-1">
                    <i class="bi bi-boxes me-2 text-primary"></i> Kelola Stok
                </h3>
                <p class="text-muted small mb-0">
                    Tambah stok produk dan proses permintaan stok dari agen.
                    <?php if (!$is_admin): ?>
                        <span class="badge bg-info text-dark ms-2">Mode TL: Hanya Lihat</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- info stok -->
                <div class="col-md-5">
                    <!-- Kartu Info Stok Saat Ini -->
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-body text-center py-4 px-4">
                            <div class="mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-4"
                                      style="width:64px;height:64px;background:#e8f1ff;">
                                    <i class="bi bi-box-seam" style="font-size:2rem;color:#4e9af1;"></i>
                                </span>
                            </div>
                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($produk['nama_produk']); ?></h5>
                            <p class="text-muted small mb-3">Produk Utama</p>
                            <div class="rounded-4 p-3" style="background:#f4f6f9;">
                                <p class="mb-1 small text-muted">Stok Tersedia</p>
                                <h2 class="fw-bold mb-0" style="color:#1a1a2e;">
                                    <?php echo number_format($produk['stok']); ?>
                                </h2>
                                <small class="text-muted">unit</small>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                                    Harga: Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?> / unit
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- form tambah stok -->
                    <?php if ($is_admin): ?>
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header fw-semibold border-0 rounded-top-4" style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-plus-circle me-2"></i> Tambah Stok Produk
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="jumlah_stok" class="form-label small fw-semibold">
                                        Jumlah yang Ditambahkan
                                    </label>
                                    <!-- Input hanya menerima angka positif (min="1") -->
                                    <input type="number" class="form-control rounded-3" id="jumlah_stok"
                                        name="jumlah_stok" min="1" placeholder="Contoh: 50" required>
                                    <div class="form-text">Stok produk utama akan bertambah sejumlah ini.</div>
                                </div>
                                <!-- name="tambah_stok" adalah penanda untuk kondisi POST di atas -->
                                <button type="submit" name="tambah_stok" class="btn btn-primary w-100 rounded-3 fw-semibold">
                                    <i class="bi bi-plus-lg me-2"></i> Tambah Stok
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Tampilkan info read-only untuk TL -->
                    <div class="alert alert-info border-0 rounded-4" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Sebagai Team Leader, Anda hanya dapat memantau stok. Hubungi Admin untuk menambah stok.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- daftar request -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header fw-semibold border-0 rounded-top-4 d-flex align-items-center gap-2"
                             style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-inbox me-1"></i> Request Stok dari Agen & TL
                            <!-- Badge jumlah request yang menunggu -->
                            <span class="badge bg-warning text-dark ms-auto">
                                <?php echo mysqli_num_rows($request_list); ?> pending
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($request_list) == 0): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2.5rem;opacity:0.3;"></i>
                                    <p class="mt-3 mb-0 small">Tidak ada request stok yang menunggu. 🎉</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Pemohon</th>
                                                <th class="text-center">Jumlah</th>
                                                <th>Catatan</th>
                                                <?php if ($is_admin): ?>
                                                    <th class="text-center pe-4">Aksi</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($req = mysqli_fetch_assoc($request_list)): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <i class="bi bi-person me-2 text-muted"></i>
                                                        <strong><?php echo htmlspecialchars($req['nama_pemohon']); ?></strong>
                                                        <span class="badge bg-light text-dark border small ms-1"><?php echo strtoupper($req['role']); ?></span>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary rounded-pill fs-6 px-3">
                                                            <?php echo $req['jumlah']; ?> unit
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $req['catatan'] ? htmlspecialchars($req['catatan']) : '-'; ?>
                                                        </small>
                                                    </td>
                                                    <?php if ($is_admin): ?>
                                                        <td class="text-center pe-4">
                                                            <!-- Tombol Setujui -->
                                                            <a href="?approve_request=<?php echo $req['id']; ?>"
                                                               class="btn btn-success btn-sm rounded-3 me-1"
                                                               onclick="return confirm('Setujui request stok <?php echo $req['jumlah']; ?> unit dari <?php echo htmlspecialchars($req['nama_pemohon']); ?>?')">
                                                                <i class="bi bi-check-lg"></i>
                                                            </a>
                                                            <!-- Tombol Tolak -->
                                                            <a href="?reject_request=<?php echo $req['id']; ?>"
                                                               class="btn btn-danger btn-sm rounded-3"
                                                               onclick="return confirm('Tolak request ini?')">
                                                                <i class="bi bi-x-lg"></i>
                                                            </a>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>