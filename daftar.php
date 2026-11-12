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
$nama                       = $_POST['nama'] ?? '';
$kategori                   = $_POST['kategori'] ?? '';
$jenis_kelamin              = $_POST['jenis_kelamin'] ?? '';
$tempat_lahir               = $_POST['tempat_lahir'] ?? '';
$tanggal_lahir              = $_POST['tanggal_lahir'] ?? '';
$tel_pribadi                = $_POST['tel_pribadi'] ?? '';
$tel_darurat                = $_POST['tel_darurat'] ?? '';
$nik                        = $_POST['nik'] ?? '';
$email                      = $_POST['email'] ?? '';
$alamat_jalan               = $_POST['alamat_jalan'] ?? '';
$alamat_kelurahan           = $_POST['alamat_kelurahan'] ?? '';
$alamat_kecamatan           = $_POST['alamat_kecamatan'] ?? '';
$alamat_kabupaten           = $_POST['alamat_kabupaten'] ?? '';
$alamat_provinsi            = $_POST['alamat_provinsi'] ?? '';
$kode_pos                   = $_POST['kode_pos'] ?? '';
$alamat_jalan_domisili      = $_POST['alamat_jalan_domisili'] ?? '';
$alamat_kelurahan_domisili  = $_POST['alamat_kelurahan_domisili'] ?? '';
$alamat_kecamatan_domisili  = $_POST['alamat_kecamatan_domisili'] ?? '';
$alamat_kabupaten_domisili  = $_POST['alamat_kabupaten_domisili'] ?? '';
$alamat_provinsi_domisili   = $_POST['alamat_provinsi_domisili'] ?? '';
$kode_pos_domisili          = $_POST['kode_pos_domisili'] ?? '';
$jenjang                    = $_POST['jenjang'] ?? '';
$nama_sekolah               = $_POST['nama_sekolah'] ?? '';
$alamat_sekolah             = $_POST['alamat_sekolah'] ?? '';
$jurusan                    = $_POST['jurusan'] ?? '';
$masuk_sekolah              = $_POST['masuk_sekolah'] ?? '';
$lulus_sekolah              = $_POST['lulus_sekolah'] ?? '';
$pekerjaan1_nama            = $_POST['pekerjaan1_nama'] ?? '';
$pekerjaan1_masuk           = $_POST['pekerjaan1_masuk'] ?? '';
$pekerjaan1_keluar          = $_POST['pekerjaan1_keluar'] ?? '';
$pekerjaan1_jenis           = $_POST['pekerjaan1_jenis'] ?? '';
$pekerjaan2_nama            = $_POST['pekerjaan2_nama'] ?? '';
$pekerjaan2_masuk           = $_POST['pekerjaan2_masuk'] ?? '';
$pekerjaan2_keluar          = $_POST['pekerjaan2_keluar'] ?? '';
$pekerjaan2_jenis           = $_POST['pekerjaan2_jenis'] ?? '';
$pekerjaan3_nama            = $_POST['pekerjaan3_nama'] ?? '';
$pekerjaan3_masuk           = $_POST['pekerjaan3_masuk'] ?? '';
$pekerjaan3_keluar          = $_POST['pekerjaan3_keluar'] ?? '';
$pekerjaan3_jenis           = $_POST['pekerjaan3_jenis'] ?? '';
$pekerjaan4_nama            = $_POST['pekerjaan4_nama'] ?? '';
$pekerjaan4_masuk           = $_POST['pekerjaan4_masuk'] ?? '';
$pekerjaan4_keluar          = $_POST['pekerjaan4_keluar'] ?? '';
$pekerjaan4_jenis           = $_POST['pekerjaan4_jenis'] ?? '';

// Validasi sederhana
$wajib = [
    "nama" => $nama,
    "kategori" => $kategori,
    "jenis_kelamin" => $jenis_kelamin
];

foreach ($wajib as $field => $value) {
    if (trim($value) === "") {
        exit("Field kosong: " . $field);
    }
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
        'ijazah',
    ];

    foreach ($files as $field) {
        if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $mail->addAttachment($_FILES[$field]['tmp_name'], $_FILES[$field]['name']);
        }
    }

    // Isi email
    $mail->isHTML(true);
    $mail->Subject = "Formulir Pendaftaran Baru - $nama";
    $body  = "<h2>Data Pendaftaran Peserta</h2>";
    $body .= "<p><strong>Nama:</strong> $nama</p>";
    $body .= "<p><strong>Kategori:</strong> $kategori</p>";
    $body .= "<p><strong>Jenis Kelamin:</strong> $jenis_kelamin</p>";
    $body .= "<p><strong>Tempat Lahir:</strong> $tempat_lahir</p>";
    $body .= "<p><strong>Tanggal Lahir:</strong> $tanggal_lahir</p>";
    $body .= "<p><strong>Telepon Pribadi:</strong> $tel_pribadi</p>";
    $body .= "<p><strong>Telepon Darurat:</strong> $tel_darurat</p>";
    $body .= "<p><strong>NIK:</strong> $nik</p>";
    $body .= "<p><strong>Email:</strong> $email</p>";
    $body .= "<h3>Alamat KTP</h3>";
    $body .= "<p>$alamat_jalan, $alamat_kelurahan, $alamat_kecamatan, $alamat_kabupaten, $alamat_provinsi, $kode_pos</p>";
    $body .= "<h3>Alamat Domisili</h3>";
    $body .= "<p>$alamat_jalan_domisili, $alamat_kelurahan_domisili, $alamat_kecamatan_domisili, $alamat_kabupaten_domisili, $alamat_provinsi_domisili, $kode_pos_domisili</p>";
    $body .= "<h3>Pendidikan Terakhir</h3>";
    $body .= "<p>Jenjang: $jenjang</p>";
    $body .= "<p>Nama Sekolah: $nama_sekolah ($masuk_sekolah - $lulus_sekolah) $jurusan</p>";
    $body .= "<p>Alamat Sekolah: $alamat_sekolah</p>";
    $body .= "<h3>Riwayat Pekerjaan</h3>";
    $body .= "<p>Pekerjaan 1: $pekerjaan1_nama ($pekerjaan1_masuk - $pekerjaan1_keluar) - $pekerjaan1_jenis</p>";
    $body .= "<p>Pekerjaan 2: $pekerjaan2_nama ($pekerjaan2_masuk - $pekerjaan2_keluar) - $pekerjaan2_jenis</p>";
    $body .= "<p>Pekerjaan 3: $pekerjaan3_nama ($pekerjaan3_masuk - $pekerjaan3_keluar) - $pekerjaan3_jenis</p>";
    $body .= "<p>Pekerjaan 4: $pekerjaan4_nama ($pekerjaan4_masuk - $pekerjaan4_keluar) - $pekerjaan4_jenis</p>";

    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $mail->Timeout = 180;

    $mail->send();

    http_response_code(200);

    if ($_POST['kategori'] == 'Trial Class') {
        echo "TRIAL_CLASS";
    } else {
        echo "OK";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
}
