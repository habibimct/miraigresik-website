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
$jenis_kelamin              = $_POST['jenis_kelamin'] ?? '';
$tempat_lahir               = $_POST['tempat_lahir'] ?? '';
$tanggal_lahir              = $_POST['tanggal_lahir'] ?? '';
$email                      = $_POST['email'] ?? '';
$nomor_telepon              = $_POST['nomor_telepon'] ?? '';
$nomor_darurat              = $_POST['nomor_darurat'] ?? '';
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
$usia                       = $_POST['usia'] ?? '';
$berat_badan                = $_POST['berat_badan'] ?? '';
$tinggi_badan               = $_POST['tinggi_badan'] ?? '';
$gol_darah                  = $_POST['gol_darah'] ?? '';
$ukuran_baju                = $_POST['ukuran_baju'] ?? '';
$ukuran_sepatu              = $_POST['ukuran_sepatu'] ?? '';
$ukuran_pinggang            = $_POST['ukuran_pinggang'] ?? '';
$ukuran_panjang_kaki        = $_POST['ukuran_panjang_kaki'] ?? '';
$bahasa                     = $_POST['bahasa'] ?? [];
$sd_nama                    = $_POST['sd_nama'] ?? '';
$sd_masuk                   = $_POST['sd_masuk'] ?? '';
$sd_lulus                   = $_POST['sd_lulus'] ?? '';
$smp_nama                   = $_POST['smp_nama'] ?? '';
$smp_masuk                  = $_POST['smp_masuk'] ?? '';
$smp_lulus                  = $_POST['smp_lulus'] ?? '';
$sma_nama                   = $_POST['sma_nama'] ?? '';
$sma_masuk                  = $_POST['sma_masuk'] ?? '';
$sma_lulus                  = $_POST['sma_lulus'] ?? '';
$sma_jurusan                = $_POST['sma_jurusan'] ?? '';
$pt_nama                    = $_POST['pt_nama'] ?? '';
$pt_masuk                   = $_POST['pt_masuk'] ?? '';
$pt_lulus                   = $_POST['pt_lulus'] ?? '';
$pt_prodi                   = $_POST['pt_prodi'] ?? '';
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
$keluarga_hubungan          = $_POST['keluarga_hubungan'] ?? [];
$keluarga_nama              = $_POST['keluarga_nama'] ?? [];
$keluarga_tgl_lahir         = $_POST['keluarga_tgl_lahir'] ?? [];
$keluarga_usia              = $_POST['keluarga_usia'] ?? [];
$keluarga_pekerjaan         = $_POST['keluarga_pekerjaan'] ?? [];
$riwayat_penyakit           = $_POST['riwayat_penyakit'] ?? '';
$riwayat_alergi             = $_POST['riwayat_alergi'] ?? '';
$merokok                    = $_POST['merokok'] ?? '';
$jumlah_anak                = $_POST['jumlah_anak'] ?? '';
$paspor                     = $_POST['paspor'] ?? '';
$tgl_mulai_bahasa_jepang    = $_POST['tgl_mulai_bahasa_jepang'] ?? '';
$kemahiran_bahasa_jepang    = $_POST['kemahiran_bahasa_jepang'] ?? '';
$skor_bab_dikuasai          = $_POST['skor_bab_dikuasai'] ?? '';
$materi_dikuasai            = $_POST['materi_dikuasai'] ?? [];
$materi_tidak_dikuasai      = $_POST['materi_tidak_dikuasai'] ?? [];
$hobi                       = $_POST['hobi'] ?? '';
$kemampuan                  = $_POST['kemampuan'] ?? '';
$kelemahan                  = $_POST['kelemahan'] ?? '';
$mimpi_jepang               = $_POST['mimpi_jepang'] ?? '';
$visi_setelah_jepang        = $_POST['visi_setelah_jepang'] ?? '';


// Validasi sederhana
if (!$nama || !$jenis_kelamin || !$tempat_lahir || !$tanggal_lahir) {
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
        'foto_3x4',
        'upload_ktp',
        'upload_kk',
        'upload_akta',
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
    $mail->Subject = "Formulir Data Peserta Baru - $nama";
    $body  = "<h2>Data Peserta</h2>";
    $body .= "<p><strong>Nama:</strong> $nama</p>";
    $body .= "<p><strong>Jenis Kelamin:</strong> $jenis_kelamin</p>";
    $body .= "<p><strong>Tempat Lahir:</strong> $tempat_lahir</p>";
    $body .= "<p><strong>Tanggal Lahir:</strong> $tanggal_lahir</p>";
    $body .= "<p><strong>Email:</strong> $email</p>";
    $body .= "<p><strong>Telepon Pribadi:</strong> $nomor_telepon</p>";
    $body .= "<p><strong>Telepon Darurat:</strong> $nomor_darurat</p>";
    $body .= "<h3>Alamat KTP</h3>";
    $body .= "<p>$alamat_jalan, $alamat_kelurahan, $alamat_kecamatan, $alamat_kabupaten, $alamat_provinsi, $kode_pos</p>";
    $body .= "<h3>Alamat Domisili</h3>";
    $body .= "<p>$alamat_jalan_domisili, $alamat_kelurahan_domisili, $alamat_kecamatan_domisili, $alamat_kabupaten_domisili, $alamat_provinsi_domisili, $kode_pos_domisili</p>";
    $body .= "<h3>Identitas Khusus</h3>";
    $body .= "<p>Usia: $usia</p>";
    $body .= "<p>Berat Badan: $berat_badan kg</p>";
    $body .= "<p>Tinggi Badan: $tinggi_badan kg</p>";
    $body .= "<p>Golongan Darah: $gol_darah</p>";
    $body .= "<p>Ukuran Baju: $ukuran_baju</p>";
    $body .= "<p>Ukuran Sepatu: $ukuran_sepatu</p>";
    $body .= "<p>Ukuran Pinggang: $ukuran_pinggang</p>";
    $body .= "<p>Ukuran Panjang Kaki: $ukuran_panjang_kaki</p>";

    $body .= "<p>Bahasa yang dikuasai: ";
    if (!empty($bahasa)) {
        $body .= implode(", ", $bahasa);
    } else {
        $body .= "-";
    }
    $body .= "</p>";

    $body .= "<h3>Riwayat Pendidikan</h3>";
    $body .= "<p>SD/MI: $sd_nama ($sd_masuk - $sd_lulus)</p>";
    $body .= "<p>SMP/MTs: $smp_nama ($smp_masuk - $smp_lulus)</p>";
    $body .= "<p>SMA/Sederajat: $sma_nama ($sma_masuk - $sma_lulus) $sma_jurusan</p>";
    $body .= "<p>PT: $pt_nama ($pt_masuk - $pt_lulus) $pt_prodi</p>";
    $body .= "<h3>Riwayat Pekerjaan</h3>";
    $body .= "<p>Pekerjaan 1: $pekerjaan1_nama ($pekerjaan1_masuk - $pekerjaan1_keluar) - $pekerjaan1_jenis</p>";
    $body .= "<p>Pekerjaan 2: $pekerjaan2_nama ($pekerjaan2_masuk - $pekerjaan2_keluar) - $pekerjaan2_jenis</p>";
    $body .= "<p>Pekerjaan 3: $pekerjaan3_nama ($pekerjaan3_masuk - $pekerjaan3_keluar) - $pekerjaan3_jenis</p>";
    $body .= "<p>Pekerjaan 4: $pekerjaan4_nama ($pekerjaan4_masuk - $pekerjaan4_keluar) - $pekerjaan4_jenis</p>";
    $body .= "<h3>Identitas Pribadi</h3>";
    $body .= "<p>Riwayat Penyakit: $riwayat_penyakit</p>";
    $body .= "<p>Riwayat Alergi: $riwayat_alergi</p>";
    $body .= "<p>Merokok: $merokok</p>";
    $body .= "<p>Jumlah Anak: $jumlah_anak</p>";
    $body .= "<p>Paspor: $paspor</p>";
    $body .= "<p>Mulai Belajar Bahasa Jepang: $tgl_mulai_bahasa_jepang</p>";
    $body .= "<p>Kemahiran Bahasa Jepang: $kemahiran_bahasa_jepang</p>";
    $body .= "<p>Skor/BAB yang dikuasai: $skor_bab_dikuasai</p>";

    $body .= "<p>Materi yang dikuasai: ";
    if (!empty($materi_dikuasai)) {
        $body .= implode(", ", $materi_dikuasai);
    } else {
        $body .= "-";
    }
    $body .= "</p>";

    $body .= "<p>Materi yang tidak dikuasai: ";
    if (!empty($materi_tidak_dikuasai)) {
        $body .= implode(", ", $materi_tidak_dikuasai);
    } else {
        $body .= "-";
    }
    $body .= "</p>";

    $body .= "<p>Hobi: $hobi</p>";
    $body .= "<p>Kemampuan: $kemampuan</p>";
    $body .= "<p>Kelemahan: $kelemahan</p>";
    $body .= "<p>Mimpi di Jepang: $mimpi_jepang</p>";
    $body .= "<p>Visi setelah dari Jepang: $visi_setelah_jepang</p>";

    $body .= "<h3>Susunan Keluarga</h3>";
    $body .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $body .= "<tr>
            <th>No</th>
            <th>Hubungan</th>
            <th>Nama</th>
            <th>Tgl Lahir</th>
            <th>Usia</th>
            <th>Pekerjaan</th>
          </tr>";

    foreach ($keluarga_hubungan as $i => $hubungan) {
        $body .= "<tr>
                <td>" . ($i + 1) . "</td>
                <td>" . $hubungan . "</td>
                <td>" . $keluarga_nama[$i] . "</td>
                <td>" . $keluarga_tgl_lahir[$i] . "</td>
                <td>" . $keluarga_usia[$i] . "</td>
                <td>" . $keluarga_pekerjaan[$i] . "</td>
              </tr>";
    }

    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    $mail->send();

    http_response_code(200);
    echo "OK";
} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
}
