<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

header('Content-Type: application/json');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

// Ambil data KK menggunakan prepared statement
$query_kk = "SELECT * FROM kartu_keluarga WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_kk);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result_kk = mysqli_stmt_get_result($stmt);
if (!$result_kk || mysqli_num_rows($result_kk) == 0) {
    echo json_encode(['success' => false, 'message' => 'Data KK tidak ditemukan']);
    exit();
}
$kk = mysqli_fetch_assoc($result_kk);

// Ambil anggota keluarga
$query_anggota = "SELECT * FROM anggota_keluarga WHERE kk_id = ? ORDER BY status_keluarga";
$stmt = mysqli_prepare($conn, $query_anggota);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result_anggota = mysqli_stmt_get_result($stmt);
$anggota = [];
while ($row = mysqli_fetch_assoc($result_anggota)) {
    $anggota[] = $row;
}

echo json_encode(['success' => true, 'kk' => $kk, 'anggota' => $anggota]);
mysqli_close($conn);
?>