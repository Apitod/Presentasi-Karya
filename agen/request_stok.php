<?php
// ============================================================
// FILE: agen/request_stok.php
// FUNGSI: Agen mengajukan permintaan tambahan stok ke admin.
// Menggunakan cURL untuk bypass SSL di localhost (XAMPP/Laragon).
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];
$pesan   = '';

// KONFIGURASI TELEGRAM
$telegram_bot_token = '8295652071:AAHLyBGaWCDD-ilTrKwkDWCasHNXcYIZ_e8';
$telegram_chat_id   = '7798797362';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah  = (int) $_POST['jumlah'];
    $catatan = trim($_POST['catatan'] ?? '');

    if ($jumlah <= 0) {
        $pesan = ['type' => 'danger', 'text' => 'Jumlah permintaan harus lebih dari 0!'];
    } else {
        $catatan_aman = mysqli_real_escape_string($koneksi, $catatan);
        $query = "INSERT INTO request_stok (agen_id, jumlah, catatan, status) 
                  VALUES ($agen_id, $jumlah, '$catatan_aman', 'pending')";

        if (mysqli_query($koneksi, $query)) {
            $pesan = ['type' => 'success', 'text' => "Permintaan stok sebanyak $jumlah unit berhasil dikirim!"];

            // --- PROSES KIRIM NOTIFIKASI TELEGRAM DENGAN CURL ---
            $nama_agen = $_SESSION['nama_lengkap'];
            $teks_pesan = "🔔 *Permintaan Stok Baru!*\n"
                        . "Agen   : *$nama_agen*\n"
                        . "Jumlah : *$jumlah unit*\n"
                        . "Catatan: " . ($catatan ?: '-') . "\n"
                        . "Status : _Menunggu Persetujuan_";

            $url_telegram = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
            $data = [
                'chat_id' => $telegram_chat_id,
                'text' => $teks_pesan,
                'parse_mode' => 'Markdown'
            ];

            // Inisialisasi PHP cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_telegram);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // KONFIGURASI MANDATORY UNTUK BYPASS SSL DI LOCALHOST (XAMPP/LARAGON)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            // Eksekusi dan tangani kemungkinan error
            $response = curl_exec($ch);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                // Beritahu user jika notifikasi gagal terkirim (masalah koneksi/SSL)
                $pesan['text'] .= " (Catatan: Notifikasi Telegram gagal: $curl_err)";
            }
            // --- END CURL ---

        } else {
            $pesan = ['type' => 'danger', 'text' => 'Gagal mengirim permintaan: ' . mysqli_error($koneksi)];
        }
    }
}

$riwayat_request = mysqli_query($koneksi, "SELECT * FROM request_stok WHERE agen_id = $agen_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Stok | Panel Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="d-flex" id="main-wrapper">
        <?php include 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <div class="mb-4">
                <h3 class="fw-bold mb-1">Permintaan Stok</h3>
                <p class="text-muted small mb-0">Ajukan penambahan stok ke Admin. Notifikasi otomatis akan dikirim ke Telegram.</p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> py-2 small shadow-sm">
                    <i class="bi bi-info-circle me-2"></i> <?php echo $pesan['text']; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold border-0 pt-4">Buat Permintaan Baru</div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Jumlah Unit</label>
                                    <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 20" required min="1">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Catatan Penjelasan</label>
                                    <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: Stok menipis untuk penjualan minggu depan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Kirim Permintaan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold border-0 pt-4">Riwayat Permintaan</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Tanggal</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($req = mysqli_fetch_assoc($riwayat_request)): ?>
                                        <tr>
                                            <td class="ps-4 small text-muted"><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></td>
                                            <td class="text-center fw-bold text-dark"><?php echo $req['jumlah']; ?> <small class="text-muted">Unit</small></td>
                                            <td class="small">
                                                <?php
                                                $status_colors = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'];
                                                $status_labels = ['pending' => 'MENUNGGU', 'approved' => 'DISETUJUI', 'rejected' => 'DITOLAK'];
                                                ?>
                                                <span class="badge <?php echo $status_colors[$req['status']]; ?> rounded-pill">
                                                    <?php echo $status_labels[$req['status']]; ?>
                                                </span>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>