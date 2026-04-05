<?php
// FILE: admin/dashboard.php
// FUNGSI: Halaman utama admin - menampilkan ringkasan sistem

require_once 'cek_sesi.php';
require_once '../koneksi.php';

// -----------------------------------------------------------
// Ambil data statistik untuk ditampilkan di dashboard
// -----------------------------------------------------------

// 1. Ambil data produk utama (stok global)
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

// 2. Hitung total agen yang terdaftar
$total_agen = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'agen'"));

// 3. Hitung transaksi yang menunggu approval
$transaksi_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM transaksi WHERE status = 'pending'"));

// 4. Hitung total transaksi yang sudah disetujui
$transaksi_approved = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM transaksi WHERE status = 'approved'"));

// 5. Hitung request stok yang masih pending
$request_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM request_stok WHERE status = 'pending'"));

// 6. Data kinerja agen untuk tabel DAN chart
$query_kinerja = "
    SELECT u.nama_lengkap, 
           COUNT(t.id) AS total_transaksi, 
           SUM(t.total_harga) AS total_pendapatan
    FROM users u
    LEFT JOIN transaksi t ON u.id = t.agen_id AND t.status = 'approved'
    WHERE u.role = 'agen'
    GROUP BY u.id
    ORDER BY total_transaksi DESC
";
$kinerja_agen = mysqli_query($koneksi, $query_kinerja);

// Simpan hasil query ke array agar bisa dipakai di tabel DAN chart
$data_kinerja = [];
while ($baris = mysqli_fetch_assoc($kinerja_agen)) {
    $data_kinerja[] = $baris;
}

// Siapkan data JSON untuk Chart.js
$chart_labels    = json_encode(array_column($data_kinerja, 'nama_lengkap'));
$chart_transaksi = json_encode(array_column($data_kinerja, 'total_transaksi'));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS dipisah ke file terpisah agar modular -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header halaman -->
            <div class="mb-4">
                <h3 class="page-title"><i class="bi bi-speedometer2 me-2" style="color:#4e9af1;"></i> Dashboard</h3>
                <p class="text-muted small mb-0">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!</p>
            </div>

            <!-- -----------------------------------------------
             BARIS 1: Kartu Statistik Utama
             ----------------------------------------------- -->
            <div class="row g-3 mb-4">
                <!-- Kartu: Stok Produk -->
                <div class="col-md-3">
                    <div class="card stat-card blue shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Stok Produk</p>
                                <h4 class="fw-bold mb-0"><?php echo number_format($produk['stok']); ?> unit</h4>
                                <small class="text-muted"><?php echo htmlspecialchars($produk['nama_produk']); ?></small>
                            </div>
                            <div class="stat-icon" style="background:#e8f1ff;">
                                <i class="bi bi-boxes" style="color:#4e9af1;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu: Total Agen -->
                <div class="col-md-3">
                    <div class="card stat-card green shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Total Agen</p>
                                <h4 class="fw-bold mb-0"><?php echo $total_agen; ?> orang</h4>
                                <small class="text-muted">Agen terdaftar</small>
                            </div>
                            <div class="stat-icon" style="background:#e8fff0;">
                                <i class="bi bi-people" style="color:#28a745;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu: Transaksi Pending -->
                <div class="col-md-3">
                    <div class="card stat-card orange shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Transaksi Pending</p>
                                <h4 class="fw-bold mb-0"><?php echo $transaksi_pending; ?></h4>
                                <small class="text-muted">Butuh persetujuan</small>
                            </div>
                            <div class="stat-icon" style="background:#fff5e8;">
                                <i class="bi bi-hourglass-split" style="color:#fd7e14;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu: Transaksi Disetujui -->
                <div class="col-md-3">
                    <div class="card stat-card green shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Transaksi Disetujui</p>
                                <h4 class="fw-bold mb-0"><?php echo $transaksi_approved; ?></h4>
                                <small class="text-muted">Berhasil diproses</small>
                            </div>
                            <div class="stat-icon" style="background:#e8fff0;">
                                <i class="bi bi-check-circle" style="color:#28a745;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- -----------------------------------------------
             BARIS 2: Notifikasi, Chart, dan Tabel Kinerja
             ----------------------------------------------- -->
            <div class="row g-3">

                <!-- Notifikasi Request Stok Pending -->
                <?php if ($request_pending > 0): ?>
                    <div class="col-12">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-bell-fill me-2"></i>
                            <div>
                                Ada <strong><?php echo $request_pending; ?> permintaan stok</strong> dari agen yang menunggu persetujuan.
                                <a href="kelola_stok.php" class="alert-link ms-1">Lihat sekarang &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- -----------------------------------------------
                 CHART: Performa Transaksi Agen (Bar Chart)
                 Menggunakan Chart.js (CDN, tidak perlu install)
                 ----------------------------------------------- -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff; border-radius:10px 10px 0 0;">
                            <i class="bi bi-bar-chart me-2"></i> Chart Performa Agen
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <?php if (count($data_kinerja) > 0): ?>
                                <!-- Canvas adalah tempat Chart.js menggambar grafik -->
                                <canvas id="chartKinerja"></canvas>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">
                                    <i class="bi bi-bar-chart" style="font-size:2rem;"></i><br>
                                    Belum ada data agen.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tabel Kinerja Agen -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff; border-radius:10px 10px 0 0;">
                            <i class="bi bi-table me-2"></i> Tabel Kinerja Agen
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Agen</th>
                                            <th class="text-center">Transaksi</th>
                                            <th class="text-end">Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($data_kinerja) > 0): ?>
                                            <?php foreach ($data_kinerja as $i => $baris): ?>
                                                <tr>
                                                    <td><?php echo $i + 1; ?></td>
                                                    <td>
                                                        <i class="bi bi-person-circle me-1 text-muted"></i>
                                                        <?php echo htmlspecialchars($baris['nama_lengkap']); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary rounded-pill">
                                                            <?php echo $baris['total_transaksi']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-semibold">
                                                        Rp <?php echo number_format($baris['total_pendapatan'] ?? 0, 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    Belum ada agen terdaftar.
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
        </div><!-- /.flex-grow-1 -->
    </div><!-- /.d-flex -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js: library untuk membuat grafik/chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Ambil data dari PHP dan render chart
        // json_encode di PHP sudah mengubah data menjadi format yang bisa dibaca JS
        const labels    = <?php echo $chart_labels; ?>;
        const transaksi = <?php echo $chart_transaksi; ?>;

        const canvas = document.getElementById('chartKinerja');
        if (canvas) {
            new Chart(canvas, {
                type: 'bar', // Jenis grafik: bar (batang)
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Transaksi',
                        data: transaksi,
                        backgroundColor: '#4e9af1',
                        borderRadius: 6,  // Sudut melengkung pada batang
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false } // Sembunyikan legenda (tidak perlu)
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 } // Tampilkan angka bulat saja
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>