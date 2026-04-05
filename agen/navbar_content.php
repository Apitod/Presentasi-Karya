<!-- Logo / Nama Aplikasi (Desktop Only) -->
<a href="dashboard.php" class="d-none d-lg-flex align-items-center mb-3 text-white text-decoration-none">
    <i class="bi bi-person-badge me-2" style="font-size: 1.5rem; color: #4fd1c5;"></i>
    <span class="fs-6 fw-bold">Portal Agen</span>
</a>

<!-- Nama agen yang sedang login -->
<div class="mb-3 ps-1">
    <small class="text-white-50 d-block">Selamat datang,</small>
    <div class="fw-bold text-white">
        <i class="bi bi-person-circle me-1"></i>
        <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
    </div>
</div>

<hr style="border-color: rgba(255,255,255,0.1);">

<ul class="nav nav-pills flex-column mb-auto">
    <!-- Menu Dashboard -->
    <li class="nav-item mb-1">
        <a href="dashboard.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'background: #4fd1c5; color: #1a1a2e !important;' : ''; ?>">
            <i class="bi bi-house me-2"></i> Dashboard
        </a>
    </li>
    <!-- Menu Request Stok -->
    <li class="nav-item mb-1">
        <a href="request_stok.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'request_stok.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'request_stok.php' ? 'background: #4fd1c5; color: #1a1a2e !important;' : ''; ?>">
            <i class="bi bi-arrow-up-circle me-2"></i> Request Stok
        </a>
    </li>
    <!-- Menu Penjualan -->
    <li class="nav-item mb-1">
        <a href="penjualan.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'penjualan.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'penjualan.php' ? 'background: #4fd1c5; color: #1a1a2e !important;' : ''; ?>">
            <i class="bi bi-cart-plus me-2"></i> Penjualan
        </a>
    </li>
    <!-- Menu Riwayat Transaksi -->
    <li class="nav-item mb-1">
        <a href="riwayat.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'background: #4fd1c5; color: #1a1a2e !important;' : ''; ?>">
            <i class="bi bi-clock-history me-2"></i> Riwayat
        </a>
    </li>
    <!-- Menu Profil -->
    <li class="nav-item mb-1">
        <a href="profil.php"
            class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>"
            style="<?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'background: #4fd1c5; color: #1a1a2e !important;' : ''; ?>">
            <i class="bi bi-person-gear me-2"></i> Profil Saya
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
