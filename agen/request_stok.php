<?php
// ============================================================
// FILE: agen/request_stok.php
// FUNGSI: Agen mengajukan permintaan tambahan stok ke admin
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id']; // Ambil ID agen dari sesi
$pesan = '';

// -----------------------------------------------------------
// PROSES: Simpan request stok baru ke database
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah = (int) $_POST['jumlah'];          // Konversi ke integer
    $catatan = trim($_POST['catatan'] ?? '');   // Catatan opsional dari agen

    if ($jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Jumlah request harus lebih dari 0!'];
    } else {
        $catatan_aman = mysqli_real_escape_string($koneksi, $catatan);

        // Masukkan request ke tabel request_stok dengan status awal 'pending'
        $query = "INSERT INTO request_stok (agen_id, jumlah, catatan, status) 
                  VALUES ($agen_id, $jumlah, '$catatan_aman', 'pending')";

        if (mysqli_query($koneksi, $query)) {
            $pesan = ['type' => 'success', 'text' => "Request stok sebanyak $jumlah unit berhasil dikirim! Menunggu persetujuan admin."];
        } else {
            $pesan = ['type' => 'danger', 'text' => 'Gagal mengirim request: ' . mysqli_error($koneksi)];
        }
    }
}

// Ambil riwayat semua request stok milik agen ini
$riwayat_request = mysqli_query(
    $koneksi,
    "SELECT * FROM request_stok WHERE agen_id = $agen_id ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Stok - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .page-title {
            font-weight: 700;
            color: #0f3460;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <h3 class="page-title mb-1">
                <i class="bi bi-arrow-up-circle me-2" style="color:#4fd1c5;"></i> Request Stok
            </h3>
            <p class="text-muted small mb-4">Ajukan permintaan penambahan stok kepada admin.</p>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Request Stok -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background: #0f3460; color:#fff;">
                            <i class="bi bi-send me-1"></i> Buat Request Baru
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="jumlah" class="form-label small fw-semibold">
                                        Jumlah Stok yang Diminta <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" min="1"
                                        placeholder="Contoh: 20" required>
                                    <div class="form-text">Masukkan jumlah unit yang Anda butuhkan.</div>
                                </div>
                                <div class="mb-4">
                                    <label for="catatan" class="form-label small fw-semibold">
                                        Catatan (opsional)
                                    </label>
                                    <textarea class="form-control" id="catatan" name="catatan" rows="3"
                                        placeholder="Contoh: untuk acara pameran minggu depan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"
                                    style="background: #0f3460; border: none;">
                                    <i class="bi bi-send me-1"></i> Kirim Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Request Sebelumnya -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background: #0f3460; color:#fff;">
                            <i class="bi bi-list-check me-1"></i> Riwayat Request Saya
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($riwayat_request) == 0): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                                    <p class="mt-2">Belum ada request yang pernah dibuat.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th class="text-center">Jumlah</th>
                                                <th>Catatan</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($req = mysqli_fetch_assoc($riwayat_request)): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <strong>
                                                            <?php echo $req['jumlah']; ?>
                                                        </strong> unit
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $req['catatan'] ? htmlspecialchars($req['catatan']) : '-'; ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php
                                                        // Tampilkan badge status dengan warna yang sesuai
                                                        $badge = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                                        $label = ['pending' => '⏳ Pending', 'approved' => '✓ Disetujui', 'rejected' => '✗ Ditolak'];
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge[$req['status']]; ?>">
                                                            <?php echo $label[$req['status']]; ?>
                                                        </span>
                                                    </td>
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