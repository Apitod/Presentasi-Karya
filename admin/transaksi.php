<?php
// ============================================================
// FILE: admin/transaksi.php
// FUNGSI: Admin melihat semua transaksi dan menyetujui/menolaknya
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$pesan = '';

// -----------------------------------------------------------
// PROSES 1: Menyetujui transaksi dari agen
// -----------------------------------------------------------
if (isset($_GET['approve'])) {
    $trx_id = (int) $_GET['approve'];

    // Ambil data transaksi yang akan disetujui (pastikan masih pending)
    $trx = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT * FROM transaksi WHERE id = $trx_id AND status = 'pending'"
    ));

    if ($trx) {
        // Ubah status menjadi 'approved'
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'approved' WHERE id = $trx_id");
        $pesan = ['type' => 'success', 'text' => 'Transaksi berhasil disetujui!'];
    }
}

// -----------------------------------------------------------
// PROSES 2: Menolak transaksi dari agen
// -----------------------------------------------------------
if (isset($_GET['reject'])) {
    $trx_id = (int) $_GET['reject'];

    // Ambil data transaksi untuk mendapatkan jumlah dan agen_id
    $trx = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT * FROM transaksi WHERE id = $trx_id AND status = 'pending'"
    ));

    if ($trx) {
        // Kembalikan stok ke agen karena transaksi ditolak
        $agen_id = $trx['agen_id'];
        $jumlah = $trx['jumlah'];
        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");

        // Ubah status transaksi menjadi 'rejected'
        mysqli_query($koneksi, "UPDATE transaksi SET status = 'rejected' WHERE id = $trx_id");
        $pesan = ['type' => 'warning', 'text' => 'Transaksi ditolak. Stok dikembalikan ke agen.'];
    }
}

// -----------------------------------------------------------
// Filter tampilan berdasarkan tab yang dipilih
// -----------------------------------------------------------
// Default tampilkan semua transaksi, atau filter berdasarkan parameter GET
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where = '';
if ($filter == 'pending')
    $where = "WHERE t.status = 'pending'";
if ($filter == 'approved')
    $where = "WHERE t.status = 'approved'";
if ($filter == 'rejected')
    $where = "WHERE t.status = 'rejected'";

// Ambil semua transaksi sesuai filter, beserta nama agennya
$transaksi_list = mysqli_query($koneksi, "
    SELECT t.*, u.nama_lengkap AS nama_agen
    FROM transaksi t
    JOIN users u ON t.agen_id = u.id
    $where
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .page-title {
            font-weight: 700;
            color: #1a1a2e;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <h3 class="page-title mb-1"><i class="bi bi-receipt-cutoff me-2" style="color:#4e9af1;"></i> Manajemen
                Transaksi</h3>
            <p class="text-muted small mb-4">Tinjau dan setujui transaksi penjualan dari agen.</p>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tab Filter Transaksi -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'semua' ? 'active' : ''; ?>" href="?filter=semua">
                        Semua
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'pending' ? 'active' : ''; ?>" href="?filter=pending">
                        <span class="badge bg-warning text-dark me-1">⏳</span> Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'approved' ? 'active' : ''; ?>" href="?filter=approved">
                        <span class="badge bg-success me-1">✓</span> Disetujui
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'rejected' ? 'active' : ''; ?>" href="?filter=rejected">
                        <span class="badge bg-danger me-1">✗</span> Ditolak
                    </a>
                </li>
            </ul>

            <!-- Tabel Daftar Transaksi -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Agen</th>
                                    <th>Nama Pembeli</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Total</th>
                                    <th>Bukti Transaksi</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                // Loop menampilkan setiap transaksi
                                while ($trx = mysqli_fetch_assoc($transaksi_list)):
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo $no++; ?>
                                        </td>
                                        <td>
                                            <small><i class="bi bi-person me-1 text-muted"></i></small>
                                            <?php echo htmlspecialchars($trx['nama_agen']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($trx['nama_pembeli']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">
                                                <?php echo $trx['jumlah']; ?> unit
                                            </span>
                                        </td>
                                        <td class="fw-semibold">
                                            Rp
                                            <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td>
                                            <!-- Bukti transaksi ditampilkan dalam format teks -->
                                            <span class="badge bg-light text-dark border"
                                                title="<?php echo htmlspecialchars($trx['bukti_transaksi']); ?>">
                                                <?php echo htmlspecialchars(substr($trx['bukti_transaksi'], 0, 20)); ?>
                                                <?php echo strlen($trx['bukti_transaksi']) > 20 ? '...' : ''; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <!-- Badge status dengan warna berbeda -->
                                            <?php
                                            // Tentukan warna badge berdasarkan status
                                            $badge = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'];
                                            $label = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
                                            ?>
                                            <span class="badge bg-<?php echo $badge[$trx['status']]; ?>">
                                                <?php echo $label[$trx['status']]; ?>
                                            </span>
                                        </td>
                                <td class="text-center">
                                    <?php if ($trx['status'] == 'pending'): ?>
                                        <!-- Tombol aksi hanya tampil untuk transaksi yang masih pending -->
                                                <a href="?approve=<?php echo $trx['id']; ?>&filter=<?php echo $filter; ?>"
                                                    class="btn btn-success btn-sm me-1"
                                                    onclick="return confirm('Setujui transaksi ini?')" title="Setujui">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                                <a href="?reject=<?php echo $trx['id']; ?>&filter=<?php echo $filter; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Tolak transaksi ini? Stok akan dikembalikan ke agen.')"
                                                    title="Tolak">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if (mysqli_num_rows($transaksi_list) == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="bi bi-receipt" style="font-size:1.5rem;"></i>
                                            <p class="mt-2 mb-0">Tidak ada transaksi ditemukan.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>