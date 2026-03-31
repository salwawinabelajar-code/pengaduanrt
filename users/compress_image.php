<?php
/**
 * compress_image.php
 * Menampilkan atau mengunduh gambar galeri dalam versi terkompres.
 * Parameter:
 *   - path: jalur relatif dari folder uploads, contoh "galeri/foto.jpg"
 *   - download: (opsional) jika ada, file akan di-download (attachment)
 */

// Konfigurasi
$uploads_dir = __DIR__ . '/../uploads/';         // path absolut ke folder uploads
$compressed_dir = $uploads_dir . 'compressed/'; // folder compressed
$quality = 75;                                   // kualitas kompresi (1-100)
$max_width = 1200;                               // lebar maksimal (opsional, resize jika lebih besar)

// Pastikan folder compressed ada
if (!is_dir($compressed_dir)) {
    mkdir($compressed_dir, 0755, true);
}

// Ambil parameter path
$path = isset($_GET['path']) ? $_GET['path'] : '';
if (empty($path)) {
    http_response_code(400);
    die('Parameter path diperlukan');
}

// Validasi path: tidak boleh mengandung .. (directory traversal)
if (strpos($path, '..') !== false) {
    http_response_code(403);
    die('Akses ditolak');
}

// Path asli file
$original_file = $uploads_dir . $path;
if (!file_exists($original_file)) {
    http_response_code(404);
    die('File tidak ditemukan');
}

// Nama file compressed (gunakan path yang sama, tetapi dalam folder compressed dengan underscore)
$compressed_file = $compressed_dir . str_replace('/', '_', $path); // flatten path

// Jika file compressed sudah ada, langsung tampilkan atau download
if (file_exists($compressed_file)) {
    $mime = mime_content_type($compressed_file);
    header('Content-Type: ' . $mime);
    if (isset($_GET['download']) && $_GET['download'] == '1') {
        $original_filename = basename($path);
        header('Content-Disposition: attachment; filename="' . $original_filename . '"');
    }
    readfile($compressed_file);
    exit;
}

// Jika belum ada, lakukan kompresi
$image_info = getimagesize($original_file);
if (!$image_info) {
    http_response_code(500);
    die('Format gambar tidak dikenali');
}

$mime = $image_info['mime'];
$src_width = $image_info[0];
$src_height = $image_info[1];

// Buka gambar asli sesuai tipe
switch ($mime) {
    case 'image/jpeg':
        $src = imagecreatefromjpeg($original_file);
        break;
    case 'image/png':
        $src = imagecreatefrompng($original_file);
        break;
    case 'image/webp':
        $src = imagecreatefromwebp($original_file);
        break;
    default:
        http_response_code(415);
        die('Tipe gambar tidak didukung');
}

// Resize jika lebar melebihi max_width
if ($src_width > $max_width) {
    $new_width = $max_width;
    $new_height = intval($src_height * ($max_width / $src_width));
    $dst = imagecreatetruecolor($new_width, $new_height);
    
    // Untuk PNG, pertahankan transparansi
    if ($mime == 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
    imagedestroy($src);
    $src = $dst;
}

// Simpan hasil kompresi
switch ($mime) {
    case 'image/jpeg':
        imagejpeg($src, $compressed_file, $quality);
        break;
    case 'image/png':
        $png_quality = 8; // compression level 0-9
        imagepng($src, $compressed_file, $png_quality);
        break;
    case 'image/webp':
        imagewebp($src, $compressed_file, $quality);
        break;
}
imagedestroy($src);

// Tampilkan atau download file compressed
header('Content-Type: ' . $mime);
if (isset($_GET['download']) && $_GET['download'] == '1') {
    $original_filename = basename($path);
    header('Content-Disposition: attachment; filename="' . $original_filename . '"');
}
readfile($compressed_file);
exit;