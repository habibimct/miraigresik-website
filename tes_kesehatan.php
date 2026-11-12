<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit("Forbidden");
}

// Ambil semua data teks
$nama             = $_POST['nama'] ?? '';
$tel_pribadi      = $_POST['tel_pribadi'] ?? '';
$tempat_lahir     = $_POST['tempat_lahir'] ?? '';
$tanggal_lahir    = $_POST['tanggal_lahir'] ?? '';
$lokasi_tes       = $_POST['lokasi_tes_text'] ?? '';
$nama_tes         = $_POST['nama_tes'] ?? '';
$tanggal_tes      = $_POST['tanggal_tes'] ?? '';

// Validasi sederhana
if (!$nama || !$tel_pribadi || !$lokasi_tes || !$tanggal_tes) {
    http_response_code(400);
    exit("Data tidak lengkap");
}

$mail = new PHPMailer(true);

try {
    // SMTP Gmail

    $config = require __DIR__ . '/config/config.php';

    $mail->isSMTP();
    $mail->Host       = $config['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['SMTP_USER'];
    $mail->Password   = $config['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['SMTP_PORT'];

    // Pengirim & tujuan
    $mail->setFrom('lpkmiraigresik@gmail.com', 'LPK Mirai Gresik');
    $mail->addAddress('admin@miraigresik.com');

    // Lampirkan file jika ada
    $files = [
        'bukti_transfer',
        'hasil_tes_kesehatan',
        'surat_izin',
        'surat_pernyataan',
        'foto_3x4',
        'ktp',
        'kk',
        'akta_kelahiran',
        'sd_ijazah',
        'smp_ijazah',
        'sma_ijazah',
        'pt_ijazah'
    ];

    foreach ($files as $field) {
        if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $mail->addAttachment($_FILES[$field]['tmp_name'], $_FILES[$field]['name']);
        }
    }

    // Isi email
    $mail->isHTML(true);
    $mail->Subject = "Formulir Tes Kesehatan Baru - $nama";
    $body  = "<h2>Data Tes Kesehatan Peserta</h2>";
    $body .= "<p><strong>Nama:</strong> $nama</p>";
    $body .= "<p><strong>Telepon Pribadi:</strong> $tel_pribadi</p>";
    $body .= "<p><strong>Tempat Lahir:</strong> $tempat_lahir</p>";
    $body .= "<p><strong>Tanggal Lahir:</strong> $tanggal_lahir</p>";
    $body .= "<p><strong>Lokasi Tes:</strong> $lokasi_tes</p>";
    $body .= "<p><strong>Nama Tes:</strong> $nama_tes</p>";
    $body .= "<p><strong>Tanggal Tes:</strong> $tanggal_tes</p>";

    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    $mail->send();

    http_response_code(200);
    echo "OK";
} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
}
