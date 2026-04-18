<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

// 1. Riwayat Transaksi (semua)
$transaksi = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap AS nama_agen, tl.nama_lengkap AS nama_tl
    FROM transaksi t
    JOIN users u ON t.agen_id = u.id
    LEFT JOIN users tl ON u.tl_id = tl.id
    ORDER BY t.created_at DESC
");

// 2. Riwayat Request Stok (semua)
$request_stok = mysqli_query($koneksi, "
    SELECT r.*, u.nama_lengkap AS nama_agen, tl.nama_lengkap AS nama_tl
    FROM request_stok r
    JOIN users u ON r.agen_id = u.id
    LEFT JOIN users tl ON u.tl_id = tl.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <h3 class="page-title mb-1">
                <i class="bi bi-clock-history me-2 text-primary"></i> Riwayat Sistem
            </h3>
            <p class="text-muted small mb-4">Riwayat seluruh transaksi dan permintaan stok dari seluruh agen.</p>

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" id="riwayatTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="transaksi-tab" data-bs-toggle="tab" data-bs-target="#transaksi" type="button" role="tab" style="color:#1a1a2e;">
                        <i class="bi bi-receipt-cutoff me-1"></i> Transaksi Global
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="stok-tab" data-bs-toggle="tab" data-bs-target="#stok" type="button" role="tab" style="color:#1a1a2e;">
                        <i class="bi bi-boxes me-1"></i> Request Stok Global
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="riwayatTabContent">
                <!-- Tab Transaksi -->
                <div class="tab-pane fade show active" id="transaksi" role="tabpanel">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Tanggal</th>
                                            <th>Agen</th>
                                            <th>Team Leader</th>
                                            <th>Nama Pembeli</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Total</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $badge_class = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                        $badge_label = ['pending' => '⏳ Pending', 'approved' => '✓ Disetujui', 'rejected' => '✗ Ditolak'];
                                        while ($trx = mysqli_fetch_assoc($transaksi)):
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($trx['nama_agen']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                    <?php echo htmlspecialchars($trx['nama_tl'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($trx['nama_pembeli']); ?></td>
                                            <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo $trx['jumlah']; ?> unit</span></td>
                                            <td class="fw-semibold text-success">Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $badge_class[$trx['status']]; ?> rounded-pill">
                                                    <?php echo $badge_label[$trx['status']]; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Request Stok -->
                <div class="tab-pane fade" id="stok" role="tabpanel">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Tanggal</th>
                                            <th>Agen</th>
                                            <th>Team Leader</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Catatan</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($req = mysqli_fetch_assoc($request_stok)): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($req['nama_agen']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                    <?php echo htmlspecialchars($req['nama_tl'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo $req['jumlah']; ?> unit</span></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($req['catatan'] ?: '-'); ?></small></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $badge_class[$req['status']]; ?> rounded-pill">
                                                    <?php echo $badge_label[$req['status']]; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
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
