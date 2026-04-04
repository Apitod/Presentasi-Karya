<!-- Logo / Nama Aplikasi (Hanya di Desktop) -->
<a href="dashboard.php" class="d-none d-lg-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
    <i class="bi bi-building-check me-2" style="font-size: 1.5rem; color: #4e9af1;"></i>
    <span class="fs-6 fw-bold">Panel Admin</span>
</a>

<!-- Nama admin yang sedang login -->
<div class="mb-3 ps-1">
    <small class="text-white-50 d-block">Selamat datang,</small>
    <div class="fw-bold text-white">
        <i class="bi bi-person-circle me-1"></i>
        <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
    </div>
</div>

<hr style="border-color: rgba(255,255,255,0.1);">

<!-- Menu navigasi -->
<ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item mb-1">
        <a href="dashboard.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'background: #4e9af1;' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item mb-1">
        <a href="kelola_stok.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_stok.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'kelola_stok.php' ? 'background: #4e9af1;' : ''; ?>">
            <i class="bi bi-boxes me-2"></i> Kelola Stok
        </a>
    </li>
    <li class="nav-item mb-1">
        <a href="kelola_agen.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_agen.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'kelola_agen.php' ? 'background: #4e9af1;' : ''; ?>">
            <i class="bi bi-people me-2"></i> Kelola Agen
        </a>
    </li>
    <li class="nav-item mb-1">
        <a href="transaksi.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'background: #4e9af1;' : ''; ?>">
            <i class="bi bi-receipt-cutoff me-2"></i> Transaksi
        </a>
    </li>
</ul>

<hr style="border-color: rgba(255,255,255,0.1);">

<!-- Tombol Keluar / Logout -->
<div class="mt-auto">
    <a href="../logout.php" class="btn btn-sm w-100 text-start"
        style="background: rgba(255,59,59,0.2); color: #ff6b6b; border: 1px solid rgba(255,59,59,0.3);"
        onclick="return confirm('Yakin ingin keluar?')">
        <i class="bi bi-box-arrow-left me-1"></i> Keluar Aplikasi
    </a>
</div>
