<?php
// ============================================================
// FILE: admin/kelola_agen.php
// FUNGSI: Modern Manage Agents - Scholarly curator style
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$pesan = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_agent']) && $is_admin) {
    $nama     = trim($_POST['nama_lengkap']);
    $alamat   = trim($_POST['alamat']);
    $nik      = trim($_POST['nik']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $tl_id    = (int) $_POST['tl_id'];

    if (empty($nama) || empty($username) || empty($password) || empty($nik)) {
        $pesan = ['type' => 'danger', 'text' => 'All required fields must be filled!'];
    } else {
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_aman'"));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Username '$username' is already taken!"];
        } else {
            $password_hash = md5($password);
            $nama_aman     = mysqli_real_escape_string($koneksi, $nama);
            $alamat_aman   = mysqli_real_escape_string($koneksi, $alamat);
            $nik_aman      = mysqli_real_escape_string($koneksi, $nik);
            $tl_sql = ($tl_id > 0) ? $tl_id : 'NULL';

            $query = "INSERT INTO users (nama_lengkap, username, password, role, tl_id, alamat, nik) 
                      VALUES ('$nama_aman', '$username_aman', '$password_hash', 'agen', $tl_sql, '$alamat_aman', '$nik_aman')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Agent '$nama' has been successfully onboarded!"];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Error onboarding agent: ' . mysqli_error($koneksi)];
            }
        }
    }
}

if (isset($_GET['delete']) && $is_admin) {
    $hapus_id = (int) $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $hapus_id AND role = 'agen'");
    $pesan = ['type' => 'warning', 'text' => 'Agent access has been revoked.'];
}

$daftar_tl = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users WHERE role = 'tl' ORDER BY nama_lengkap ASC");

if ($is_admin) {
    $where_agen = "WHERE u.role = 'agen'";
} else {
    $tl_session_id = (int) $_SESSION['user_id'];
    $where_agen    = "WHERE u.role = 'agen' AND u.tl_id = $tl_session_id";
}

$daftar_agen = mysqli_query($koneksi, "
    SELECT u.*, tl.nama_lengkap AS nama_tl 
    FROM users u
    LEFT JOIN users tl ON u.tl_id = tl.id
    $where_agen
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents | Scholarly Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1">
            <header class="top-nav justify-content-between">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search agents...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-muted p-1"><i class="bi bi-bell fs-5"></i></button>
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama_lengkap']); ?>&background=0061f2&color=fff" class="rounded-circle" width="36" height="36">
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start mb-5">
                    <div>
                        <h1 class="page-title">Manage Agents</h1>
                        <p class="text-subtitle mb-0">Configure editorial staff access and identity profiles.</p>
                    </div>
                    <?php if ($is_admin): ?>
                    <button class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm" onclick="document.getElementById('nama_lengkap').focus();">
                        <i class="bi bi-person-plus-fill"></i> Add New Agent
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($pesan): ?>
                    <div class="alert alert-<?php echo $pesan['type']; ?> alert-modern mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i> <?php echo $pesan['text']; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Form Column -->
                    <?php if ($is_admin): ?>
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center gap-2">
                                <i class="bi bi-text-paragraph text-primary"></i> Agent Details
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" placeholder="e.g. Dr. Julian Thorne" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">NIK (Employee ID)</label>
                                        <input type="text" class="form-control" name="nik" placeholder="16-digit identification number" required maxlength="16">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Username</label>
                                        <div class="input-modern position-relative">
                                            <span class="position-absolute start-0 top-50 translate-middle-y ps-3 text-muted">@</span>
                                            <input type="text" class="form-control ps-5" name="username" placeholder="username" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Team Leader</label>
                                        <select class="form-select border-0 bg-light p-3 rounded-3" name="tl_id">
                                            <option value="0">Unassigned (Independent)</option>
                                            <?php while($tl = mysqli_fetch_assoc($daftar_tl)): ?>
                                                <option value="<?php echo $tl['id']; ?>"><?php echo $tl['nama_lengkap']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="alamat" rows="3" placeholder="Residential or Office address"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required placeholder="Security key">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="save_agent" class="btn btn-primary flex-grow-1">SAVE AGENT</button>
                                        <button type="reset" class="btn btn-light border fw-bold text-muted">CLEAR</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Table Column -->
                    <div class="<?php echo $is_admin ? 'col-lg-8' : 'col-12'; ?>">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Active Agents Directory</span>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-link text-muted p-0"><i class="bi bi-filter-right fs-5"></i></button>
                                    <button class="btn btn-link text-muted p-0"><i class="bi bi-download fs-5"></i></button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>NIK</th>
                                                <th>Username</th>
                                                <th>Team Leader</th>
                                                <?php if($is_admin): ?><th>Action</th><?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($agen = mysqli_fetch_assoc($daftar_agen)): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-light fw-bold text-primary small" style="width:32px; height:32px;">
                                                            <?php echo substr($agen['nama_lengkap'], 0, 1); ?>
                                                        </div>
                                                        <div class="fw-bold"><?php echo $agen['nama_lengkap']; ?></div>
                                                    </div>
                                                </td>
                                                <td class="text-muted small"><?php echo $agen['nik']; ?></td>
                                                <td><span class="text-primary small fw-600">@<?php echo $agen['username']; ?></span></td>
                                                <td>
                                                    <?php if($agen['nama_tl']): ?>
                                                        <span class="badge bg-primary-subtle text-primary"><?php echo $agen['nama_tl']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted italic small">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if($is_admin): ?>
                                                <td>
                                                    <a href="edit_agen.php?id=<?php echo $agen['id']; ?>" class="btn btn-link text-muted p-0 me-2"><i class="bi bi-pencil-square"></i></a>
                                                    <a href="?delete=<?php echo $agen['id']; ?>" class="btn btn-link text-danger p-0" onclick="return confirm('Revoke agent access?')"><i class="bi bi-trash3"></i></a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-bold">SHOWING <?php echo mysqli_num_rows($daftar_agen); ?> AGENTS</span>
                                <nav>
                                    <ul class="pagination pagination-sm m-0">
                                        <li class="page-item disabled"><a class="page-link border-0 bg-light rounded-pill px-3 mx-1" href="#">Prev</a></li>
                                        <li class="page-item active"><a class="page-link border-0 rounded-pill px-3 mx-1" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link border-0 bg-light rounded-pill px-3 mx-1" href="#">Next</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- Audit Reports Card (Ref Image 2 bottom) -->
                        <div class="card bg-primary text-white mt-4 overflow-hidden">
                             <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="fw-800 mb-1">Audit Reports</h4>
                                    <p class="text-white text-opacity-75 small mb-0">Review agent activity logs and editorial performance metrics for the current quarter.</p>
                                </div>
                                <button class="btn btn-light fw-bold px-4">View Logs</button>
                             </div>
                             <div class="position-absolute end-0 bottom-0 opacity-10 mb-n4 me-n4">
                                <i class="bi bi-shield-check" style="font-size: 8rem;"></i>
                             </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>