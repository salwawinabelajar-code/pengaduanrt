<?php
require_once(__DIR__ . '/../config/db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pengaduan_id = $_GET['id'] ?? 0;

$query = "SELECT * FROM pengaduan WHERE id = '$pengaduan_id' AND user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $pengaduan = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'data' => $pengaduan]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pengaduan tidak ditemukan']);
}
?>