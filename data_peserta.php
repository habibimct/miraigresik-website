<?php
/**
 * LPK Mirai Gresik - Data Peserta Form Handler
 * Menerima formulir multipart, memvalidasi data dan lampiran, lalu mengirim email.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: text/plain; charset=UTF-8');

/** Kirim respons teks singkat dan hentikan proses. */
function respond(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo $message;
    exit;
}

/** Ubah nilai ukuran PHP (mis. 32M) menjadi byte. */
function iniSizeToBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    switch ($unit) {
        case 'g':
            $number *= 1024;
            // lanjut ke perkalian MB di bawah
        case 'm':
            $number *= 1024;
            // lanjut ke perkalian KB di bawah
        case 'k':
            $number *= 1024;
            break;
        default:
            return (int) $number;
    }

    return (int) $number;
}

/** Ambil satu field teks; input array/object ditolak. */
function postText(string $key): string
{
    $value = $_POST[$key] ?? '';

    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }

    return trim((string) $value);
}

/** Ambil field array dan pastikan setiap elemen berupa string. */
function postStringArray(string $key, bool $unique = true, bool $preserveEmpty = false): array
{
    $value = $_POST[$key] ?? [];

    if (!is_array($value)) {
        respond(400, 'Format pilihan formulir tidak valid. Silakan muat ulang halaman.');
    }

    $result = [];
    foreach ($value as $item) {
        if (!is_string($item)) {
            respond(400, 'Format pilihan formulir tidak valid. Silakan muat ulang halaman.');
        }

        $item = trim($item);
        if ($preserveEmpty || $item !== '') {
            $result[] = $item;
        }
    }

    return $unique ? array_values(array_unique($result)) : array_values($result);
}

/** Pastikan daftar pilihan hanya berisi opsi yang dikenal. */
function validateChoiceArray(string $label, array $values, array $allowed): array
{
    if (count($values) === 0) {
        respond(400, $label . ' wajib memiliki minimal satu pilihan.');
    }

    foreach ($values as $value) {
        if (!in_array($value, $allowed, true)) {
            respond(400, 'Ditemukan pilihan ' . $label . ' yang tidak dikenal. Muat ulang formulir.');
        }
    }

    return $values;
}

/** Escape semua nilai sebelum dimasukkan ke HTML email. */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format baris tabel email. */
function emailRow(string $label, string $value): string
{
    $safeValue = $value === '' ? '-' : nl2br(h($value), false);

    return '<tr>'
        . '<th style="text-align:left;vertical-align:top;padding:7px;border:1px solid #ddd;background:#f7f7f7;width:32%;">'
        . h($label)
        . '</th>'
        . '<td style="padding:7px;border:1px solid #ddd;">' . $safeValue . '</td>'
        . '</tr>';
}

/** Format pesan upload berdasarkan kode error PHP. */
function uploadErrorMessage(int $error): string
{
    switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran file melebihi batas server.';
        case UPLOAD_ERR_PARTIAL:
            return 'File hanya terunggah sebagian. Silakan pilih ulang.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder sementara upload server tidak tersedia.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server gagal menyimpan file sementara.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload dihentikan oleh konfigurasi server.';
        default:
            return 'Terjadi kesalahan saat mengunggah file.';
    }
}

// -------------------------------------------------------------------------
// 1. Metode request dan batas ukuran request
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, 'Method tidak diizinkan.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxBytes = iniSizeToBytes((string) ini_get('post_max_size'));

if (
    $contentLength > 0
    && $postMaxBytes > 0
    && $contentLength > $postMaxBytes
    && empty($_POST)
    && empty($_FILES)
) {
    respond(413, 'Total ukuran formulir dan dokumen melebihi batas upload server. Kurangi ukuran file lalu coba kembali.');
}

// -------------------------------------------------------------------------
// 2. Ambil data teks
// -------------------------------------------------------------------------
$textKeys = [
    'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'email',
    'nomor_telepon', 'nomor_darurat',
    'alamat_jalan', 'alamat_kelurahan', 'alamat_kecamatan', 'alamat_kabupaten',
    'alamat_provinsi', 'kode_pos',
    'alamat_jalan_domisili', 'alamat_kelurahan_domisili', 'alamat_kecamatan_domisili',
    'alamat_kabupaten_domisili', 'alamat_provinsi_domisili', 'kode_pos_domisili',
    'usia', 'berat_badan', 'tinggi_badan', 'gol_darah', 'ukuran_baju',
    'ukuran_sepatu', 'ukuran_pinggang', 'ukuran_panjang_kaki',
    'sd_nama', 'sd_masuk', 'sd_lulus',
    'smp_nama', 'smp_masuk', 'smp_lulus',
    'sma_nama', 'sma_masuk', 'sma_lulus', 'sma_jurusan',
    'pt_nama', 'pt_masuk', 'pt_lulus', 'pt_prodi',
    'pekerjaan1_nama', 'pekerjaan1_masuk', 'pekerjaan1_keluar', 'pekerjaan1_jenis',
    'pekerjaan2_nama', 'pekerjaan2_masuk', 'pekerjaan2_keluar', 'pekerjaan2_jenis',
    'pekerjaan3_nama', 'pekerjaan3_masuk', 'pekerjaan3_keluar', 'pekerjaan3_jenis',
    'pekerjaan4_nama', 'pekerjaan4_masuk', 'pekerjaan4_keluar', 'pekerjaan4_jenis',
    'riwayat_penyakit', 'riwayat_alergi', 'merokok', 'jumlah_anak', 'paspor',
    'tgl_mulai_bahasa_jepang', 'kemahiran_bahasa_jepang', 'skor_bab_dikuasai',
    'hobi', 'kemampuan', 'kelemahan', 'mimpi_jepang', 'visi_setelah_jepang'
];

$data = [];
foreach ($textKeys as $key) {
    $data[$key] = postText($key);
}

// Samakan validasi server dengan field HTML yang memang bertanda required.
$requiredTextFields = [
    'nama' => 'Nama',
    'jenis_kelamin' => 'Jenis kelamin',
    'tempat_lahir' => 'Tempat lahir',
    'tanggal_lahir' => 'Tanggal lahir',
    'email' => 'Email',
    'nomor_telepon' => 'Nomor telepon',
    'nomor_darurat' => 'Nomor darurat',
    'alamat_jalan' => 'Alamat jalan KTP',
    'alamat_kecamatan' => 'Kecamatan KTP',
    'alamat_provinsi' => 'Provinsi KTP',
    'kode_pos' => 'Kode pos KTP',
    'kode_pos_domisili' => 'Kode pos domisili',
    'usia' => 'Usia',
    'berat_badan' => 'Berat badan',
    'tinggi_badan' => 'Tinggi badan',
    'gol_darah' => 'Golongan darah',
    'ukuran_baju' => 'Ukuran baju',
    'ukuran_sepatu' => 'Ukuran sepatu',
    'ukuran_pinggang' => 'Ukuran pinggang',
    'ukuran_panjang_kaki' => 'Panjang kaki',
    'sd_nama' => 'Nama sekolah SD/MI',
    'sd_masuk' => 'Tanggal masuk SD/MI',
    'sd_lulus' => 'Tanggal lulus SD/MI',
    'riwayat_penyakit' => 'Riwayat penyakit',
    'riwayat_alergi' => 'Riwayat alergi',
    'merokok' => 'Status merokok',
    'jumlah_anak' => 'Jumlah anak',
    'paspor' => 'Paspor',
    'tgl_mulai_bahasa_jepang' => 'Tanggal mulai belajar bahasa Jepang',
    'kemahiran_bahasa_jepang' => 'Kemahiran bahasa Jepang',
    'skor_bab_dikuasai' => 'Skor/bab yang dikuasai',
    'hobi' => 'Hobi',
    'kemampuan' => 'Kemampuan/kekuatan',
    'kelemahan' => 'Kelemahan',
    'mimpi_jepang' => 'Mimpi di Jepang',
    'visi_setelah_jepang' => 'Visi setelah kembali dari Jepang'
];

foreach ($requiredTextFields as $key => $label) {
    if ($data[$key] === '') {
        respond(400, $label . ' wajib diisi.');
    }
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    respond(400, 'Format email peserta tidak valid.');
}

// Validasi pilihan select agar nilai yang diterima sesuai dengan formulir.
$allowedSelects = [
    'jenis_kelamin' => ['Laki-laki', 'Perempuan'],
    'gol_darah' => ['A', 'B', 'AB', 'O'],
    'ukuran_baju' => ['S', 'M', 'L', 'XL/LL', 'XXL/LLL'],
    'merokok' => ['Tidak Merokok', 'Merokok'],
    'kemahiran_bahasa_jepang' => ['Minna no Nihongo', 'JFT Basic A2', 'JLPT N4', 'JLPT N5']
];

foreach ($allowedSelects as $key => $allowedValues) {
    if (!in_array($data[$key], $allowedValues, true)) {
        respond(400, 'Pilihan ' . str_replace('_', ' ', $key) . ' tidak valid.');
    }
}

// -------------------------------------------------------------------------
// 3. Validasi checkbox (wajib, whitelist, dan saling eksklusif)
// -------------------------------------------------------------------------
$allowedLanguages = [
    'Indonesia', 'Jepang', 'Inggris', 'Prancis', 'Arab', 'China', 'Rusia', 'Spanyol'
];
$allowedMaterials = [
    'Kosa-kata', 'Kanji', 'Percakapan',
    'Pemahaman mendengarkan', 'Tata-bahasa', 'Kosakata karakter'
];

$bahasa = validateChoiceArray(
    'Bahasa yang dikuasai',
    postStringArray('bahasa'),
    $allowedLanguages
);

$materiDikuasai = validateChoiceArray(
    'Materi yang dikuasai',
    postStringArray('materi_dikuasai'),
    $allowedMaterials
);

$materiTidakDikuasai = validateChoiceArray(
    'Materi yang tidak dikuasai',
    postStringArray('materi_tidak_dikuasai'),
    $allowedMaterials
);

$conflictingMaterials = array_values(array_intersect($materiDikuasai, $materiTidakDikuasai));
if (count($conflictingMaterials) > 0) {
    respond(400, 'Materi yang sama tidak boleh dipilih pada kedua kelompok. Periksa kembali pilihan Anda.');
}

// -------------------------------------------------------------------------
// 4. Validasi susunan keluarga (array harus sejajar dan lengkap)
// -------------------------------------------------------------------------
$familyFields = [
    'keluarga_hubungan' => 'Hubungan keluarga',
    'keluarga_nama' => 'Nama anggota keluarga',
    'keluarga_tgl_lahir' => 'Tanggal lahir anggota keluarga',
    'keluarga_usia' => 'Usia anggota keluarga',
    'keluarga_pekerjaan' => 'Pekerjaan anggota keluarga'
];

$family = [];
foreach ($familyFields as $key => $label) {
    // Repeated family values are valid (for example two children), so do not deduplicate.
    $family[$key] = postStringArray($key, false, true);
}

$familyCount = count($family['keluarga_hubungan']);
if ($familyCount < 1) {
    respond(400, 'Minimal satu anggota keluarga harus diisi.');
}

foreach ($family as $key => $values) {
    if (count($values) !== $familyCount) {
        respond(400, 'Data susunan keluarga tidak lengkap atau tidak sejajar. Periksa kembali setiap anggota keluarga.');
    }
}

$allowedRelationships = [
    'Ayah', 'Ibu', 'Kakak laki-laki', 'Kakak Perempuan',
    'Adik laki-laki', 'Adik Perempuan', 'Kakek', 'Nenek',
    'Suami', 'Istri', 'Anak laki-laki', 'Anak perempuan',
    'Sepupu laki-laki', 'Sepupu perempuan'
];

for ($i = 0; $i < $familyCount; $i++) {
    if (!in_array($family['keluarga_hubungan'][$i], $allowedRelationships, true)) {
        respond(400, 'Hubungan anggota keluarga ke-' . ($i + 1) . ' tidak valid.');
    }

    foreach (['keluarga_nama', 'keluarga_tgl_lahir', 'keluarga_usia', 'keluarga_pekerjaan'] as $key) {
        if (trim($family[$key][$i]) === '') {
            respond(400, 'Data anggota keluarga ke-' . ($i + 1) . ' belum lengkap.');
        }
    }
}

// -------------------------------------------------------------------------
// 5. Validasi dokumen upload
// -------------------------------------------------------------------------
$uploadLabels = [
    'foto_3x4' => 'Foto 3x4',
    'upload_ktp' => 'KTP',
    'upload_kk' => 'Kartu Keluarga',
    'upload_akta' => 'Akta Kelahiran',
    'sd_ijazah' => 'Ijazah SD/MI',
    'smp_ijazah' => 'Ijazah SMP/MTs',
    'sma_ijazah' => 'Ijazah SMA/Sederajat',
    'pt_ijazah' => 'Ijazah Perguruan Tinggi'
];

$requiredUploads = ['foto_3x4', 'upload_ktp', 'upload_kk', 'upload_akta', 'sd_ijazah'];
$attachments = [];
$allowedMimeTypes = [
    'image/jpeg' => ['extension' => 'jpg', 'max' => 20 * 1024 * 1024],
    'image/png' => ['extension' => 'png', 'max' => 20 * 1024 * 1024],
    'image/webp' => ['extension' => 'webp', 'max' => 20 * 1024 * 1024],
    'application/pdf' => ['extension' => 'pdf', 'max' => 10 * 1024 * 1024]
];

if (!class_exists('finfo')) {
    respond(500, 'Fitur pemeriksaan tipe file tidak tersedia pada server. Hubungi admin.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

foreach ($uploadLabels as $field => $label) {
    $isRequired = in_array($field, $requiredUploads, true);

    if (!isset($_FILES[$field])) {
        if ($isRequired) {
            respond(400, 'Dokumen ' . $label . ' wajib diunggah.');
        }
        continue;
    }

    $upload = $_FILES[$field];
    if (
        !is_array($upload)
        || !isset($upload['error'])
        || is_array($upload['error'])
        || (isset($upload['tmp_name']) && !is_string($upload['tmp_name']))
        || (isset($upload['name']) && !is_string($upload['name']))
        || (isset($upload['size']) && !is_numeric($upload['size']))
    ) {
        respond(400, 'Format upload dokumen ' . $label . ' tidak valid.');
    }

    $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        if ($isRequired) {
            respond(400, 'Dokumen ' . $label . ' wajib diunggah.');
        }
        continue;
    }

    if ($error !== UPLOAD_ERR_OK) {
        respond(400, 'Dokumen ' . $label . ': ' . uploadErrorMessage($error));
    }

    $tmpName = (string) ($upload['tmp_name'] ?? '');
    $originalName = (string) ($upload['name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName) || !is_file($tmpName)) {
        respond(400, 'File ' . $label . ' tidak dapat diverifikasi. Silakan pilih ulang.');
    }

    $actualSize = filesize($tmpName);
    $fileSize = $actualSize === false ? 0 : (int) $actualSize;
    if ($fileSize < 1) {
        respond(400, 'File ' . $label . ' kosong. Silakan pilih ulang.');
    }

    $mimeType = $finfo->file($tmpName);
    if (!is_string($mimeType) || !isset($allowedMimeTypes[$mimeType])) {
        respond(400, 'Format dokumen ' . $label . ' tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF.');
    }

    if ($field === 'foto_3x4' && $mimeType === 'application/pdf') {
        respond(400, 'Foto 3x4 harus berupa file gambar, bukan PDF.');
    }

    $maxSize = $allowedMimeTypes[$mimeType]['max'];
    if ($fileSize > $maxSize) {
        $maxMb = (int) ($maxSize / 1024 / 1024);
        respond(400, 'Ukuran dokumen ' . $label . ' maksimal ' . $maxMb . ' MB.');
    }

    $baseName = pathinfo(basename($originalName), PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName);
    $safeBaseName = trim((string) $safeBaseName, '_-');
    if ($safeBaseName === '') {
        $safeBaseName = 'dokumen';
    }

    $extension = $allowedMimeTypes[$mimeType]['extension'];
    $attachmentName = $label . '_' . $safeBaseName . '.' . $extension;

    $attachments[] = [
        'path' => $tmpName,
        'name' => $attachmentName,
        'mime' => $mimeType
    ];
}

// -------------------------------------------------------------------------
// 6. Siapkan isi email dengan seluruh nilai di-escape
// -------------------------------------------------------------------------
$sections = [
    'Data Peserta' => [
        'nama' => 'Nama',
        'jenis_kelamin' => 'Jenis Kelamin',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'email' => 'Email',
        'nomor_telepon' => 'Nomor Telepon',
        'nomor_darurat' => 'Nomor Darurat'
    ],
    'Alamat KTP' => [
        'alamat_jalan' => 'Jalan',
        'alamat_kelurahan' => 'Kelurahan/Desa',
        'alamat_kecamatan' => 'Kecamatan',
        'alamat_kabupaten' => 'Kabupaten/Kota',
        'alamat_provinsi' => 'Provinsi',
        'kode_pos' => 'Kode Pos'
    ],
    'Alamat Domisili' => [
        'alamat_jalan_domisili' => 'Jalan',
        'alamat_kelurahan_domisili' => 'Kelurahan/Desa',
        'alamat_kecamatan_domisili' => 'Kecamatan',
        'alamat_kabupaten_domisili' => 'Kabupaten/Kota',
        'alamat_provinsi_domisili' => 'Provinsi',
        'kode_pos_domisili' => 'Kode Pos'
    ],
    'Identitas Khusus' => [
        'usia' => 'Usia',
        'berat_badan' => 'Berat Badan (kg)',
        'tinggi_badan' => 'Tinggi Badan (cm)',
        'gol_darah' => 'Golongan Darah',
        'ukuran_baju' => 'Ukuran Baju',
        'ukuran_sepatu' => 'Ukuran Sepatu (cm)',
        'ukuran_pinggang' => 'Ukuran Celana / Pinggang (cm)',
        'ukuran_panjang_kaki' => 'Panjang Kaki (cm)'
    ],
    'Riwayat Pendidikan' => [
        'sd_nama' => 'SD/MI',
        'sd_masuk' => 'Tanggal Masuk SD/MI',
        'sd_lulus' => 'Tanggal Lulus SD/MI',
        'smp_nama' => 'SMP/MTs',
        'smp_masuk' => 'Tanggal Masuk SMP/MTs',
        'smp_lulus' => 'Tanggal Lulus SMP/MTs',
        'sma_nama' => 'SMA/Sederajat',
        'sma_masuk' => 'Tanggal Masuk SMA/Sederajat',
        'sma_lulus' => 'Tanggal Lulus SMA/Sederajat',
        'sma_jurusan' => 'Jurusan SMA/Sederajat',
        'pt_nama' => 'Perguruan Tinggi',
        'pt_masuk' => 'Tanggal Masuk Perguruan Tinggi',
        'pt_lulus' => 'Tanggal Lulus Perguruan Tinggi',
        'pt_prodi' => 'Program Studi'
    ],
    'Riwayat Pekerjaan' => [
        'pekerjaan1_nama' => 'Perusahaan 1',
        'pekerjaan1_masuk' => 'Perusahaan 1 - Tanggal Masuk',
        'pekerjaan1_keluar' => 'Perusahaan 1 - Tanggal Keluar',
        'pekerjaan1_jenis' => 'Perusahaan 1 - Jenis Pekerjaan',
        'pekerjaan2_nama' => 'Perusahaan 2',
        'pekerjaan2_masuk' => 'Perusahaan 2 - Tanggal Masuk',
        'pekerjaan2_keluar' => 'Perusahaan 2 - Tanggal Keluar',
        'pekerjaan2_jenis' => 'Perusahaan 2 - Jenis Pekerjaan',
        'pekerjaan3_nama' => 'Perusahaan 3',
        'pekerjaan3_masuk' => 'Perusahaan 3 - Tanggal Masuk',
        'pekerjaan3_keluar' => 'Perusahaan 3 - Tanggal Keluar',
        'pekerjaan3_jenis' => 'Perusahaan 3 - Jenis Pekerjaan',
        'pekerjaan4_nama' => 'Perusahaan 4',
        'pekerjaan4_masuk' => 'Perusahaan 4 - Tanggal Masuk',
        'pekerjaan4_keluar' => 'Perusahaan 4 - Tanggal Keluar',
        'pekerjaan4_jenis' => 'Perusahaan 4 - Jenis Pekerjaan'
    ],
    'Data Pribadi dan Bahasa Jepang' => [
        'riwayat_penyakit' => 'Riwayat Penyakit',
        'riwayat_alergi' => 'Riwayat Alergi',
        'merokok' => 'Merokok',
        'jumlah_anak' => 'Jumlah Anak',
        'paspor' => 'Paspor',
        'tgl_mulai_bahasa_jepang' => 'Tanggal Mulai Belajar Bahasa Jepang',
        'kemahiran_bahasa_jepang' => 'Kemahiran Bahasa Jepang',
        'skor_bab_dikuasai' => 'Skor/Bab yang Dikuasai',
        'hobi' => 'Hobi',
        'kemampuan' => 'Kemampuan/Kekuatan',
        'kelemahan' => 'Kelemahan',
        'mimpi_jepang' => 'Mimpi di Jepang',
        'visi_setelah_jepang' => 'Visi Setelah Kembali dari Jepang'
    ]
];

$body = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
$body .= '<h2>Formulir Data Peserta Baru - LPK Mirai Gresik</h2>';

foreach ($sections as $sectionTitle => $fields) {
    $body .= '<h3>' . h($sectionTitle) . '</h3>';
    $body .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:850px;">';
    foreach ($fields as $key => $label) {
        $body .= emailRow($label, $data[$key] ?? '');
    }
    $body .= '</table>';
}

$body .= '<h3>Bahasa yang Dikuasai</h3>';
$body .= '<p>' . h(implode(', ', $bahasa)) . '</p>';

$body .= '<h3>Materi Bahasa Jepang</h3>';
$body .= '<p><strong>Materi yang dikuasai:</strong> ' . h(implode(', ', $materiDikuasai)) . '</p>';
$body .= '<p><strong>Materi yang tidak dikuasai:</strong> ' . h(implode(', ', $materiTidakDikuasai)) . '</p>';

$body .= '<h3>Susunan Keluarga</h3>';
$body .= '<table cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:850px;">';
$body .= '<thead><tr>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">No</th>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">Hubungan</th>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">Nama</th>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">Tanggal Lahir</th>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">Usia</th>'
    . '<th style="border:1px solid #ddd;background:#f7f7f7;">Pekerjaan</th>'
    . '</tr></thead><tbody>';

for ($i = 0; $i < $familyCount; $i++) {
    $body .= '<tr>'
        . '<td style="border:1px solid #ddd;">' . ($i + 1) . '</td>'
        . '<td style="border:1px solid #ddd;">' . h($family['keluarga_hubungan'][$i]) . '</td>'
        . '<td style="border:1px solid #ddd;">' . h($family['keluarga_nama'][$i]) . '</td>'
        . '<td style="border:1px solid #ddd;">' . h($family['keluarga_tgl_lahir'][$i]) . '</td>'
        . '<td style="border:1px solid #ddd;">' . h($family['keluarga_usia'][$i]) . '</td>'
        . '<td style="border:1px solid #ddd;">' . h($family['keluarga_pekerjaan'][$i]) . '</td>'
        . '</tr>';
}

$body .= '</tbody></table>';
$body .= '<h3>Lampiran</h3><ul>';
foreach ($attachments as $attachment) {
    $body .= '<li>' . h($attachment['name']) . '</li>';
}
$body .= '</ul></body></html>';

// -------------------------------------------------------------------------
// 7. Konfigurasi SMTP dan pengiriman email
// -------------------------------------------------------------------------
try {
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    $configPath = __DIR__ . '/config/config.php';

    if (!is_file($autoloadPath)) {
        throw new RuntimeException('Composer autoload tidak ditemukan.');
    }
    if (!is_file($configPath)) {
        throw new RuntimeException('Konfigurasi SMTP tidak ditemukan.');
    }

    require_once $autoloadPath;

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Format konfigurasi SMTP tidak valid.');
    }

    foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT'] as $requiredConfig) {
        if (!isset($config[$requiredConfig]) || (string) $config[$requiredConfig] === '') {
            throw new RuntimeException('Konfigurasi SMTP tidak lengkap: ' . $requiredConfig);
        }
    }

    $smtpUser = (string) $config['SMTP_USER'];
    if (!filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Alamat SMTP_USER tidak valid.');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = (string) $config['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $config['SMTP_PORT'];
    $mail->Timeout = 30;
    $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);

    // Gunakan akun SMTP sebagai pengirim yang terautentikasi.
    $mail->setFrom($smtpUser, 'LPK Mirai Gresik');
    $mail->addAddress('admin@miraigresik.com', 'Admin LPK Mirai Gresik');
    $mail->addReplyTo($data['email'], $data['nama']);

    $safeSubjectName = str_replace(["\r", "\n"], ' ', $data['nama']);
    $mail->Subject = 'Formulir Data Peserta Baru - ' . $safeSubjectName;
    $mail->Body = $body;
    $mail->AltBody = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    foreach ($attachments as $attachment) {
        $mail->addAttachment(
            $attachment['path'],
            $attachment['name'],
            PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
            $attachment['mime']
        );
    }

    $mail->send();

    // UploadCore mengharapkan respons persis "OK" pada sukses.
    respond(200, 'OK');
} catch (Throwable $exception) {
    $errorDetails = isset($mail) && $mail instanceof PHPMailer\PHPMailer\PHPMailer
        ? $mail->ErrorInfo
        : '';

    error_log(
        '[data_peserta.php] ' . $exception->getMessage()
        . ($errorDetails !== '' ? ' | PHPMailer: ' . $errorDetails : '')
    );

    respond(500, 'Data belum berhasil dikirim ke email admin. Silakan coba kembali beberapa saat lagi atau hubungi admin LPK Mirai.');
}
