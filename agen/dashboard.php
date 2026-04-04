<?php
// ============================================================
// FILE: agen/dashboard.php
// FUNGSI: Halaman utama agen - ringkasan stok dan penjualan
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

// Ambil ID agen dari sesi yang tersimpan saat login
$agen_id = $_SESSION['user_id'];

// -----------------------------------------------------------
// Ambil data yang relevan untuk agen ini
// -----------------------------------------------------------

// 1. Ambil stok yang dimiliki agen ini
$stok_agen = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"
));

if ($stok_agen) {
    $stok_saya = $stok_agen['stok'];
} else {
    $stok_saya = 0;
}

// 2. Hitung transaksi berdasarkan status
$pending_count = mysqli_num_rows(mysqli_query(
    $koneksi,
    "SELECT id FROM transaksi WHERE agen_id = $agen_id AND status = 'pending'"
));
$approved_count = mysqli_num_rows(mysqli_query(
    $koneksi,
    "SELECT id FROM transaksi WHERE agen_id = $agen_id AND status = 'approved'"
));

// 3. Hitung total pendapatan dari transaksi yang sudah disetujui
$pendapatan_data = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT SUM(total_harga) AS total FROM transaksi WHERE agen_id = $agen_id AND status = 'approved'"
));
$total_pendapatan = $pendapatan_data['total'] ?? 0;

// 4. Ambil 5 transaksi terbaru agen ini
$transaksi_terbaru = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC LIMIT 5"
);

// 5. Ambil data produk untuk menampilkan harga
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agen - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .stat-card {
            border-left: 4px solid;
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .page-title {
            font-weight: 700;
            color: #0f3460;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header halaman -->
            <div class="mb-4">
                <h3 class="page-title"><i class="bi bi-house me-2" style="color:#4fd1c5;"></i> Dashboard Agen</h3>
                <p class="text-muted small mb-0">Selamat datang,
                    <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!
                </p>
            </div>

            <!-- Peringatan jika stok agen hampir habis -->
            <?php if ($stok_saya <= 5 && $stok_saya >= 0): ?>
                <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        Stok Anda hanya tersisa <strong>
                            <?php echo $stok_saya; ?> unit
                        </strong>!
                        <a href="request_stok.php" class="alert-link">Request stok sekarang &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Baris Kartu Statistik -->
            <div class="row g-3 mb-4">
                <!-- Kartu Stok Saya -->
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #4fd1c5;">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Stok Saya</p>
                            <h3 class="fw-bold mb-0" style="color:#0f3460;">
                                <?php echo $stok_saya; ?> unit
                            </h3>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <!-- Kartu Transaksi Menunggu -->
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #fd7e14;">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Transaksi Pending</p>
                            <h3 class="fw-bold mb-0" style="color:#0f3460;">
                                <?php echo $pending_count; ?>
                            </h3>
                            <small class="text-muted">Menunggu persetujuan admin</small>
                        </div>
                    </div>
                </div>
                <!-- Kartu Total Pendapatan -->
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #28a745;">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Pendapatan</p>
                            <h3 class="fw-bold mb-0" style="color:#0f3460;">
                                Rp
                                <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                            </h3>
                            <small class="text-muted">Dari
                                <?php echo $approved_count; ?> transaksi disetujui
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel 5 Transaksi Terbaru -->
            <div class="card shadow-sm border-0">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center"
                    style="background: #0f3460; color:#fff; border-radius: 10px 10px 0 0;">
                    <span><i class="bi bi-clock-history me-2"></i> Transaksi Terbaru</span>
                    <a href="riwayat.php" class="btn btn-sm btn-outline-light">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pembeli</th>
                                <th class="text-center">Jumlah</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Definisikan warna dan label status di luar loop agar lebih efisien
                            $badge_class = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                            $badge_label = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

                            while ($trx = mysqli_fetch_assoc($transaksi_terbaru)):
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($trx['nama_pembeli']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $trx['jumlah']; ?> unit
                                    </td>
                                    <td>Rp
                                        <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td><small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($trx['created_at'])); ?>
                                        </small></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $badge_class[$trx['status']]; ?>">
                                            <?php echo $badge_label[$trx['status']]; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($approved_count == 0 && $pending_count == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada transaksi. <a href="penjualan.php">Buat transaksi pertama &rarr;</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>