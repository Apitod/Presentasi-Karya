<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = (int) $_SESSION['user_id'];

// Statistik Agen
$stok_saya = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"))['stok'] ?? 0;
$total_penjualan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM transaksi WHERE agen_id = $agen_id AND status = 'approved'"))['total'] ?? 0;
$transaksi_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM transaksi WHERE agen_id = $agen_id AND status = 'pending'"));

// Riwayat Penjualan Terbaru
$recent_sales = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC LIMIT 5");

// Grafik Penjualan Pribadi (7 Hari Terakhir)
$grafik_penjualan = mysqli_query($koneksi, "
    SELECT DATE(created_at) as tanggal, SUM(total_harga) as total_harian
    FROM transaksi 
    WHERE status = 'approved' AND agen_id = $agen_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

$label_tanggal = [];
$data_penjualan = [];

// Siapkan array 7 hari terakhir
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label_tanggal[$tgl] = date('d M', strtotime($tgl));
    $data_penjualan[$tgl] = 0;
}

while ($row = mysqli_fetch_assoc($grafik_penjualan)) {
    $tgl = $row['tanggal'];
    if (isset($data_penjualan[$tgl])) {
        $data_penjualan[$tgl] = (int) $row['total_harian'];
    }
}

$labels_js = json_encode(array_values($label_tanggal));
$data_js = json_encode(array_values($data_penjualan));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Agen | Manajemen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Halo, <?php echo explode(' ', $_SESSION['nama_lengkap'])[0]; ?></h2>
                <p class="text-muted small">Kelola stok dan pantau analitik penjualan pribadi Anda di sini.</p>
            </div>

            <!-- Kartu Statistik -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Stok Saya</div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo number_format($stok_saya); ?> <small class="fs-6 text-muted">Unit</small></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Total Pencapaian</div>
                        <h3 class="fw-bold mb-0 text-success">Rp <?php echo number_format($total_penjualan); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Trx Pending</div>
                        <h3 class="fw-bold mb-0 text-warning"><?php echo $transaksi_pending; ?> <small class="fs-6 text-muted">Data</small></h3>
                    </div>
                </div>
            </div>

            <!-- Grafik Penjualan Agen -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Kinerja Penjualan Saya</h5>
                            <span class="badge bg-light text-muted border">7 Hari Terakhir</span>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Penjualan -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold border-0 pt-4 px-4">Ringkasan Riwayat Penjualan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No Ref</th>
                                    <th>Pembeli</th>
                                    <th>Total Nilai</th>
                                    <th>Status Pencatatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_sales)): ?>
                                <tr>
                                    <td class="ps-4 small text-muted">TRX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold small"><?php echo $row['nama_pembeli']; ?></td>
                                    <td class="text-primary fw-bold">Rp <?php echo number_format($row['total_harga']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] == 'approved' ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill small" style="font-size: 0.65rem;">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($recent_sales) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">Anda belum melakukan pencatatan transaksi.</td>
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
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const labels = <?php echo $labels_js; ?>;
        const dataPenjualan = <?php echo $data_js; ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Omzet Penjualan Pribadi (Rp)',
                    data: dataPenjualan,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0' },
                        ticks: {
                            callback: function(value) {
                                if(value >= 1000000) return (value / 1000000) + ' Jt';
                                if(value >= 1000) return (value / 1000) + ' Rb';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>