<?php
// ============================================================
// FILE: admin/dashboard.php
// FUNGSI: Dashboard Utama Admin - Modern Scholarly Overhaul
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

// Stats Logic
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));
$total_agen = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'agen'"));
$transaksi_approved = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM transaksi WHERE status = 'approved'"));
$request_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM request_stok WHERE status = 'pending'"));

// Chart Data (Top Agents)
$query_kinerja = "
    SELECT u.nama_lengkap, SUM(t.total_harga) AS total_pendapatan
    FROM users u
    JOIN transaksi t ON u.id = t.agen_id AND t.status = 'approved'
    WHERE u.role = 'agen'
    GROUP BY u.id
    ORDER BY total_pendapatan DESC
    LIMIT 4
";
$top_agents = mysqli_query($koneksi, $query_kinerja);

// Recent Activity (Mixed)
$activities = mysqli_query($koneksi, "
    (SELECT 'transaksi' as type, u.nama_lengkap, t.created_at, t.total_harga as info, t.status FROM transaksi t JOIN users u ON t.agen_id = u.id)
    UNION
    (SELECT 'stok' as type, u.nama_lengkap, r.created_at, r.jumlah as info, r.status FROM request_stok r JOIN users u ON r.agen_id = u.id)
    ORDER BY created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Scholarly Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .activity-item {
            position: relative;
            padding-left: 32px;
            padding-bottom: 24px;
            border-left: 2px solid var(--border-color);
        }
        .activity-item:last-child {
            padding-bottom: 0;
            border-left-color: transparent;
        }
        .activity-icon {
            position: absolute;
            left: -11px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: var(--primary);
            z-index: 1;
        }
        .progress {
            height: 8px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1">
            <!-- Topbar -->
            <header class="top-nav justify-content-between">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search data points, agents, or stock...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-muted position-relative p-1">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    </button>
                    <button class="btn btn-link text-muted p-1">
                        <i class="bi bi-gear fs-5"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2">
                        <div class="text-end d-none d-md-block">
                            <div class="fw-bold small lh-1"><?php echo $_SESSION['nama_lengkap']; ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Head Administrator</div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama_lengkap']); ?>&background=0061f2&color=fff" class="rounded-circle" width="36" height="36">
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start mb-5">
                    <div>
                        <h1 class="page-title">Dashboard Overview</h1>
                        <p class="text-subtitle mb-0">Aggregated insights across the scholarly ecosystem.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light bg-white border shadow-sm">Export Report</button>
                        <button class="btn btn-primary d-flex align-items-center gap-2">
                             Refresh Data
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-box"></i>
                                </div>
                                <span class="badge bg-success-subtle text-success">+12.5%</span>
                            </div>
                            <div class="text-muted small fw-bold text-uppercase ls-wide" style="font-size: 0.65rem;">Total Products</div>
                            <h2 class="fw-800 mb-0"><?php echo number_format($produk['stok'] ?? 0); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-info-subtle text-info">
                                    <i class="bi bi-people"></i>
                                </div>
                                <span class="badge bg-success-subtle text-success">+4 new</span>
                            </div>
                            <div class="text-muted small fw-bold text-uppercase ls-wide" style="font-size: 0.65rem;">Total Agents</div>
                            <h2 class="fw-800 mb-0"><?php echo $total_agen; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-success-subtle text-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <span class="text-muted small">Last 30 days</span>
                            </div>
                            <div class="text-muted small fw-bold text-uppercase ls-wide" style="font-size: 0.65rem;">Approved Sales</div>
                            <h2 class="fw-800 mb-0"><?php echo number_format($transaksi_approved); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white shadow-primary">
                            <div class="d-flex justify-content-between mb-3 text-white">
                                <div class="stat-icon bg-white bg-opacity-20">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <i class="bi bi-arrow-right fs-5"></i>
                            </div>
                            <div class="text-white text-opacity-75 small fw-bold text-uppercase ls-wide" style="font-size: 0.65rem;">Pending Requests</div>
                            <h2 class="fw-800 mb-0"><?php echo $request_pending; ?></h2>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <!-- Top Performing Agents -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Top Performing Agents</span>
                                <a href="kelola_agen.php" class="text-primary text-decoration-none small fw-bold">View All</a>
                            </div>
                            <div class="card-body p-4">
                                <?php while($agent = mysqli_fetch_assoc($top_agents)): ?>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($agent['nama_lengkap']); ?>&background=f8f9fc&color=0061f2" class="rounded-pill" width="32" height="32">
                                            <span class="fw-600 small"><?php echo $agent['nama_lengkap']; ?></span>
                                        </div>
                                        <span class="fw-bold small text-dark">Rp <?php echo number_format($agent['total_pendapatan'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: <?php echo rand(40, 95); ?>%"></div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">Recent Activity</div>
                            <div class="card-body p-4">
                                <?php while($act = mysqli_fetch_assoc($activities)): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi <?php echo $act['type'] == 'transaksi' ? 'bi-receipt' : 'bi-box'; ?>"></i>
                                    </div>
                                    <div class="fw-bold small mb-1">
                                        <?php if($act['type'] == 'transaksi'): ?>
                                            New Transaction <span class="badge <?php echo $act['status'] == 'approved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?> p-1 ms-1"><?php echo $act['status']; ?></span>
                                        <?php else: ?>
                                            Stock Request <span class="badge bg-primary-subtle text-primary p-1 ms-1"><?php echo $act['status']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-muted small mb-1">
                                        <?php echo $act['nama_lengkap']; ?> 
                                        <?php echo $act['type'] == 'transaksi' ? 'recorded Rp '.number_format($act['info']) : 'requested '.$act['info'].' units'; ?>
                                    </p>
                                    <div class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d, H:i', strtotime($act['created_at'])); ?></div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Monitoring Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Inventory Monitoring</span>
                        <select class="form-select form-select-sm w-auto border-0 shadow-none bg-light fw-bold text-muted">
                            <option>All Categories</option>
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Stock Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Academic Presentation Bundle</strong></td>
                                        <td class="text-muted">Print Media</td>
                                        <td class="fw-600"><?php echo number_format($produk['stok'] ?? 0); ?> units</td>
                                        <td><span class="badge bg-success-subtle text-success">IN STOCK</span></td>
                                    </tr>
                                </tbody>
                            </table>
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