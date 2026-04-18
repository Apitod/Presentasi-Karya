<?php

require_once 'cek_sesi.php';
require_once '../koneksi.php';

// Ambil Statistik
$stok_global = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(stok) as total FROM produk"))['total'] ?? 0;
$total_agen = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'agen'"));
$transaksi_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM transaksi WHERE status = 'pending_admin'"));
$request_stok_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM request_stok WHERE status = 'pending'"));

// Data Kinerja Agen (Top 5 berdasarkan total pendapatan disetujui)
$top_agents = mysqli_query($koneksi, "
    SELECT u.nama_lengkap, SUM(t.total_harga) AS total_pendapatan
    FROM users u
    JOIN transaksi t ON u.id = t.agen_id AND t.status = 'approved'
    WHERE u.role = 'agen'
    GROUP BY u.id
    ORDER BY total_pendapatan DESC
    LIMIT 5
");

// Data Kinerja Team Leader (Top 5 berdasarkan poin)
$top_tls = mysqli_query($koneksi, "
    SELECT nama_lengkap, poin 
    FROM users 
    WHERE role = 'tl' 
    ORDER BY poin DESC 
    LIMIT 5
");

// Data Grafik Penjualan (7 Hari Terakhir)
$grafik_penjualan = mysqli_query($koneksi, "
    SELECT DATE(created_at) as tanggal, SUM(total_harga) as total_harian
    FROM transaksi 
    WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

$label_tanggal = [];
$data_penjualan = [];

// Siapkan array 7 hari terakhir agar grafik tidak kosong jika tak ada transaksi
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
    <title>Dasbor Admin | Manajemen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Include Chart.js untuk Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Selamat Datang, <?php echo explode(' ', $_SESSION['nama_lengkap'])[0]; ?></h2>
                <p class="text-muted small">Berikut adalah ringkasan operasional sistem beserta analitik penjualan.</p>
            </div>

            <!-- Kartu Statistik -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Stok Tersedia</div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo number_format($stok_global); ?> <small class="fs-6 text-muted">Unit</small></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Total Agen</div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $total_agen; ?> <small class="fs-6 text-muted">Orang</small></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Transaksi Pending</div>
                        <h3 class="fw-bold mb-0 text-warning"><?php echo $transaksi_pending; ?> <small class="fs-6 text-muted">Data</small></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Request Stok</div>
                        <h3 class="fw-bold mb-0 text-danger"><?php echo $request_stok_pending; ?> <small class="fs-6 text-muted">Request</small></h3>
                    </div>
                </div>
            </div>

            <!-- Grafik Penjualan -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Grafik Penjualan Harian</h5>
                            <span class="badge bg-light text-muted border">7 Hari Terakhir</span>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Agen dan Team Leader -->
            <div class="row g-4 pb-5">
                <!-- Top 5 Agen -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold border-0 pt-4 px-4"><i class="bi bi-trophy-fill text-warning me-2"></i>Top 5 Agen Terbaik</div>
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
                                        <?php $no=1; while($agent = mysqli_fetch_assoc($top_agents)): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small fw-bold">#<?php echo $no++; ?></td>
                                            <td class="fw-bold small"><?php echo $agent['nama_lengkap']; ?></td>
                                            <td class="text-end pe-4 text-primary fw-bold">Rp <?php echo number_format($agent['total_pendapatan']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($top_agents) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted small">Belum ada data penjualan tersedia.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Team Leader -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold border-0 pt-4 px-4"><i class="bi bi-star-fill text-success me-2"></i>Top 5 Team Leader</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Nama Team Leader</th>
                                            <th class="text-end pe-4">Total Poin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no=1; while($tl = mysqli_fetch_assoc($top_tls)): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small fw-bold">#<?php echo $no++; ?></td>
                                            <td class="fw-bold small"><?php echo $tl['nama_lengkap']; ?></td>
                                            <td class="text-end pe-4 text-success fw-bold"><?php echo number_format($tl['poin']); ?> <small class="text-muted fw-normal">Pts</small></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($top_tls) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted small">Belum ada data poin TL tersedia.</td>
                                        </tr>
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
    <script>
        // Konfigurasi Chart.js
        const ctx = document.getElementById('salesChart').getContext('2d');
        const labels = <?php echo $labels_js; ?>;
        const dataPenjualan = <?php echo $data_js; ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Penjualan (Rp)',
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
                    tension: 0.3 // Lengkungan garis
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        },
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