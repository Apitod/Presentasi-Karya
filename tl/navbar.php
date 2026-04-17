<?php
// ============================================================
// FILE: admin/navbar.php
// FUNGSI: Sidebar container for Admin
// ============================================================
?>
<!-- Sidebar Desktop -->
<aside class="d-none d-lg-flex flex-column flex-shrink-0 text-white" 
    style="width: 280px; min-height: 100vh; position: sticky; top: 0;">
    <?php include 'navbar_content.php'; ?>
</aside>

<!-- Mobile Toggle Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white d-lg-none border-bottom py-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-book-half text-primary me-2"></i> Editorial Mgmt
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Sidebar Mobile (Offcanvas) -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include 'navbar_content.php'; ?>
    </div>
</div>
