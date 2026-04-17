<?php
// ============================================================
// FILE: admin/transaksi.php
// FUNGSI: Audit Transaksi Penjualan - Panel Admin
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$pesan = '';

// Proses Setujui Transaksi
if (isset($_GET['approve']) && $is_admin) {
    $trx_id = (int) $_GET['approve'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT t.*, u.tl_id AS tl_id_agen FROM transaksi t JOIN users u ON t.agen_id = u.id WHERE t.id = $trx_id AND t.status = 'pending'"));

    if ($trx) {
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'approved' WHERE id = $trx_id");
        if (!empty($trx['tl_id_agen'])) {
            $tl_id = (int) $trx['tl_id_agen'];
            mysqli_query($koneksi, "UPDATE users SET poin = poin + 10 WHERE id = $tl_id AND role = 'tl'");
        }
        $pesan = ['type' => 'success', 'text' => 'Transaksi telah disetujui. Poin TL berhasil ditambahkan.'];
    }
}

// Proses Tolak Transaksi
if (isset($_GET['reject']) && $is_admin) {
    $trx_id = (int) $_GET['reject'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id = $trx_id AND status = 'pending'"));
    if ($trx) {
        $agen_id = $trx['agen_id'];
        $jumlah  = $trx['jumlah'];
        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'rejected' WHERE id = $trx_id");
        $pesan = ['type' => 'warning', 'text' => 'Transaksi ditolak. Stok telah dikembalikan ke agen.'];
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where  = '';
if ($filter == 'pending')  $where = "WHERE t.status = 'pending'";
if ($filter == 'approved') $where = "WHERE t.status = 'approved'";
if ($filter == 'rejected') $where = "WHERE t.status = 'rejected'";

$transaksi_list = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap AS nama_agen, tl.nama_lengkap AS nama_tl 
    FROM transaksi t
    JOIN users u ON t.agen_id = u.id
    LEFT JOIN users tl ON u.tl_id = tl.id
    $where
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Transaksi | Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            body * { visibility: hidden; }
            #main-wrapper { display: block !important; }
            .flex-grow-1, .flex-grow-1 * { visibility: visible; }
            .nav, .btn, .aksi-kolom, aside, .alert { display: none !important; }
            .flex-grow-1 { position: absolute; left: 0; top: 0; width: 100%; padding: 0 !important; }
            .table { width: 100% !important; border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Audit Transaksi</h3>
                    <p class="text-muted small mb-0">Tinjau dan proses verifikasi penjualan dari agen.</p>
                </div>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> py-2 small shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> <?php echo $pesan['text']; ?>
                </div>
            <?php endif; ?>

            <ul class="nav nav-pills mb-4 gap-2">
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'semua' ? 'active' : 'bg-white border text-dark'; ?>" href="?filter=semua">SEMUA</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'pending' ? 'active' : 'bg-white border text-dark'; ?>" href="?filter=pending">PENDING</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'approved' ? 'active' : 'bg-white border text-dark'; ?>" href="?filter=approved">DISETUJUI</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'rejected' ? 'active' : 'bg-white border text-dark'; ?>" href="?filter=rejected">DITOLAK</a>
                </li>
            </ul>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No Ref</th>
                                    <th>Agen</th>
                                    <th>Team Leader</th>
                                    <th>Pembeli</th>
                                    <th>Jumlah</th>
                                    <th>Total Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($transaksi_list)): ?>
                                <tr>
                                    <td class="ps-4 small text-muted">TRX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo $row['nama_agen']; ?></strong></td>
                                    <td><span class="small text-muted"><?php echo $row['nama_tl'] ?: '-'; ?></span></td>
                                    <td><?php echo $row['nama_pembeli']; ?></td>
                                    <td class="fw-bold"><?php echo $row['jumlah']; ?></td>
                                    <td class="text-primary fw-bold">Rp <?php echo number_format($row['total_harga']); ?></td>
                                    <td class="aksi-kolom">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="?approve=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="text-success me-2" title="Setujui"><i class="bi bi-check-circle-fill fs-5"></i></a>
                                            <a href="?reject=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="text-danger" title="Tolak"><i class="bi bi-x-circle-fill fs-5"></i></a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border small"><?php echo strtoupper($row['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($transaksi_list) == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted small">Tidak ada data transaksi.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>