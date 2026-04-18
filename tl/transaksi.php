<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_tl = ($_SESSION['role'] === 'tl');
$tl_id_session = $_SESSION['user_id'];
$pesan = '';

// Proses Setujui Transaksi (Forward to Admin)
if (isset($_GET['approve']) && $is_tl) {
    $trx_id = (int) $_GET['approve'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT t.* FROM transaksi t JOIN users u ON t.agen_id = u.id WHERE t.id = $trx_id AND t.status = 'pending_tl' AND u.tl_id = $tl_id_session"));

    if ($trx) {
        // Update ke pending_admin untuk persetujuan akhir
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'pending_admin' WHERE id = $trx_id");
        $pesan = ['type' => 'success', 'text' => 'Transaksi disetujui TL dan diteruskan ke Admin untuk finalisasi.'];
    }
}

// Proses Tolak Transaksi
if (isset($_GET['reject']) && $is_tl) {
    $trx_id = (int) $_GET['reject'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT t.* FROM transaksi t JOIN users u ON t.agen_id = u.id WHERE t.id = $trx_id AND t.status = 'pending_tl' AND u.tl_id = $tl_id_session"));
    
    if ($trx) {
        $agen_id = $trx['agen_id'];
        $jumlah  = $trx['jumlah'];
        // Kembalikan stok agen
        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
        // Tolak transaksi
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'rejected' WHERE id = $trx_id");
        $pesan = ['type' => 'warning', 'text' => 'Transaksi ditolak oleh TL. Stok dikembalikan ke agen.'];
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where  = "WHERE u.tl_id = $tl_id_session ";
if ($filter == 'pending_tl')  $where .= "AND t.status = 'pending_tl'";
elseif ($filter == 'pending_admin') $where .= "AND t.status = 'pending_admin'";
elseif ($filter == 'approved') $where .= "AND t.status = 'approved'";
elseif ($filter == 'rejected') $where .= "AND t.status = 'rejected'";

$transaksi_list = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap AS nama_agen
    FROM transaksi t
    JOIN users u ON t.agen_id = u.id
    $where
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Transaksi | Panel Team Leader</title>
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
                    <h3 class="fw-bold mb-1">Validasi Transaksi Agen</h3>
                    <p class="text-muted small mb-0">Tinjau dan proses verifikasi penjualan dari agen di bawah Anda.</p>
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
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'pending_tl' ? 'active' : 'bg-warning text-dark border'; ?>" href="?filter=pending_tl">PERLU DISAHKAN</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'pending_admin' ? 'active' : 'bg-info text-dark border'; ?>" href="?filter=pending_admin">PROSES ADMIN</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'approved' ? 'active' : 'bg-success text-white border-0'; ?>" href="?filter=approved">DISETUJUI TOTAL</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-sm py-1 <?php echo $filter == 'rejected' ? 'active' : 'bg-danger text-white border-0'; ?>" href="?filter=rejected">DITOLAK</a>
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
                                    <th>Pembeli</th>
                                    <th>Jumlah</th>
                                    <th>Total Nilai</th>
                                    <th>Aksi / Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($transaksi_list)): ?>
                                <tr>
                                    <td class="ps-4 small text-muted">TRX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo $row['nama_agen']; ?></strong></td>
                                    <td><?php echo $row['nama_pembeli']; ?></td>
                                    <td class="fw-bold"><?php echo $row['jumlah']; ?></td>
                                    <td class="text-primary fw-bold">Rp <?php echo number_format($row['total_harga']); ?></td>
                                    <td class="aksi-kolom">
                                        <?php if ($row['status'] == 'pending_tl'): ?>
                                            <a href="?approve=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="text-success me-2" title="Setujui dan teruskan ke Admin"><i class="bi bi-check-circle-fill fs-5"></i></a>
                                            <a href="?reject=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="text-danger" title="Tolak"><i class="bi bi-x-circle-fill fs-5"></i></a>
                                        <?php elseif ($row['status'] == 'pending_admin'): ?>
                                            <span class="badge bg-info text-dark border small">PROSES ADMIN</span>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <span class="badge bg-success border small">DISETUJUI</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger border small">DITOLAK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($transaksi_list) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted small">Tidak ada data transaksi dari agen.</td>
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