<?php
// ============================================================
// FILE: admin/transaksi.php
// FUNGSI: Corporate Transaction Audit - Scholarly style
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$pesan = '';

// Approve logic
if (isset($_GET['approve']) && $is_admin) {
    $trx_id = (int) $_GET['approve'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT t.*, u.tl_id AS tl_id_agen FROM transaksi t JOIN users u ON t.agen_id = u.id WHERE t.id = $trx_id AND t.status = 'pending'"));

    if ($trx) {
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'approved' WHERE id = $trx_id");
        if (!empty($trx['tl_id_agen'])) {
            $tl_id = (int) $trx['tl_id_agen'];
            mysqli_query($koneksi, "UPDATE users SET poin = poin + 10 WHERE id = $tl_id AND role = 'tl'");
        }
        $pesan = ['type' => 'success', 'text' => 'Transaction audited and approved. TL rewards distributed.'];
    }
}

// Reject logic
if (isset($_GET['reject']) && $is_admin) {
    $trx_id = (int) $_GET['reject'];
    $trx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id = $trx_id AND status = 'pending'"));
    if ($trx) {
        $agen_id = $trx['agen_id'];
        $jumlah  = $trx['jumlah'];
        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'rejected' WHERE id = $trx_id");
        $pesan = ['type' => 'warning', 'text' => 'Transaction rejected. Inventory returned to agent.'];
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Scholarly Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            body * { visibility: hidden; }
            #main-wrapper { display: block !important; }
            .flex-grow-1, .flex-grow-1 * { visibility: visible; }
            header, .nav-pills, .btn-print, .aksi-kolom, aside { display: none !important; }
            .flex-grow-1 { position: absolute; left: 0; top: 0; width: 100%; padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #eee !important; }
        }
    </style>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1">
            <header class="top-nav justify-content-between">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search audits...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-muted p-1"><i class="bi bi-bell fs-5"></i></button>
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2 text-end">
                         <div class="fw-bold small lh-1"><?php echo $_SESSION['nama_lengkap']; ?></div>
                         <div class="text-muted" style="font-size: 0.7rem;">Auditor General</div>
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-end mb-5">
                    <div>
                        <h1 class="page-title">Transaction Audit</h1>
                        <p class="text-subtitle mb-0">Reviewing scholarly trade logs and administrative approvals.</p>
                    </div>
                    <button onclick="window.print()" class="btn btn-light border bg-white shadow-sm fw-bold btn-print">
                        <i class="bi bi-printer me-2"></i> EXPORT TO PDF
                    </button>
                </div>

                <?php if ($pesan): ?>
                    <div class="alert alert-<?php echo $pesan['type']; ?> alert-modern mb-4 shadow-sm">
                        <i class="bi bi-info-circle-fill me-2"></i> <?php echo $pesan['text']; ?>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 shadow-sm w-fit-content" style="width: fit-content;">
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 <?php echo $filter == 'semua' ? 'active' : ''; ?>" href="?filter=semua">ALL LOGS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 <?php echo $filter == 'pending' ? 'active' : ''; ?>" href="?filter=pending">PENDING AUDIT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 <?php echo $filter == 'approved' ? 'active' : ''; ?>" href="?filter=approved">APPROVED</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 <?php echo $filter == 'rejected' ? 'active' : ''; ?>" href="?filter=rejected">RETRACTED</a>
                    </li>
                </ul>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Reference</th>
                                        <th>Agent / Staff</th>
                                        <th>Team Leader</th>
                                        <th>Client Entity</th>
                                        <th>Qty</th>
                                        <th>Valuation</th>
                                        <th>Verified Proof</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($transaksi_list)): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-muted small fw-bold">TX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></span></td>
                                        <td><strong><?php echo $row['nama_agen']; ?></strong></td>
                                        <td>
                                            <?php if($row['nama_tl']): ?>
                                                <span class="badge bg-primary-subtle text-primary"><?php echo $row['nama_tl']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Independent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['nama_pembeli']; ?></td>
                                        <td><span class="fw-bold"><?php echo $row['jumlah']; ?></span></td>
                                        <td class="text-primary fw-bold">Rp <?php echo number_format($row['total_harga']); ?></td>
                                        <td>
                                            <?php if ($row['bukti_transaksi']): ?>
                                                <a href="../uploads/<?php echo $row['bukti_transaksi']; ?>" target="_blank">
                                                    <img src="../uploads/<?php echo $row['bukti_transaksi']; ?>" class="rounded-3 border shadow-sm" style="width:40px;height:40px;object-fit:cover;">
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="aksi-kolom">
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <a href="?approve=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-link text-success p-0 me-2" title="Approve Transaction"><i class="bi bi-check-circle-fill fs-5"></i></a>
                                                <a href="?reject=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-link text-danger p-0" title="Reject Transaction"><i class="bi bi-x-circle-fill fs-5"></i></a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border"><?php echo strtoupper($row['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>