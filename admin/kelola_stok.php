<?php
// ============================================================
// FILE: admin/kelola_stok.php
// FUNGSI: Admin dapat menambah stok produk utama dan
//         menyetujui/menolak request stok dari agen
// ============================================================

require_once 'cek_sesi.php';   // Guard: pastikan hanya admin yang bisa akses
require_once '../koneksi.php'; // Koneksi database

$pesan = ''; // Variabel untuk menampung pesan sukses atau error

// -----------------------------------------------------------
// PROSES 1: Menambah stok produk utama
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_stok'])) {
    // Ambil jumlah stok yang ingin ditambahkan dari form
    $jumlah = (int) $_POST['jumlah_stok']; // (int) memastikan nilai adalah angka bulat

    if ($jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Jumlah stok harus lebih dari 0!'];
    } else {
        // UPDATE: Tambahkan jumlah ke stok yang sudah ada di tabel produk
        $query = "UPDATE produk SET stok = stok + $jumlah WHERE id = 1";
        mysqli_query($koneksi, $query);
        $pesan = ['type' => 'success', 'text' => "Stok berhasil ditambah sebanyak $jumlah unit!"];
    }
}

// -----------------------------------------------------------
// PROSES 2: Menyetujui request stok dari agen
// -----------------------------------------------------------
if (isset($_GET['approve_request'])) {
    $request_id = (int) $_GET['approve_request']; // ID request yang akan disetujui

    // Ambil detail request dari database
    $req = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT * FROM request_stok WHERE id = $request_id AND status = 'pending'"
    ));

    if ($req) {
        $jumlah = $req['jumlah'];
        $agen_id = $req['agen_id'];

        // Cek apakah stok produk utama cukup untuk memenuhi request
        $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM produk WHERE id = 1"));

        if ($produk['stok'] < $jumlah) {
            $pesan = ['type' => 'danger', 'text' => 'Stok produk tidak cukup untuk memenuhi request ini!'];
        } else {
            // KURANGI stok produk utama
            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $jumlah WHERE id = 1");

            // TAMBAH stok ke akun agen yang bersangkutan
            // Cek dulu apakah agen sudah punya record di tabel stok_agen
            $cek_stok_agen = mysqli_fetch_assoc(mysqli_query(
                $koneksi,
                "SELECT id FROM stok_agen WHERE agen_id = $agen_id"
            ));

            if ($cek_stok_agen) {
                // Jika sudah ada, update (tambahkan)
                mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
            } else {
                // Jika belum ada, buat record baru
                mysqli_query($koneksi, "INSERT INTO stok_agen (agen_id, stok) VALUES ($agen_id, $jumlah)");
            }

            // Ubah status request menjadi 'approved'
            mysqli_query($koneksi, "UPDATE request_stok SET status = 'approved' WHERE id = $request_id");

            $pesan = ['type' => 'success', 'text' => "Request stok berhasil disetujui! Stok agen diperbarui."];
        }
    }
}

// -----------------------------------------------------------
// PROSES 3: Menolak request stok dari agen
// -----------------------------------------------------------
if (isset($_GET['reject_request'])) {
    $request_id = (int) $_GET['reject_request'];
    // Ubah status request menjadi 'rejected'
    mysqli_query($koneksi, "UPDATE request_stok SET status = 'rejected' WHERE id = $request_id");
    $pesan = ['type' => 'warning', 'text' => "Request stok telah ditolak."];
}

// Ambil data produk untuk ditampilkan
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

// Ambil semua request stok yang masih pending, beserta nama agennya
$request_list = mysqli_query($koneksi, "
    SELECT r.*, u.nama_lengkap AS nama_agen
    FROM request_stok r
    JOIN users u ON r.agen_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - Admin</title>
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
            <h3 class="page-title mb-1"><i class="bi bi-boxes me-2" style="color:#4e9af1;"></i> Kelola Stok</h3>
            <p class="text-muted small mb-4">Tambah stok produk dan proses permintaan stok dari agen.</p>

            <!-- Tampilkan pesan sukses/error jika ada -->
            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- -----------------------------------------------
                 KOLOM KIRI: Info Stok + Form Tambah Stok
                 ----------------------------------------------- -->
                <div class="col-md-5">
                    <!-- Kartu Informasi Stok Saat Ini -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-box-seam" style="font-size:2.5rem; color:#4e9af1;"></i>
                            <h5 class="mt-2 mb-0 fw-bold">
                                <?php echo htmlspecialchars($produk['nama_produk']); ?>
                            </h5>
                            <p class="text-muted small">Produk Utama</p>
                            <div class="border rounded p-3 mt-2" style="background:#f8f9fa;">
                                <p class="mb-0 text-muted small">Stok Tersedia</p>
                                <h2 class="fw-bold mb-0" style="color:#1a1a2e;">
                                    <?php echo number_format($produk['stok']); ?>
                                </h2>
                                <small class="text-muted">unit</small>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark border">
                                    Harga: Rp
                                    <?php echo number_format($produk['harga'], 0, ',', '.'); ?> / unit
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Tambah Stok -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Stok Produk
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="jumlah_stok" class="form-label small fw-semibold">
                                        Jumlah yang Ditambahkan
                                    </label>
                                    <!-- Input hanya menerima angka positif -->
                                    <input type="number" class="form-control" id="jumlah_stok" name="jumlah_stok"
                                        min="1" placeholder="Contoh: 50" required>
                                </div>
                                <!-- name="tambah_stok" digunakan sebagai penanda POST di atas -->
                                <button type="submit" name="tambah_stok" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Stok
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- -----------------------------------------------
                 KOLOM KANAN: Daftar Request Stok dari Agen
                 ----------------------------------------------- -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background:#1a1a2e; color:#fff;">
                            <i class="bi bi-inbox me-1"></i> Request Stok dari Agen
                            <!-- Badge jumlah request pending -->
                            <span class="badge bg-warning text-dark ms-2">
                                <?php echo mysqli_num_rows($request_list); ?> pending
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($request_list) == 0): ?>
                                <!-- Tampilkan info jika tidak ada request -->
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                                    <p class="mt-2">Tidak ada request stok yang menunggu.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Agen</th>
                                                <th class="text-center">Jumlah</th>
                                                <th>Catatan</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Loop untuk menampilkan setiap request yang pending
                                            while ($req = mysqli_fetch_assoc($request_list)):
                                                ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-person me-1 text-muted"></i>
                                                        <?php echo htmlspecialchars($req['nama_agen']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y', strtotime($req['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary fs-6">
                                                            <?php echo $req['jumlah']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $req['catatan'] ? htmlspecialchars($req['catatan']) : '-'; ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <!-- Tombol Setujui: kirim request_id via GET -->
                                                        <a href="?approve_request=<?php echo $req['id']; ?>"
                                                            class="btn btn-success btn-sm"
                                                            onclick="return confirm('Setujui request ini?')">
                                                            <i class="bi bi-check-lg"></i>
                                                        </a>
                                                        <!-- Tombol Tolak -->
                                                        <a href="?reject_request=<?php echo $req['id']; ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Tolak request ini?')">
                                                            <i class="bi bi-x-lg"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>