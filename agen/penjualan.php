<?php
// ============================================================
// FILE: agen/penjualan.php
// FUNGSI: Agen melakukan transaksi penjualan produk ke pembeli
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
    $jumlah = (int) $_POST['jumlah'];
    $bukti = trim($_POST['bukti_transaksi']); // Bukti berupa teks (bukan file)

    // Validasi: semua field wajib diisi
    if (empty($nama_pembeli) || $jumlah <= 0 || empty($bukti)) {
        $pesan = ['type' => 'danger', 'text' => 'Semua field wajib diisi!'];
    } else {
        // Ambil stok yang dimiliki agen ini
        $stok_data = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"
        ));
        $stok_tersedia = $stok_data ? $stok_data['stok'] : 0;

        // Cek apakah stok mencukupi untuk transaksi ini
        if ($stok_tersedia < $jumlah) {
            $pesan = ['type' => 'danger', 'text' => "Stok tidak cukup! Stok Anda: $stok_tersedia unit."];
        } else {
            // Ambil harga produk untuk menghitung total
            $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM produk WHERE id = 1"));
            $total = $produk['harga'] * $jumlah;

            // Bersihkan input dari potensi injeksi
            $nama_pembeli_aman = mysqli_real_escape_string($koneksi, $nama_pembeli);
            $bukti_aman = mysqli_real_escape_string($koneksi, $bukti);

            // KURANGI stok agen terlebih dahulu sebelum transaksi tercatat
            mysqli_query(
                $koneksi,
                "UPDATE stok_agen SET stok = stok - $jumlah WHERE agen_id = $agen_id"
            );

            // Simpan transaksi baru dengan status 'pending' (menunggu approval admin)
            $query = "INSERT INTO transaksi (agen_id, nama_pembeli, jumlah, total_harga, bukti_transaksi, status)
                      VALUES ($agen_id, '$nama_pembeli_aman', $jumlah, $total, '$bukti_aman', 'pending')";

            if (mysqli_query($koneksi, $query)) {
                $pesan = ['type' => 'success', 'text' => "Transaksi berhasil dicatat! Total: Rp " . number_format($total, 0, ',', '.') . ". Menunggu persetujuan admin."];
            } else {
                // Jika gagal simpan, kembalikan stok yang sudah dikurangi
                mysqli_query(
                    $koneksi,
                    "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id"
                );
                $pesan = ['type' => 'danger', 'text' => 'Gagal mencatat transaksi!'];
            }
        }
    }
}

// Ambil data terkini setelah proses
$stok_data = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"
));
$stok_saya = $stok_data ? $stok_data['stok'] : 0;
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .page-title {
            font-weight: 700;
            color: #0f3460;
        }

        /* Tampilan preview total harga secara real-time */
        #preview-total {
            background: #e8f4fd;
            border: 2px dashed #4fd1c5;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            display: none;
            /* Tersembunyi sampai user mengisi jumlah */
        }
    </style>
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
                                <!-- Tampilkan peringatan jika stok habis -->
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Stok Anda habis! Silakan
                                    <a href="request_stok.php" class="alert-link">request stok</a> terlebih dahulu.
                                </div>
                            <?php else: ?>
                                <form method="POST" action="">
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
                                        <input type="number" class="form-control" id="jumlah" name="jumlah" min="1"
                                            max="<?php echo $stok_saya; ?>" required placeholder="Contoh: 2">
                                        <div class="form-text">
                                            Maksimal: <strong>
                                                <?php echo $stok_saya; ?> unit
                                            </strong> (stok Anda saat ini)
                                        </div>
                                    </div>

                                    <!-- Preview Total Harga (tampil real-time via JavaScript) -->
                                    <div id="preview-total" class="mb-3">
                                        <p class="text-muted small mb-1">Estimasi Total Harga</p>
                                        <h4 class="fw-bold mb-0" style="color:#0f3460;" id="total-text">Rp 0</h4>
                                    </div>

                                    <!-- Field Bukti Transaksi (berupa teks, bukan file) -->
                                    <div class="mb-4">
                                        <label for="bukti_transaksi" class="form-label small fw-semibold">
                                            Bukti Transaksi <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bukti_transaksi" name="bukti_transaksi"
                                            required placeholder="Contoh: REF-20240101-001 atau nomor transfer">
                                        <div class="form-text">
                                            Masukkan nomor referensi, bukti transfer, atau kode transaksi pembayaran.
                                        </div>
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
                            <h5 class="mt-2 fw-bold">
                                <?php echo htmlspecialchars($produk['nama_produk']); ?>
                            </h5>
                            <p class="text-muted small">Produk yang dijual</p>

                            <div class="row text-center mt-3">
                                <div class="col-6 border-end">
                                    <p class="text-muted small mb-1">Stok Saya</p>
                                    <h4 class="fw-bold"
                                        style="color: <?php echo $stok_saya > 5 ? '#28a745' : '#dc3545'; ?>">
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

                    <!-- Keterangan alur transaksi -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Alur Transaksi</h6>
                            <div class="d-flex mb-2">
                                <span class="badge bg-primary me-2 mt-1">1</span>
                                <small>Agen mengisi dan mengirim form transaksi</small>
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
        // JavaScript untuk menampilkan preview total harga secara real-time
        const inputJumlah = document.getElementById('jumlah');
        const previewDiv = document.getElementById('preview-total');
        const totalText = document.getElementById('total-text');
        const hargaSatuan = <?php echo $produk['harga']; ?>; // Ambil harga dari PHP

        if (inputJumlah) {
            inputJumlah.addEventListener('input', function () {
                const jumlah = parseInt(this.value) || 0;
                if (jumlah > 0) {
                    const total = jumlah * hargaSatuan;
                    // Gunakan Intl.NumberFormat untuk format Rupiah
                    totalText.textContent = 'Rp ' + total.toLocaleString('id-ID');
                    previewDiv.style.display = 'block'; // Tampilkan bagian preview
                } else {
                    previewDiv.style.display = 'none';  // Sembunyikan jika kosong
                }
            });
        }
    </script>
</body>

</html>