<?php
// ============================================================
// FILE: agen/penjualan.php
// FUNGSI: Record New Sale - Scholarly curator style overhaul
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sale'])) {
    $nama_pembeli = trim($_POST['nama_pembeli']);
    $jumlah       = (int) $_POST['jumlah'];

    if (empty($nama_pembeli) || $jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'All fields are strictly required.'];
    } elseif (!isset($_FILES['bukti_transaksi']) || $_FILES['bukti_transaksi']['error'] !== UPLOAD_ERR_OK) {
        $pesan = ['type' => 'danger', 'text' => 'Proof of transaction image is mandatory.'];
    } else {
        $file       = $_FILES['bukti_transaksi'];
        $ekstensi_ok = ['jpg', 'jpeg', 'png'];
        $ekstensi   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi, $ekstensi_ok)) {
            $pesan = ['type' => 'danger', 'text' => 'Format must be JPG or PNG.'];
        } else {
            $nama_file = 'bukti_' . $agen_id . '_' . time() . '.' . $ekstensi;
            $tujuan    = '../uploads/' . $nama_file;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                $stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"));
                $stok_tersedia = $stok_data['stok'] ?? 0;

                if ($stok_tersedia < $jumlah) {
                    $pesan = ['type' => 'danger', 'text' => "Insufficient stock ($stok_tersedia available)."];
                    unlink($tujuan);
                } else {
                    $produk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM produk WHERE id = 1"));
                    $total  = $produk['harga'] * $jumlah;
                    $nama_pembeli_aman = mysqli_real_escape_string($koneksi, $nama_pembeli);

                    mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok - $jumlah WHERE agen_id = $agen_id");
                    $query = "INSERT INTO transaksi (agen_id, nama_pembeli, jumlah, total_harga, bukti_transaksi, status) 
                              VALUES ($agen_id, '$nama_pembeli_aman', $jumlah, $total, '$nama_file', 'pending')";

                    if (mysqli_query($koneksi, $query)) {
                        $pesan = ['type' => 'success', 'text' => 'Sale recorded successfully. Awaiting administrative audit.'];
                    } else {
                        mysqli_query($koneksi, "UPDATE stok_agen SET stok = stok + $jumlah WHERE agen_id = $agen_id");
                        unlink($tujuan);
                        $pesan = ['type' => 'danger', 'text' => 'System error recording sale.'];
                    }
                }
            }
        }
    }
}

$stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM stok_agen WHERE agen_id = $agen_id"));
$stok_saya = $stok_data ? $stok_data['stok'] : 0;
$produk    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM produk LIMIT 1"));

$recent_sales = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE agen_id = $agen_id ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale | Scholarly Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .calc-box {
            background: #f8f9fc;
            border-radius: 16px;
            padding: 30px;
            text-align: right;
            border: 2px dashed var(--border-color);
        }
        .grand-total {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
        }
        .file-upload-wrapper {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: #f8f9fc;
            cursor: pointer;
            transition: all 0.2s;
        }
        .file-upload-wrapper:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
        }
    </style>
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1">
            <header class="top-nav justify-content-between">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search catalog, sales, or agents...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-muted p-1"><i class="bi bi-bell fs-5"></i></button>
                    <div class="d-flex align-items-center gap-2 border-start ps-3 ms-2">
                        <div class="text-end">
                            <div class="fw-bold small lh-1">Dr. <?php echo explode(' ', $_SESSION['nama_lengkap'])[0]; ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Senior Sales Agent</div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama_lengkap']); ?>&background=0061f2&color=fff" class="rounded-circle" width="36" height="36">
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                <div class="mb-5">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                            <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-primary">TRANSACTION ENTRY</a></li>
                        </ol>
                    </nav>
                    <h1 class="page-title">Record New Sale</h1>
                </div>

                <?php if ($pesan): ?>
                    <div class="alert alert-<?php echo $pesan['type']; ?> alert-modern mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i> <?php echo $pesan['text']; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card p-4">
                            <div class="d-flex gap-2 mb-4">
                                <span class="badge bg-light text-muted border px-3 py-2"><i class="bi bi-box-seam me-1"></i> Current Stock: <?php echo $stok_saya; ?> Units</span>
                                <span class="badge bg-light text-muted border px-3 py-2"><i class="bi bi-tag me-1"></i> Unit Price: Rp <?php echo number_format($produk['harga']); ?></span>
                            </div>

                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label">Buyer Information</label>
                                        <div class="position-relative">
                                            <i class="bi bi-person position-absolute start-0 top-50 translate-middle-y ps-3 text-muted"></i>
                                            <input type="text" name="nama_pembeli" class="form-control ps-5" placeholder="Enter Full Legal Name or Entity" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Order Quantity</label>
                                        <div class="position-relative">
                                            <i class="bi bi-cart position-absolute start-0 top-50 translate-middle-y ps-3 text-muted"></i>
                                            <input type="number" name="jumlah" id="input_jumlah" class="form-control ps-5" placeholder="0" min="1" max="<?php echo $stok_saya; ?>" required>
                                        </div>
                                        <?php if($stok_saya < 5): ?>
                                        <div class="mt-2 text-danger small fw-bold">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Low Stock Warning: Only <?php echo $stok_saya; ?> units remaining.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Upload Proof</label>
                                        <input type="file" name="bukti_transaksi" class="form-control" accept="image/*" required>
                                        <div class="form-text">JPG/PNG Proof of payment required.</div>
                                    </div>
                                    <div class="col-12 mt-5">
                                        <button type="submit" name="submit_sale" class="btn btn-primary-modern w-100 py-3 rounded-3 d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-send-fill"></i> Submit Sale
                                        </button>
                                        <p class="text-center text-muted small mt-3">By submitting, you confirm that all transaction data is accurate per academic audit guidelines.</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="calc-box mb-4">
                            <div class="text-muted small fw-bold text-uppercase mb-2">Total Calculation</div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Units</span>
                                <span class="fw-bold" id="calc_units">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <span class="text-muted">Rate</span>
                                <span class="fw-bold" id="calc_rate">Rp <?php echo number_format($produk['harga']); ?></span>
                            </div>
                            <div class="text-muted small fw-bold text-uppercase mb-1">Grand Total</div>
                            <div class="grand-total" id="calc_total">Rp 0</div>
                        </div>

                        <div class="card border-0 shadow-sm">
                             <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 text-success mb-3">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                    <h6 class="fw-bold mb-0">Transaction Policy</h6>
                                </div>
                                <p class="text-muted small">All new sales are subject to a 24-hour verification period. Transactions exceeding Rp 5,000,000 require administrative approval.</p>
                                <a href="#" class="text-primary text-decoration-none small fw-bold">Read Policy <i class="bi bi-chevron-right small"></i></a>
                             </div>
                        </div>
                        
                        <div class="card bg-dark mt-4 overflow-hidden">
                             <img src="https://images.unsplash.com/photo-1554224155-1696413565d3?q=80&w=2000&auto=format&fit=crop" class="card-img opacity-50" style="height: 154px; object-fit: cover;">
                             <div class="card-img-overlay d-flex flex-column justify-content-end p-3">
                                <div class="badge bg-white text-dark p-1 rounded-pill w-auto d-inline-block small" style="font-size: 0.6rem; width: fit-content !important;">JOURNAL ARCHIVE</div>
                                <h6 class="text-white fw-bold mb-0 mt-2">Sales log #2024-QX</h6>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-800">Recent Sales Activity</h4>
                        <a href="riwayat.php" class="text-primary text-decoration-none small fw-bold">View All History</a>
                    </div>
                    <div class="row g-3">
                        <?php while($row = mysqli_fetch_assoc($recent_sales)): ?>
                        <div class="col-md-4">
                            <div class="card bg-white border shadow-none p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary-soft text-primary p-2 rounded-circle">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small"><?php echo $row['nama_pembeli']; ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?php echo $row['jumlah']; ?> Units • Rp <?php echo number_format($row['total_harga']); ?></div>
                                    </div>
                                    <span class="badge <?php echo $row['status'] == 'approved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?> p-1" style="font-size: 0.6rem;"><?php echo strtoupper($row['status']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const input = document.getElementById('input_jumlah');
        const calcUnits = document.getElementById('calc_units');
        const calcTotal = document.getElementById('calc_total');
        const rate = <?php echo $produk['harga']; ?>;

        input.addEventListener('input', (e) => {
            const val = parseInt(e.target.value) || 0;
            calcUnits.innerText = val;
            calcTotal.innerText = 'Rp ' + (val * rate).toLocaleString('id-ID');
        });
    </script>
</body>
</html>