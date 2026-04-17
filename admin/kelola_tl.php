<?php
// ============================================================
// FILE: admin/kelola_tl.php
// FUNGSI: Manage Team Leaders - Modern Scholarly Overhaul
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_tl'])) {
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($username) || empty($password)) {
        $pesan = ['type' => 'danger', 'text' => 'All employee details must be provided.'];
    } else {
        $username_aman = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_aman'"));

        if ($cek) {
            $pesan = ['type' => 'danger', 'text' => "Credential prefix '@$username' is already assigned."];
        } else {
            $password_hash = md5($password);
            $nama_aman     = mysqli_real_escape_string($koneksi, $nama);

            $query = "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama_aman', '$username_aman', '$password_hash', 'tl')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Team Leader '$nama' has been successfully registered."];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Critical error during registration: ' . mysqli_error($koneksi)];
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $hapus_id = (int) $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $hapus_id AND role = 'tl'");
    mysqli_query($koneksi, "UPDATE users SET tl_id = NULL WHERE tl_id = $hapus_id");
    $pesan = ['type' => 'warning', 'text' => 'Team Leader account retracted. Associated staff have been unlinked.'];
}

$daftar_tl = mysqli_query($koneksi, "
    SELECT tl.id, tl.nama_lengkap, tl.username, tl.poin,
           COUNT(agen.id) AS jumlah_agen
    FROM users tl
    LEFT JOIN users agen ON agen.tl_id = tl.id AND agen.role = 'agen'
    WHERE tl.role = 'tl'
    GROUP BY tl.id
    ORDER BY tl.poin DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage TL | Scholarly Curator</title>
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
                    <input type="text" placeholder="Search team leaders...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2">
                         <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama_lengkap']); ?>&background=0061f2&color=fff" class="rounded-circle" width="36" height="36">
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start mb-5">
                    <div>
                        <h1 class="page-title">Team Management</h1>
                        <p class="text-subtitle mb-0">Configure high-level team management access and reward structures.</p>
                    </div>
                </div>

                <?php if ($pesan): ?>
                    <div class="alert alert-<?php echo $pesan['type']; ?> alert-modern mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i> <?php echo $pesan['text']; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header"><i class="bi bi-person-plus-fill text-primary"></i> Team Leader Details</div>
                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label class="form-label">Full Legal Name</label>
                                        <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" placeholder="e.g. Dr. Arthur Sterling" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">System Username</label>
                                        <div class="input-modern position-relative">
                                            <span class="position-absolute start-0 top-50 translate-middle-y ps-3 text-muted">@</span>
                                            <input type="text" class="form-control ps-5" name="username" placeholder="username" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Access Password</label>
                                        <input type="password" class="form-control" name="password" required placeholder="Security credentials">
                                    </div>
                                    <button type="submit" name="save_tl" class="btn btn-primary w-100 mb-2">SAVE TEAM LEADER</button>
                                    <button type="reset" class="btn btn-light border w-100 fw-bold text-muted">CLEAR FORM</button>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-4 border-start border-primary border-4">
                             <div class="card-body p-4">
                                <h6 class="fw-800 text-primary small text-uppercase mb-2 ls-wide">Reward Overview</h6>
                                <p class="text-muted small mb-0">Team Leaders receive <span class="text-primary fw-bold">+10 points</span> for every successfully audited sale within their supervised sales team.</p>
                             </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Supervisory Directory</span>
                                <i class="bi bi-shield-lock text-muted"></i>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Leader Name</th>
                                                <th>Staff Count</th>
                                                <th>Reward Points</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($tl = mysqli_fetch_assoc($daftar_tl)): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="rounded-pill d-flex align-items-center justify-content-center bg-primary text-white fw-bold small shadow-sm" style="width:36px; height:36px;">
                                                            <?php echo substr($tl['nama_lengkap'], 0, 1); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo $tl['nama_lengkap']; ?></div>
                                                            <div class="text-muted" style="font-size: 0.7rem;">@<?php echo $tl['username']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border px-3"><?php echo $tl['jumlah_agen']; ?> Staffs</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold <?php echo $tl['poin'] > 0 ? 'text-primary' : 'text-muted'; ?>"><?php echo number_format($tl['poin']); ?> Points</span>
                                                        <?php if($tl['poin'] > 100): ?><i class="bi bi-patch-check-fill text-primary small"></i><?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="?delete=<?php echo $tl['id']; ?>" class="btn btn-link text-danger p-0" onclick="return confirm('Revoke leadership privileges?')"><i class="bi bi-trash3"></i></a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
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
