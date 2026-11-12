<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Forbidden');
}

// ========================
// Sanitasi Input
// ========================
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$nama        = clean($_POST['nama'] ?? '');
$tel_pribadi = preg_replace('/[^0-9]/', '', $_POST['tel_pribadi'] ?? '');
$nominal     = (int) ($_POST['nominal'] ?? 0);
$keterangan  = clean($_POST['keterangan'] ?? '');

// ========================
// Validasi
// ========================
if (!$nama || !$tel_pribadi || $nominal <= 0 || !$keterangan) {
    http_response_code(400);
    exit('Data tidak lengkap');
}

// Format Rupiah
$nominal_rp = 'Rp ' . number_format($nominal, 0, ',', '.');

// ========================
// Konfigurasi Email
// ========================
$config = require __DIR__ . '/config/config.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['SMTP_USER'];
    $mail->Password   = $config['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['SMTP_PORT'];

    $mail->setFrom('lpkmiraigresik@gmail.com', 'LPK Mirai Gresik');
    $mail->addAddress('admin@miraigresik.com');

    // ========================
    // Upload Bukti Pembayaran
    // ========================
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_size    = 5 * 1024 * 1024; // 5MB

    if (!empty($_FILES['bukti_pembayaran']['tmp_name'])) {
        $file = $_FILES['bukti_pembayaran'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            exit('Format file tidak diizinkan');
        }

        if ($file['size'] > $max_size) {
            exit('Ukuran file maksimal 2MB');
        }

        $mail->addAttachment($file['tmp_name'], 'Bukti-Transfer-' . $nama . '.' . $ext);
    }

    // ========================
    // Isi Email
    // ========================
    $mail->isHTML(true);
    $mail->Subject = "Pembayaran - $nama";

    $mail->Body = "
        <h2>Data Pembayaran Peserta</h2>
        <table cellpadding='6'>
            <tr><td><strong>Nama</strong></td><td>: $nama</td></tr>
            <tr><td><strong>Telepon</strong></td><td>: $tel_pribadi</td></tr>
            <tr><td><strong>Nominal</strong></td><td>: $nominal_rp</td></tr>
            <tr><td><strong>Keterangan</strong></td><td>: $keterangan</td></tr>
        </table>
        <p><i>Email ini dikirim otomatis dari sistem pembayaran.</i></p>
    ";

    $mail->AltBody = "Nama: $nama\nTelepon: $tel_pribadi\nNominal: $nominal_rp\nKeterangan: $keterangan";

    $mail->send();

    http_response_code(200);
    echo 'OK';

} catch (Exception $e) {
    http_response_code(500);
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}
