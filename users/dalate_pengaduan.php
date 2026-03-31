<?php
session_start();
require_once(__DIR__ . '/../config/db.php'); // Sesuaikan path jika perlu

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Anda harus login']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Pastikan request adalah DELETE atau POST dengan parameter
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && !isset($_POST['_method']) && $_POST['_method'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

// Ambil ID dari query string (DELETE) atau POST
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $delete_vars);
    $id = isset($delete_vars['id']) ? (int)$delete_vars['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
} else {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
}

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
    exit();
}

// Gunakan prepared statement untuk keamanan
$query = "SELECT * FROM pengaduan WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pengaduan = mysqli_fetch_assoc($result);

if (!$pengaduan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda']);
    exit();
}

// Hapus file foto jika ada
if (!empty($pengaduan['foto'])) {
    $file_path = '../' . $pengaduan['foto']; // Sesuaikan dengan path upload
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Hapus data dari database
$delete_query = "DELETE FROM pengaduan WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $id);
$success = mysqli_stmt_execute($delete_stmt);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Pengaduan berhasil dihapus']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
}
?>