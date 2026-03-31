<?php
session_start();
require_once(__DIR__ . '/../config/db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

// Ambil data lama
$query = "SELECT * FROM pengaduan WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$old = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$old) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit();
}

$judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
$lokasi = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
$kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
$urgensi = mysqli_real_escape_string($conn, $_POST['urgensi'] ?? 'rendah');

if (empty($judul) || empty($deskripsi)) {
    echo json_encode(['success' => false, 'message' => 'Judul dan deskripsi harus diisi']);
    exit();
}

$foto = $old['foto'];
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto'];
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format foto harus JPG/PNG']);
        exit();
    }
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Ukuran foto maksimal 2MB']);
        exit();
    }

    if (!empty($old['foto']) && file_exists('../' . $old['foto'])) {
        unlink('../' . $old['foto']);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'pengaduan_' . time() . '_' . uniqid() . '.' . $ext;
    $upload_dir = '../uploads/pengaduan/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $target = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $foto = 'uploads/pengaduan/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal upload foto']);
        exit();
    }
}

$update = "UPDATE pengaduan SET judul=?, deskripsi=?, lokasi=?, kategori=?, urgensi=?, foto=? WHERE id=? AND user_id=?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "ssssssii", $judul, $deskripsi, $lokasi, $kategori, $urgensi, $foto, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>