<?php
// ============================================================
// FILE: agen/riwayat.php
// FUNGSI: Menampilkan semua riwayat transaksi milik agen ini
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];

// Ambil semua transaksi milik agen yang sedang login, diurutkan terbaru
$transaksi = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC"
);

// Hitung total pendapatan yang sudah disetujui
$total_data = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT SUM(total_harga) AS total, COUNT(id) AS jumlah_trx 
     FROM transaksi 
     WHERE agen_id = $agen_id AND status = 'approved'"
));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <h3 class="page-title mb-1">
                <i class="bi bi-clock-history me-2" style="color:#4fd1c5;"></i> Riwayat Transaksi
            </h3>
            <p class="text-muted small mb-4">Semua transaksi penjualan yang pernah Anda buat.</p>

            <!-- Ringkasan Statistik -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm"
                        style="border-left: 4px solid #28a745 !important; border-left-width: 4px !important;">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Transaksi Disetujui</p>
                            <h3 class="fw-bold mb-0">
                                <?php echo $total_data['jumlah_trx'] ?? 0; ?> transaksi
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Pendapatan (Disetujui)</p>
                            <h3 class="fw-bold mb-0">
                                Rp
                                <?php echo number_format($total_data['total'] ?? 0, 0, ',', '.'); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Transaksi Lengkap -->
            <div class="card shadow-sm border-0">
                <div class="card-header fw-semibold"
                    style="background: #0f3460; color:#fff; border-radius: 10px 10px 0 0;">
                    <i class="bi bi-table me-1"></i> Semua Transaksi
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Pembeli</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Total Harga</th>
                                    <th>Bukti Transaksi</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $badge_class = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                $badge_label = ['pending' => '⏳ Pending', 'approved' => '✓ Disetujui', 'rejected' => '✗ Ditolak'];

                                // Loop setiap transaksi
                                while ($trx = mysqli_fetch_assoc($transaksi)):
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo $no++; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($trx['nama_pembeli']); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo $trx['jumlah']; ?> unit
                                        </td>
                                        <td class="fw-semibold">
                                            Rp
                                            <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td>
                                            <!-- Tampilkan thumbnail gambar bukti transaksi -->
                                            <?php if ($trx['bukti_transaksi']): ?>
                                                <a href="../uploads/<?php echo htmlspecialchars($trx['bukti_transaksi']); ?>" target="_blank">
                                                    <img src="../uploads/<?php echo htmlspecialchars($trx['bukti_transaksi']); ?>"
                                                         alt="Bukti" style="width:45px;height:45px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $badge_class[$trx['status']]; ?>">
                                                <?php echo $badge_label[$trx['status']]; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if (mysqli_num_rows($transaksi) == 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-receipt" style="font-size:1.5rem;"></i>
                                            <p class="mt-2 mb-0">
                                                Belum ada transaksi. <a href="penjualan.php">Buat transaksi pertama!</a>
                                            </p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>