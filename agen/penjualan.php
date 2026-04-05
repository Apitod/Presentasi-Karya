<?php
// ============================================================
// FILE: agen/penjualan.php
// FUNGSI: Agen melakukan transaksi penjualan produk ke pembeli
// Bukti transaksi berupa UPLOAD GAMBAR (bukan teks)
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];
$pesan = '';

// -----------------------------------------------------------
// PROSES: Simpan transaksi penjualan baru
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pembeli = trim($_POST['nama_pembeli']);
    $jumlah       = (int) $_POST['jumlah'];

    // Validasi field teks
    if (empty($nama_pembeli) || $jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Semua field wajib diisi!'];
    } elseif (!isset($_FILES['bukti_transaksi']) || $_FILES['bukti_transaksi']['error'] !== UPLOAD_ERR_OK) {
        // Validasi: file gambar wajib diunggah
        $pesan = ['type' => 'danger', 'text' => 'Gambar bukti transaksi wajib diunggah!'];
    } else {
        // ---------------------------------------------------
        // Proses Upload Gambar
        // ---------------------------------------------------
        $file       = $_FILES['bukti_transaksi'];
        $ekstensi_ok = ['jpg', 'jpeg', 'png', 'gif']; // Tipe file yang diizinkan
        $ekstensi   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ukuran_max = 2 * 1024 * 1024; // Maksimal 2 MB

        if (!in_array($ekstensi, $ekstensi_ok)) {
            $pesan = ['type' => 'danger', 'text' => 'Format file harus JPG, PNG, atau GIF!'];
        } elseif ($file['size'] > $ukuran_max) {
            $pesan = ['type' => 'danger', 'text' => 'Ukuran file maksimal 2 MB!'];
        } else {
            // Buat nama file unik agar tidak bentrok
            $nama_file = 'bukti_' . $agen_id . '_' . time() . '.' . $ekstensi;
            $tujuan    = '../uploads/' . $nama_file;

            if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
                $pesan = ['type' => 'danger', 'text' => 'Gagal mengunggah file!'];
            } else {
                // Upload berhasil, lanjut proses transaksi
                $stok_data = mysqli_fetch_assoc(mysqli_query(
                    $koneksi,
                    "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"
                ));
                $stok_tersedia = $stok_data ? $stok_data['stok'] : 0;

                if ($stok_tersedia < $jumlah) {
                    $pesan = ['type' => 'danger', 'text' => "Stok tidak cukup! Stok Anda: $stok_tersedia unit."];
                    // Hapus file yang sudah terupload karena transaksi gagal
                    unlink($tujuan);
                } else {
                    $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM produk WHERE id = 1"));
                    $total  = $produk['harga'] * $jumlah;
                    $nama_pembeli_aman = mysqli_real_escape_string($koneksi, $nama_pembeli);

                    // Kurangi stok agen
                    mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok - $jumlah WHERE agen_id = $agen_id");

                    // Simpan transaksi dengan nama file gambar sebagai bukti
                    $query = "INSERT INTO transaksi (agen_id, nama_pembeli, jumlah, total_harga, bukti_transaksi, status)
                              VALUES ($agen_id, '$nama_pembeli_aman', $jumlah, $total, '$nama_file', 'pending')";

                    if (mysqli_query($koneksi, $query)) {
                        $pesan = ['type' => 'success', 'text' => "Transaksi berhasil dicatat! Total: Rp " . number_format($total, 0, ',', '.') . ". Menunggu persetujuan admin."];
                    } else {
                        // Gagal simpan: kembalikan stok dan hapus file
                        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
                        unlink($tujuan);
                        $pesan = ['type' => 'danger', 'text' => 'Gagal mencatat transaksi!'];
                    }
                }
            }
        }
    }
}

// Ambil data terkini setelah proses
$stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"));
$stok_saya = $stok_data ? $stok_data['stok'] : 0;
$produk    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <h3 class="page-title mb-1">
                <i class="bi bi-cart-plus me-2" style="color:#4fd1c5;"></i> Buat Transaksi Penjualan
            </h3>
            <p class="text-muted small mb-4">Input data penjualan produk kepada pembeli.</p>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Transaksi Penjualan -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-semibold" style="background: #0f3460; color:#fff;">
                            <i class="bi bi-pencil-square me-1"></i> Form Penjualan
                        </div>
                        <div class="card-body">
                            <?php if ($stok_saya <= 0): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Stok Anda habis! Silakan
                                    <a href="request_stok.php" class="alert-link">request stok</a> terlebih dahulu.
                                </div>
                            <?php else: ?>
                                <!-- enctype multipart/form-data WAJIB untuk upload file -->
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <!-- Field Nama Pembeli -->
                                    <div class="mb-3">
                                        <label for="nama_pembeli" class="form-label small fw-semibold">
                                            Nama Pembeli <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nama_pembeli" name="nama_pembeli"
                                            required placeholder="Nama lengkap pembeli">
                                    </div>

                                    <!-- Field Jumlah Produk -->
                                    <div class="mb-3">
                                        <label for="jumlah" class="form-label small fw-semibold">
                                            Jumlah Produk <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="jumlah" name="jumlah"
                                            min="1" max="<?php echo $stok_saya; ?>" required placeholder="Contoh: 2">
                                        <div class="form-text">
                                            Maksimal: <strong><?php echo $stok_saya; ?> unit</strong>
                                        </div>
                                    </div>

                                    <!-- Preview Total Harga (real-time via JS) -->
                                    <div id="preview-total" class="mb-3">
                                        <p class="text-muted small mb-1">Estimasi Total Harga</p>
                                        <h4 class="fw-bold mb-0" style="color:#0f3460;" id="total-text">Rp 0</h4>
                                    </div>

                                    <!-- Field Bukti Transaksi: Upload Gambar -->
                                    <div class="mb-4">
                                        <label for="bukti_transaksi" class="form-label small fw-semibold">
                                            Bukti Transaksi (Gambar) <span class="text-danger">*</span>
                                        </label>
                                        <!-- accept: batasi hanya file gambar -->
                                        <input type="file" class="form-control" id="bukti_transaksi"
                                            name="bukti_transaksi" accept="image/*" required>
                                        <div class="form-text">
                                            Upload foto bukti pembayaran. Format: JPG/PNG, maks 2 MB.
                                        </div>
                                        <!-- Preview gambar sebelum dikirim -->
                                        <img id="preview-gambar" src="" alt="Preview Bukti">
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100"
                                        style="background: #0f3460; border: none;">
                                        <i class="bi bi-send me-1"></i> Kirim Transaksi
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info Produk & Stok -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-box-seam" style="font-size: 2.5rem; color: #4fd1c5;"></i>
                            <h5 class="mt-2 fw-bold"><?php echo htmlspecialchars($produk['nama_produk']); ?></h5>
                            <p class="text-muted small">Produk yang dijual</p>
                            <div class="row text-center mt-3">
                                <div class="col-6 border-end">
                                    <p class="text-muted small mb-1">Stok Saya</p>
                                    <h4 class="fw-bold" style="color: <?php echo $stok_saya > 5 ? '#28a745' : '#dc3545'; ?>">
                                        <?php echo $stok_saya; ?>
                                    </h4>
                                    <small class="text-muted">unit</small>
                                </div>
                                <div class="col-6">
                                    <p class="text-muted small mb-1">Harga Satuan</p>
                                    <h4 class="fw-bold" style="color:#0f3460;">
                                        <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                                    </h4>
                                    <small class="text-muted">Rp/unit</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alur transaksi -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Alur Transaksi</h6>
                            <div class="d-flex mb-2">
                                <span class="badge bg-primary me-2 mt-1">1</span>
                                <small>Agen mengisi form dan upload bukti pembayaran</small>
                            </div>
                            <div class="d-flex mb-2">
                                <span class="badge bg-warning text-dark me-2 mt-1">2</span>
                                <small>Transaksi menunggu approval dari Admin</small>
                            </div>
                            <div class="d-flex">
                                <span class="badge bg-success me-2 mt-1">3</span>
                                <small>Admin menyetujui → transaksi selesai</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview total harga real-time
        const inputJumlah = document.getElementById('jumlah');
        const previewDiv  = document.getElementById('preview-total');
        const totalText   = document.getElementById('total-text');
        const hargaSatuan = <?php echo $produk['harga']; ?>;

        if (inputJumlah) {
            inputJumlah.addEventListener('input', function () {
                const jumlah = parseInt(this.value) || 0;
                if (jumlah > 0) {
                    totalText.textContent = 'Rp ' + (jumlah * hargaSatuan).toLocaleString('id-ID');
                    previewDiv.style.display = 'block';
                } else {
                    previewDiv.style.display = 'none';
                }
            });
        }

        // Preview gambar sebelum dikirim
        // FileReader membaca file lokal dan menampilkannya tanpa upload
        const inputBukti   = document.getElementById('bukti_transaksi');
        const previewGambar = document.getElementById('preview-gambar');

        if (inputBukti) {
            inputBukti.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewGambar.src     = e.target.result;
                        previewGambar.style.display = 'block';
                    };
                    reader.readAsDataURL(file); // Baca file sebagai URL data
                } else {
                    previewGambar.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>