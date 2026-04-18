<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$tl_id_session = (int) $_SESSION['user_id'];

// Ambil semua agen yang tl_id-nya sama dengan ID TL ini
// Beserta total penjualan masing-masing agen
$daftar_agen = mysqli_query($koneksi, "
    SELECT u.id, u.nama_lengkap, u.username, u.alamat, u.nik,
           COUNT(t.id) AS total_transaksi,
           SUM(t.total_harga) AS total_pendapatan
    FROM users u
    LEFT JOIN transaksi t ON u.id = t.agen_id AND t.status = 'approved'
    WHERE u.role = 'agen' AND u.tl_id = $tl_id_session
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agen Saya - Panel Team Leader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-4">
                <h3 class="page-title mb-1">
                    <i class="bi bi-people me-2 text-primary"></i> Tim Agen Saya
                </h3>
                <p class="text-muted small mb-0">
                    Daftar agen yang berada di bawah pengawasan Anda.
                    <span class="badge bg-info text-dark ms-2">Mode TL: Hanya Lihat</span>
                </p>
            </div>

            <!-- Tabel Daftar Agen -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header fw-semibold border-0 rounded-top-4" style="background:#1a1a2e; color:#fff;">
                    <i class="bi bi-table me-2"></i> Daftar Agen
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Alamat</th>
                                    <th class="text-center">Total Transaksi</th>
                                    <th class="text-end pe-4">Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($agen = mysqli_fetch_assoc($daftar_agen)):
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                        <td>
                                            <i class="bi bi-person-circle me-2 text-muted"></i>
                                            <strong><?php echo htmlspecialchars($agen['nama_lengkap']); ?></strong>
                                            <br>
                                            <small class="text-muted">NIK: <?php echo htmlspecialchars($agen['nik'] ?? '-'); ?></small>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($agen['username']); ?></code></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($agen['alamat'] ?? '-'); ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary rounded-pill">
                                                <?php echo $agen['total_transaksi']; ?> TRX
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold text-success pe-4">
                                            Rp <?php echo number_format($agen['total_pendapatan'] ?? 0, 0, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if (mysqli_num_rows($daftar_agen) == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-people-fill" style="font-size:2rem;opacity:0.4;"></i>
                                            <p class="mt-2 mb-0 small">Belum ada agen di bawah naungan Anda.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-info border-0 rounded-4 mt-4 shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Pengelolaan Akun Agen:</strong> Anda tidak dapat menambah atau mengubah data agen. Pengelolaan data agen, termasuk penugasan agen ke dalam tim, hanya dapat dilakukan oleh <strong>Admin Utama</strong>.
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
