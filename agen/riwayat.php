<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];

// Ambil semua transaksi milik agen yang sedang login, diurutkan terbaru
$transaksi = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC"
);

// Ambil riwayat request stok milik agen yang sedang login
$request_stok = mysqli_query(
    $koneksi,
    "SELECT * FROM request_stok WHERE agen_id = $agen_id ORDER BY created_at DESC"
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

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" id="riwayatTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="transaksi-tab" data-bs-toggle="tab" data-bs-target="#transaksi-pane" type="button" role="tab" style="color:#0f3460;">
                        <i class="bi bi-receipt-cutoff me-1"></i> Riwayat Transaksi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="stok-tab" data-bs-toggle="tab" data-bs-target="#stok-pane" type="button" role="tab" style="color:#0f3460;">
                        <i class="bi bi-boxes me-1"></i> Riwayat Request Stok
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="riwayatTabContent">
                <!-- Tab Transaksi -->
                <div class="tab-pane fade show active" id="transaksi-pane" role="tabpanel">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>Nama Pembeli</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Total Harga</th>
                                            <th>Bukti</th>
                                            <th>Tanggal</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $badge_class = ['pending' => 'warning text-dark', 'pending_tl' => 'warning text-dark', 'pending_admin' => 'info text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                        $badge_label = ['pending' => '⏳ Pending', 'pending_tl' => '⏳ Menunggu TL', 'pending_admin' => '🔍 Cek Admin', 'approved' => '✓ Disetujui', 'rejected' => '✗ Ditolak'];

                                        while ($trx = mysqli_fetch_assoc($transaksi)):
                                        ?>
                                            <tr>
                                                <td class="ps-4"><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($trx['nama_pembeli']); ?></td>
                                                <td class="text-center"><?php echo $trx['jumlah']; ?> unit</td>
                                                <td class="fw-semibold">Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($trx['bukti_transaksi']): ?>
                                                        <a href="../uploads/<?php echo htmlspecialchars($trx['bukti_transaksi']); ?>" target="_blank">
                                                            <img src="../uploads/<?php echo htmlspecialchars($trx['bukti_transaksi']); ?>" alt="Bukti" class="rounded-3" style="width:45px;height:45px;object-fit:cover;border:1px solid #ddd;">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $badge_class[$trx['status']]; ?> rounded-pill">
                                                        <?php echo $badge_label[$trx['status']]; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($transaksi) == 0): ?>
                                            <tr><td colspan="7" class="text-center py-4">Belum ada transaksi.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Request Stok -->
                <div class="tab-pane fade" id="stok-pane" role="tabpanel">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Tanggal</th>
                                            <th class="text-center">Jumlah Request</th>
                                            <th>Catatan</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($req = mysqli_fetch_assoc($request_stok)): ?>
                                        <tr>
                                            <td class="ps-4"><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></small></td>
                                            <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo $req['jumlah']; ?> unit</span></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($req['catatan'] ?: '-'); ?></small></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $badge_class[$req['status']]; ?> rounded-pill">
                                                    <?php echo $badge_label[$req['status']]; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($request_stok) == 0): ?>
                                            <tr><td colspan="4" class="text-center py-4">Belum ada history request stok.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>