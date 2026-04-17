<?php
// ============================================================
// FILE: agen/dashboard.php
// FUNGSI: Halaman utama agen - ringkasan stok dan penjualan.
//
// PEMBARUAN v2: UI dioverhaul ke clean corporate Bootstrap 5.
// Tambah info nama TL (atasan) agen jika ada.
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id']; // Ambil ID agen dari sesi login

// -----------------------------------------------------------
// Ambil semua data yang relevan untuk agen ini
// -----------------------------------------------------------

// 1. Stok yang dimiliki agen ini saat ini
$stok_agen = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"
));
$stok_saya = $stok_agen ? $stok_agen['stok'] : 0;

// 2. Hitung transaksi berdasarkan statusnya
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

// 5. Ambil data produk untuk menampilkan nama produk
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

// 6. BARU: Cek apakah agen ini punya TL (atasan)
// Ambil nama TL dari tabel users berdasarkan tl_id agen
$info_tl = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT tl.nama_lengkap AS nama_tl
     FROM users agen
     LEFT JOIN users tl ON agen.tl_id = tl.id
     WHERE agen.id = $agen_id"
));
$nama_tl = $info_tl['nama_tl'] ?? null; // NULL jika agen tidak punya TL
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agen - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header Halaman -->
            <div class="mb-4">
                <h3 class="page-title">
                    <i class="bi bi-house me-2" style="color:#4fd1c5;"></i> Dashboard Agen
                </h3>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <p class="text-muted small mb-0">
                        Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>!
                    </p>
                    <!-- Tampilkan info TL jika agen ini punya atasan TL -->
                    <?php if ($nama_tl): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                            <i class="bi bi-person-badge me-1"></i> TL: <?php echo htmlspecialchars($nama_tl); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Peringatan jika stok agen hampir habis -->
            <?php if ($stok_saya <= 5 && $stok_saya >= 0): ?>
                <div class="alert alert-warning d-flex align-items-center mb-4 shadow-sm border-0 rounded-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
                    <div>
                        Stok Anda hanya tersisa <strong><?php echo $stok_saya; ?> unit</strong>!
                        <a href="request_stok.php" class="alert-link ms-2">Request stok sekarang &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Baris Kartu Statistik -->
            <div class="row g-3 mb-4">
                <!-- Kartu: Stok Saya -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100" style="border-left: 4px solid #4fd1c5 !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Stok Saya</p>
                                    <h3 class="fw-bold mb-0" style="color:#0f3460;"><?php echo $stok_saya; ?> unit</h3>
                                    <small class="text-muted"><?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk'); ?></small>
                                </div>
                                <div class="stat-icon" style="background:#e0faf7;">
                                    <i class="bi bi-box-seam" style="color:#4fd1c5; font-size:1.4rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu: Transaksi Menunggu -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100" style="border-left: 4px solid #fd7e14 !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Transaksi Pending</p>
                                    <h3 class="fw-bold mb-0" style="color:#0f3460;"><?php echo $pending_count; ?></h3>
                                    <small class="text-muted">Menunggu persetujuan admin</small>
                                </div>
                                <div class="stat-icon" style="background:#fff5e8;">
                                    <i class="bi bi-hourglass-split" style="color:#fd7e14; font-size:1.4rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu: Total Pendapatan -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100" style="border-left: 4px solid #28a745 !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Total Pendapatan</p>
                                    <h3 class="fw-bold mb-0" style="color:#0f3460;">
                                        Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                                    </h3>
                                    <small class="text-muted">Dari <?php echo $approved_count; ?> transaksi disetujui</small>
                                </div>
                                <div class="stat-icon" style="background:#e8fff0;">
                                    <i class="bi bi-currency-dollar" style="color:#28a745; font-size:1.4rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel 5 Transaksi Terbaru -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header fw-semibold border-0 rounded-top-4 d-flex justify-content-between align-items-center"
                    style="background: #1a1a2e; color:#fff;">
                    <span><i class="bi bi-clock-history me-2"></i> Transaksi Terbaru</span>
                    <a href="riwayat.php" class="btn btn-sm btn-outline-light rounded-3">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Pembeli</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                    <th class="text-center pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Definisikan warna dan teks badge status di luar loop
                                $badge_class = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                $badge_label = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

                                while ($trx = mysqli_fetch_assoc($transaksi_terbaru)):
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php echo htmlspecialchars($trx['nama_pembeli']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary rounded-pill"><?php echo $trx['jumlah']; ?> unit</span>
                                        </td>
                                        <td class="fw-semibold text-success">
                                            Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($trx['created_at'])); ?></small>
                                        </td>
                                        <td class="text-center pe-4">
                                            <span class="badge rounded-pill bg-<?php echo $badge_class[$trx['status']]; ?>">
                                                <?php echo $badge_label[$trx['status']]; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if ($approved_count == 0 && $pending_count == 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-receipt" style="font-size:2rem;opacity:0.3;"></i>
                                            <p class="mt-3 mb-0 small">Belum ada transaksi. <a href="penjualan.php">Buat transaksi pertama &rarr;</a></p>
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