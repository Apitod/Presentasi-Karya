<!-- ============================================================
     FILE: admin/navbar_content.php
     FUNGSI: Menu sidebar Modern - Scholarly Curator Style
     ============================================================ -->

<!-- Logo / Brand Section -->
<div class="px-3 mb-4 mt-3">
    <div class="d-flex align-items-center gap-2">
        <div class="brand-box bg-primary text-white d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px;">
            <i class="bi bi-book-half fs-5"></i>
        </div>
        <div>
            <div class="fw-bold text-dark lh-1" style="font-size: 1rem;">Editorial</div>
            <div class="fw-bold text-dark lh-1" style="font-size: 1rem;">Management</div>
            <div class="text-muted fw-bold" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Admin Portal</div>
        </div>
    </div>
</div>

<hr class="mx-3 opacity-10">

<!-- Menu Navigasi Utama -->
<ul class="nav flex-column mb-auto">

    <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-grid-fill"></i> DASHBOARD
        </a>
    </li>

    <li class="nav-item">
        <a href="kelola_tl.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_tl.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge-fill"></i> TEAM LEADERS
        </a>
    </li>

    <li class="nav-item">
        <a href="kelola_agen.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_agen.php' ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i> SALES TEAM
        </a>
    </li>

    <li class="nav-item">
        <a href="kelola_stok.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_stok.php' ? 'active' : ''; ?>">
            <i class="bi bi-box-seam-fill"></i> INVENTORY
        </a>
    </li>

    <li class="nav-item">
        <a href="transaksi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>">
            <i class="bi bi-receipt-cutoff"></i> TRANSACTIONS
        </a>
    </li>

    <li class="nav-item">
        <a href="riwayat.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart-fill"></i> REPORTS
        </a>
    </li>

</ul>

<hr class="mx-3 opacity-10 mt-4">

<ul class="nav flex-column">
    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="bi bi-question-circle-fill"></i> HELP CENTER
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="bi bi-person-fill-gear"></i> ACCOUNT SETTINGS
        </a>
    </li>
    <li class="nav-item mt-2 px-3">
        <a href="../logout.php" class="btn btn-light btn-sm w-100 text-danger fw-bold rounded-3 border py-2" onclick="return confirm('Exit session?')">
            <i class="bi bi-box-arrow-left me-2"></i> LOGOUT
        </a>
    </li>
</ul>
