<?php
require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];
$pesan = '';

// proses simpan penjualan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sale'])) {
    $nama_pembeli = trim($_POST['nama_pembeli']);
    $jumlah       = (int) $_POST['jumlah'];

    if (empty($nama_pembeli) || $jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Seluruh kolom wajib diisi dengan benar.'];
    } elseif (!isset($_FILES['bukti_transaksi']) || $_FILES['bukti_transaksi']['error'] !== UPLOAD_ERR_OK) {
        $pesan = ['type' => 'danger', 'text' => 'Foto bukti transaksi wajib diunggah.'];
    } else {
        $file       = $_FILES['bukti_transaksi'];
        $ekstensi_ok = ['jpg', 'jpeg', 'png'];
        $ekstensi   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi, $ekstensi_ok)) {
            $pesan = ['type' => 'danger', 'text' => 'Format file harus JPG atau PNG.'];
        } else {
            $nama_file = 'bukti_' . $agen_id . '_' . time() . '.' . $ekstensi;
            $tujuan    = '../uploads/' . $nama_file;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                $stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"));
                $stok_tersedia = $stok_data['stok'] ?? 0;

                if ($stok_tersedia < $jumlah) {
                    $pesan = ['type' => 'danger', 'text' => "Stok Anda tidak mencukupi (Tersedia: $stok_tersedia)."];
                    unlink($tujuan);
                } else {
                    $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM produk WHERE id = 1"));
                    $total  = $produk['harga'] * $jumlah;
                    $nama_pembeli_aman = mysqli_real_escape_string($koneksi, $nama_pembeli);

                    // simpan data transaksi
                    mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok - $jumlah WHERE agen_id = $agen_id");
                    $query = "INSERT INTO transaksi (agen_id, nama_pembeli, jumlah, total_harga, bukti_transaksi, status) 
                              VALUES ($agen_id, '$nama_pembeli_aman', $jumlah, $total, '$nama_file', 'pending_admin')";

                    if (mysqli_query($koneksi, $query)) {
                        $pesan = ['type' => 'success', 'text' => 'Penjualan berhasil dicatat! Menunggu verifikasi admin.'];
                    } else {
                        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
                        unlink($tujuan);
                        $pesan = ['type' => 'danger', 'text' => 'Terjadi kesalahan sistem saat menyimpan data.'];
                    }
                }
            }
        }
    }
}

// data stok tl
$stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"));
$stok_saya = $stok_data ? $stok_data['stok'] : 0;
// data produk
$produk    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

// data riwayat transaksi terakhir
$recent_sales = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Penjualan | Panel Team Leader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-5">
                <h3 class="fw-bold mb-1">Input Penjualan Baru</h3>
                <p class="text-muted small">Catat data penjualan produk kepada pembeli di sini.</p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> py-2 small shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> <?php echo $pesan['text']; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex gap-2 mb-4">
                            <span class="badge bg-light text-dark border px-3 py-2 fw-bold">Stok Saya: <?php echo $stok_saya; ?> Unit</span>
                            <span class="badge bg-light text-dark border px-3 py-2 fw-bold">Harga Satuan: Rp <?php echo number_format($produk['harga']); ?></span>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Identitas Pembeli</label>
                                <input type="text" name="nama_pembeli" class="form-control" placeholder="Nama Lengkap Pembeli / Instansi" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Jumlah Pesanan</label>
                                    <input type="number" name="jumlah" id="input_jumlah" class="form-control" placeholder="0" min="1" max="<?php echo $stok_saya; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Unggah Bukti Transaksi</label>
                                    <input type="file" name="bukti_transaksi" class="form-control" accept="image/*" required>
                                </div>
                            </div>
                            
                            <div class="bg-light p-4 rounded-3 text-end my-4">
                                <div class="text-muted small fw-bold text-uppercase">Estimasi Total Pembayaran</div>
                                <h1 class="fw-bold text-primary mb-0" id="display_total">Rp 0</h1>
                            </div>

                            <button type="submit" name="submit_sale" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">Simpan Data Penjualan</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm bg-primary text-white p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Audit</h5>
                        <p class="small text-white-50">Setiap transaksi yang disimpan akan melalui proses verifikasi oleh Admin sebelum masuk ke riwayat poin.</p>
                        <hr class="opacity-25">
                        <div class="small fw-bold mb-1">Aktivitas Terakhir:</div>
                        <?php while($row = mysqli_fetch_assoc($recent_sales)): ?>
                            <div class="d-flex justify-content-between small opacity-75 mb-1">
                                <span><?php echo $row['nama_pembeli']; ?></span>
                                <span class="badge bg-white text-primary rounded-pill" style="font-size: 0.6rem;"><?php echo strtoupper($row['status']); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const input = document.getElementById('input_jumlah');
        const displayTotal = document.getElementById('display_total');
        const harga = <?php echo $produk['harga']; ?>;

        if(input) {
            input.addEventListener('input', (e) => {
                const total = (parseInt(e.target.value) || 0) * harga;
                displayTotal.innerText = 'Rp ' + total.toLocaleString('id-ID');
            });
        }
    </script>
</body>
</html>