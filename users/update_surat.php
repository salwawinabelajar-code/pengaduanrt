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
$query = "SELECT * FROM pengajuan_surat WHERE id = ? AND user_id = ?";
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

$jenis_surat = mysqli_real_escape_string($conn, $_POST['jenis_surat'] ?? '');
$keperluan = mysqli_real_escape_string($conn, $_POST['keperluan'] ?? '');
$keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

if (empty($jenis_surat) || empty($keperluan)) {
    echo json_encode(['success' => false, 'message' => 'Jenis surat dan keperluan harus diisi']);
    exit();
}

$file_pendukung = $old['file_pendukung'];
if (isset($_FILES['file_pendukung']) && $_FILES['file_pendukung']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file_pendukung'];
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        exit();
    }
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit();
    }

    if (!empty($old['file_pendukung']) && file_exists('../uploads/surat/' . $old['file_pendukung'])) {
        unlink('../uploads/surat/' . $old['file_pendukung']);
    }

    $filename = 'surat_' . time() . '_' . uniqid() . '.' . $ext;
    $upload_dir = '../uploads/surat/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $target = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $file_pendukung = $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal upload file']);
        exit();
    }
}

$update = "UPDATE pengajuan_surat SET jenis_surat=?, keperluan=?, keterangan=?, file_pendukung=? WHERE id=? AND user_id=?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "ssssii", $jenis_surat, $keperluan, $keterangan, $file_pendukung, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>