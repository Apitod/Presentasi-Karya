<?php
// ============================================================
// FILE: agen/request_stok.php
// FUNGSI: Agen mengajukan permintaan tambahan stok ke admin.
//
// PEMBARUAN v2: Setelah request berhasil disimpan ke database,
// sistem akan mengirim notifikasi otomatis ke Telegram Admin.
// Menggunakan file_get_contents() murni — tanpa cURL, tanpa n8n.
// ============================================================

require_once 'cek_sesi.php';
require_once '../koneksi.php';

$agen_id = $_SESSION['user_id'];     // Ambil ID agen dari sesi login
$pesan   = '';

// -----------------------------------------------------------
// KONFIGURASI TELEGRAM
// -----------------------------------------------------------
// Isi dua variabel ini dengan data bot Telegram kamu:
// 1. BOT_TOKEN: token yang diberikan oleh @BotFather di Telegram
// 2. CHAT_ID: ID chat Admin (bisa grup atau private chat)
// Cara mendapat CHAT_ID: kirim pesan ke bot, lalu buka URL:
// https://api.telegram.org/bot<TOKEN>/getUpdates
// -----------------------------------------------------------
$telegram_bot_token = '8295652071:AAHLyBGaWCDD-ilTrKwkDWCasHNXcYIZ_e8';   // Contoh: 123456789:ABCdef...
$telegram_chat_id   = '8295652071';     // Contoh: -1001234567890

// -----------------------------------------------------------
// PROSES: Simpan request stok baru ke database
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah  = (int) $_POST['jumlah'];           // Paksa ke integer agar aman
    $catatan = trim($_POST['catatan'] ?? '');     // Catatan opsional dari agen

    if ($jumlah <= 0) {
        // Validasi: jumlah harus lebih dari 0
        $pesan = ['type' => 'danger', 'text' => 'Jumlah request harus lebih dari 0!'];
    } else {
        // Bersihkan catatan dari karakter berbahaya sebelum disimpan ke database
        $catatan_aman = mysqli_real_escape_string($koneksi, $catatan);

        // Susun query INSERT untuk menyimpan request ke tabel request_stok
        $query = "INSERT INTO request_stok (agen_id, jumlah, catatan, status) 
                  VALUES ($agen_id, $jumlah, '$catatan_aman', 'pending')";

        if (mysqli_query($koneksi, $query)) {
            // Query berhasil! Request tersimpan di database.
            $pesan = ['type' => 'success', 'text' => "Request stok sebanyak $jumlah unit berhasil dikirim! Menunggu persetujuan admin."];

            // -------------------------------------------------------
            // KIRIM NOTIFIKASI TELEGRAM KE ADMIN
            // -------------------------------------------------------
            // Ambil nama agen dari sesi untuk dimasukkan ke pesan
            $nama_agen = $_SESSION['nama_lengkap'];

            // Susun teks pesan yang akan dikirim ke Telegram
            // Format: pesan dengan emoji agar mudah dibaca di chat
            $teks_pesan = "🔔 *Request Stok Baru!*\n"
                        . "👤 Agen   : *$nama_agen*\n"
                        . "📦 Jumlah : *$jumlah unit*\n"
                        . "📝 Catatan: " . ($catatan ?: '-') . "\n"
                        . "⏳ Status : _Menunggu Persetujuan_\n\n"
                        . "Silakan cek panel admin untuk menyetujui request ini.";

            // Encode teks agar aman dikirim via URL (spasi → %20, dll)
            $teks_encoded = urlencode($teks_pesan);

            // Susun URL lengkap untuk memanggil Telegram Bot API
            // Format URL API Telegram: https://api.telegram.org/bot<TOKEN>/sendMessage
            $url_telegram = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage"
                          . "?chat_id={$telegram_chat_id}"
                          . "&text={$teks_encoded}"
                          . "&parse_mode=Markdown"; // Markdown agar teks bisa bold, italic, dll

            // Gunakan file_get_contents() untuk mengirim request GET ke URL Telegram
            // Ini adalah cara paling ringkas di PHP — tidak perlu cURL atau library tambahan
            // @ di depan = sembunyikan error jika koneksi gagal (agar tidak mengganggu user)
            @file_get_contents($url_telegram);
            // -------------------------------------------------------
            // CATATAN PENTING:
            // Jika hosting kamu memblokir file_get_contents() untuk URL eksternal,
            // aktifkan 'allow_url_fopen = On' di php.ini.
            // -------------------------------------------------------

        } else {
            // Query gagal dijalankan, tampilkan pesan error
            $pesan = ['type' => 'danger', 'text' => 'Gagal mengirim request: ' . mysqli_error($koneksi)];
        }
    }
}

// Ambil riwayat semua request stok milik agen ini, urut terbaru dulu
$riwayat_request = mysqli_query(
    $koneksi,
    "SELECT * FROM request_stok WHERE agen_id = $agen_id ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Stok - Agen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="d-flex" id="main-wrapper">
        <?php require_once 'navbar.php'; ?>

        <div class="flex-grow-1 p-4">
            <!-- Header Halaman -->
            <div class="mb-4">
                <h3 class="page-title mb-1">
                    <i class="bi bi-arrow-up-circle me-2" style="color:#4fd1c5;"></i> Request Stok
                </h3>
                <p class="text-muted small mb-0">Ajukan permintaan penambahan stok kepada admin. Notifikasi otomatis akan terkirim ke Telegram Admin.</p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?php echo $pesan['type']; ?> alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
                    <i class="bi bi-<?php echo $pesan['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $pesan['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Request Stok -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header fw-semibold border-0 rounded-top-4" style="background: #1a1a2e; color:#fff;">
                            <i class="bi bi-send me-2"></i> Buat Request Baru
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="jumlah" class="form-label small fw-semibold">
                                        Jumlah Stok yang Diminta <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control rounded-3" id="jumlah" name="jumlah"
                                        min="1" placeholder="Contoh: 20" required>
                                    <div class="form-text">Masukkan jumlah unit yang Anda butuhkan.</div>
                                </div>
                                <div class="mb-4">
                                    <label for="catatan" class="form-label small fw-semibold">
                                        Catatan (opsional)
                                    </label>
                                    <textarea class="form-control rounded-3" id="catatan" name="catatan" rows="3"
                                        placeholder="Contoh: untuk acara pameran minggu depan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold"
                                    style="background: #1a1a2e; border: none;">
                                    <i class="bi bi-send me-2"></i> Kirim Request + Notif Telegram
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Info card tentang Telegram -->
                    <div class="card border-0 rounded-4 mt-3" style="background:#f0f9ff;">
                        <div class="card-body py-3 px-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-telegram text-primary fs-5"></i>
                                <span class="fw-semibold small text-primary">Notifikasi Telegram Aktif</span>
                            </div>
                            <p class="text-muted small mb-0">
                                Setiap request yang berhasil dikirim akan secara otomatis memberi tahu Admin melalui bot Telegram.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Request Sebelumnya -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header fw-semibold border-0 rounded-top-4" style="background: #1a1a2e; color:#fff;">
                            <i class="bi bi-list-check me-2"></i> Riwayat Request Saya
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($riwayat_request) == 0): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2.5rem;opacity:0.4;"></i>
                                    <p class="mt-3 mb-0 small">Belum ada request yang pernah dibuat.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Tanggal</th>
                                                <th class="text-center">Jumlah</th>
                                                <th>Catatan</th>
                                                <th class="text-center pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($req = mysqli_fetch_assoc($riwayat_request)): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center fw-bold">
                                                        <?php echo $req['jumlah']; ?>
                                                        <span class="text-muted fw-normal">unit</span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $req['catatan'] ? htmlspecialchars($req['catatan']) : '-'; ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center pe-4">
                                                        <?php
                                                        // Tentukan warna badge berdasarkan status request
                                                        $badge = [
                                                            'pending'  => 'warning text-dark',
                                                            'approved' => 'success',
                                                            'rejected' => 'danger'
                                                        ];
                                                        $label = [
                                                            'pending'  => '⏳ Pending',
                                                            'approved' => '✓ Disetujui',
                                                            'rejected' => '✗ Ditolak'
                                                        ];
                                                        ?>
                                                        <span class="badge rounded-pill bg-<?php echo $badge[$req['status']]; ?>">
                                                            <?php echo $label[$req['status']]; ?>
                                                        </span>
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