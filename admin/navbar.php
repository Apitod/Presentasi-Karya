<?php
// ============================================================
// FILE: admin/navbar.php
// FUNGSI: Komponen navigasi (sidebar) yang dipakai bersama
// di semua halaman admin. Di-include menggunakan require_once.
// ============================================================
?>
<!-- Navbar Top (Mobile) -->
<nav class="navbar navbar-expand-lg navbar-dark d-lg-none py-3" style="background: #1a1a2e; border-bottom: 1px solid rgba(255,255,255,0.1);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-building-check me-2" style="color: #4e9af1;"></i> Panel Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Sidebar Desktop -->
<aside class="d-none d-lg-flex flex-column flex-shrink-0 p-3 text-white"
    style="width: 260px; min-height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: sticky; top: 0;">
    <?php include 'navbar_content.php'; ?>
</aside>

<!-- Sidebar Mobile (Offcanvas) -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" style="background: #16213e; width: 260px;">
    <div class="offcanvas-header bg-dark">
        <h5 class="offcanvas-title fw-bold">
            <i class="bi bi-building-check me-2" style="color: #4e9af1;"></i> Panel Admin
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <?php include 'navbar_content.php'; ?>
    </div>
</div>
