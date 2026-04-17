<?php
// ============================================================
// FILE: tl/dashboard.php
// FUNGSI: Dashboard Team Leader - Modern Academic Style
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$tl_id = (int) $_SESSION['user_id'];

// Stats
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));
$total_agen = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'agen' AND tl_id = $tl_id"));
$poin_saya = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT poin FROM users WHERE id = $tl_id"))['poin'];

// Recent Tim Transactions
$transactions = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap 
    FROM transaksi t 
    JOIN users u ON t.agen_id = u.id 
    WHERE u.tl_id = $tl_id 
    ORDER BY t.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TL Dashboard | Scholarly Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1">
            <!-- Topbar -->
            <header class="top-nav justify-content-between">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search team metrics, agents...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-muted p-1"><i class="bi bi-bell fs-5"></i></button>
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2">
                        <div class="text-end d-none d-md-block">
                            <div class="fw-bold small lh-1"><?php echo $_SESSION['nama_lengkap']; ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Senior Team Leader</div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama_lengkap']); ?>&background=0061f2&color=fff" class="rounded-circle" width="36" height="36">
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start mb-5">
                    <div>
                        <h1 class="page-title">Team Performance</h1>
                        <p class="text-subtitle mb-0">Tracking your team scholarly contributions and rewards.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light bg-white border shadow-sm px-4">Download Report</button>
                        <button class="btn btn-primary px-4 shadow-sm">Inventory Audit</button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card stat-card border-start border-primary border-4">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-primary-soft text-primary">
                                    <i class="bi bi-boxes"></i>
                                </div>
                                <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.6rem;">GLOBAL STOCK</span>
                            </div>
                            <h2 class="fw-800 mb-0"><?php echo number_format($produk['stok'] ?? 0); ?></h2>
                            <div class="text-muted small fw-bold text-uppercase mt-1" style="font-size: 0.65rem;">Remaining Products</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card border-start border-success border-4">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-success-subtle text-success">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>
                                <span class="badge bg-success-subtle text-success" style="font-size: 0.6rem;">▲ 12%</span>
                            </div>
                            <h2 class="fw-800 mb-0"><?php echo $total_agen; ?></h2>
                            <div class="text-muted small fw-bold text-uppercase mt-1" style="font-size: 0.65rem;">Active Team Members</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card border-start border-warning border-4">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <button class="btn btn-link p-0 text-muted"><i class="bi bi-info-circle"></i></button>
                            </div>
                            <h2 class="fw-800 mb-0"><?php echo number_format($poin_saya); ?> <span class="fs-4 fw-600 text-muted">PTS</span></h2>
                            <div class="text-muted small fw-bold text-uppercase mt-1" style="font-size: 0.65rem;">Personal Reward Points</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Team Transactions</span>
                                <a href="riwayat.php" class="text-primary text-decoration-none small fw-bold">View History</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Reference</th>
                                                <th>Agent Name</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($trx = mysqli_fetch_assoc($transactions)): ?>
                                            <tr>
                                                <td><span class="text-muted small fw-bold">TX-<?php echo str_pad($trx['id'], 5, '0', STR_PAD_LEFT); ?></span></td>
                                                <td><strong><?php echo $trx['nama_lengkap']; ?></strong></td>
                                                <td class="fw-bold">Rp <?php echo number_format($trx['total_harga']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $trx['status'] == 'approved' ? 'bg-success-subtle text-success' : ($trx['status'] == 'pending' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'); ?>">
                                                        <?php echo strtoupper($trx['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($trx['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card bg-primary text-white mb-4 overflow-hidden position-relative">
                            <div class="card-body p-4 position-relative" style="z-index: 2;">
                                <div class="small fw-bold text-uppercase mb-2 text-white-50">Quarterly Bonus</div>
                                <h4 class="fw-800 mb-3">You're only 12 sales away from Platinum status.</h4>
                                <div class="progress mb-2 bg-white bg-opacity-20" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 85%"></div>
                                </div>
                                <div class="d-flex justify-content-between small fw-bold">
                                    <span>GOLD</span>
                                    <span>PLATINUM</span>
                                </div>
                            </div>
                            <div class="position-absolute bottom-0 end-0 opacity-10 mb-n4 me-n4">
                                <i class="bi bi-award-fill" style="font-size: 10rem;"></i>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">Inventory Alert</div>
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="bg-danger-subtle text-danger p-2 rounded-3 me-3">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small">Stok Critical</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Stok global produk sisa <?php echo $produk['stok']; ?> units.</div>
                                    </div>
                                    <span class="badge bg-danger">LOW</span>
                                </div>
                                <button class="btn btn-light w-100 fw-bold small py-2 mt-2">REQUEST RESTOCK</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="p-4 p-lg-5 text-center mt-auto border-top bg-white">
                <div class="text-muted small ls-wide">© <?php echo date('Y'); ?> Scholarly Curator Systems. All rights reserved.</div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
