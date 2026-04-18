
<div class="px-4 py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="bg-primary text-white p-2 rounded-3">
            <i class="bi bi-person-workspace fs-5"></i>
        </div>
        <div class="lh-1">
            <div class="fw-bold text-dark">Panel</div>
            <div class="text-muted small">Admin</div>
        </div>
    </div>

    <ul class="nav flex-column gap-1">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-grid-fill me-2"></i> Dasbor
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola_tl.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_tl.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-person-badge-fill me-2"></i> Kelola Team Leader
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola_agen.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_agen.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-people-fill me-2"></i> Kelola Agen
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola_stok.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_stok.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-box-seam-fill me-2"></i> Kelola Stok
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-receipt-cutoff me-2"></i> Audit Transaksi
            </a>
        </li>
        <li class="nav-item">
            <a href="riwayat.php" class="nav-link py-2 px-3 rounded-2 <?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active bg-primary text-white fw-bold shadow-sm' : 'text-secondary'; ?>">
                <i class="bi bi-clock-history me-2"></i> Riwayat Global
            </a>
        </li>
    </ul>

    <hr class="my-4 opacity-10">
    
    <div class="nav flex-column gap-1">
        <a href="../logout.php" class="nav-link py-2 px-3 text-danger fw-bold" onclick="return confirm('Keluar dari sistem?')">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>
    </div>
</div>
