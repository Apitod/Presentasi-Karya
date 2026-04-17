<?php
// ============================================================
// FILE: tl/dashboard.php
// FUNGSI: Dasbor Team Leader - Bersih & Fungsional (Dengan Analitik)
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$tl_id = (int) $_SESSION['user_id'];

// Ambil Statistik Tim
$stok_global = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(stok) as total FROM produk"))['total'] ?? 0;
$total_agen = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'agen' AND tl_id = $tl_id"));
$poin_saya = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT poin FROM users WHERE id = $tl_id"))['poin'] ?? 0;

// Top 5 Agen Tim Sendiri
$top_agents_tim = mysqli_query($koneksi, "
    SELECT u.nama_lengkap, SUM(t.total_harga) AS total_pendapatan
    FROM users u
    JOIN transaksi t ON u.id = t.agen_id AND t.status = 'approved'
    WHERE u.role = 'agen' AND u.tl_id = $tl_id
    GROUP BY u.id
    ORDER BY total_pendapatan DESC
    LIMIT 5
");

// Grafik Penjualan Khusus Tim (7 Hari Terakhir)
$grafik_penjualan = mysqli_query($koneksi, "
    SELECT DATE(t.created_at) as tanggal, SUM(t.total_harga) as total_harian
    FROM transaksi t
    JOIN users u ON t.agen_id = u.id
    WHERE t.status = 'approved' AND u.tl_id = $tl_id AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(t.created_at)
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

// Aktivitas Penjualan Tim Terbaru
$recent_sales = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap 
    FROM transaksi t 
    JOIN users u ON t.agen_id = u.id 
    WHERE u.tl_id = $tl_id 
    ORDER BY t.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor TL | Manajemen Karyawan</title>
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
                <h2 class="fw-bold mb-1">Halo, Team Leader <?php echo explode(' ', $_SESSION['nama_lengkap'])[0]; ?></h2>
                <p class="text-muted small">Pantau kinerja dan capaian tim agen Anda di bawah ini.</p>
            </div>

            <!-- Kartu Statistik -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Total Tim Agen</div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo $total_agen; ?> <small class="fs-6 text-muted">Orang</small></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Poin Reward Saya</div>
                        <h3 class="fw-bold mb-0 text-success"><?php echo number_format($poin_saya); ?> <small class="fs-6 text-muted">Poin</small></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Stok Global</div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($stok_global); ?> <small class="fs-6 text-muted">Unit</small></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Grafik Penjualan Tim -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Kinerja Penjualan Tim</h5>
                            <span class="badge bg-light text-muted border">7 Hari Terakhir</span>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Agen Tim -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold border-0 pt-4 px-4"><i class="bi bi-star-fill text-warning me-2"></i>Top 5 Agen Terbaik Tim</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Nama Agen</th>
                                            <th class="text-end pe-4">Total Omzet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no=1; while($agent = mysqli_fetch_assoc($top_agents_tim)): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small fw-bold">#<?php echo $no++; ?></td>
                                            <td class="fw-bold small"><?php echo $agent['nama_lengkap']; ?></td>
                                            <td class="text-end pe-4 text-primary fw-bold">Rp <?php echo number_format($agent['total_pendapatan']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($top_agents_tim) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted small">Belum ada penjualan disetujui dalam tim.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaksi Terbaru Tim -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold border-0 pt-4 px-4">Penjualan Tim Terbaru</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No Ref</th>
                                    <th>Nama Agen</th>
                                    <th>Nilai Transaksi</th>
                                    <th>Status Audit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_sales)): ?>
                                <tr>
                                    <td class="ps-4 small text-muted">TRX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold small"><?php echo $row['nama_lengkap']; ?></td>
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
                                    <td colspan="4" class="text-center py-4 text-muted small">Belum ada aktivitas transaksi dari tim Anda.</td>
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
                    label: 'Omzet Tim (Rp)',
                    data: dataPenjualan,
                    borderColor: '#198754', /* Success color */
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#198754',
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
