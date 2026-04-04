<?php
// ============================================================
// FILE: agen/navbar.php
// FUNGSI: Komponen sidebar navigasi untuk halaman agen
// ============================================================
?>
<!-- Navbar Top (Mobile) -->
<nav class="navbar navbar-expand-lg navbar-dark d-lg-none py-3" style="background: #0f3460; border-bottom: 1px solid rgba(255,255,255,0.1);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-person-badge me-2" style="color: #4fd1c5;"></i> Portal Agen
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Sidebar Desktop -->
<aside class="d-none d-lg-flex flex-column flex-shrink-0 p-3 text-white"
    style="width: 240px; min-height: 100vh; background: linear-gradient(180deg, #0f3460 0%, #16213e 100%); position: sticky; top: 0;">
    <?php include 'navbar_content.php'; ?>
</aside>

<!-- Sidebar Mobile (Offcanvas) -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" style="background: #16213e; width: 240px;">
    <div class="offcanvas-header bg-dark">
        <h5 class="offcanvas-title fw-bold">
            <i class="bi bi-person-badge me-2" style="color: #4fd1c5;"></i> Portal Agen
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <?php include 'navbar_content.php'; ?>
    </div>
</div>

<style>
    .nav-link {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    @media (max-width: 991.98px) {
        #main-wrapper {
            flex-direction: column;
        }
        .flex-grow-1 {
            width: 100%;
            padding: 1.5rem !important;
        }
    }
</style>