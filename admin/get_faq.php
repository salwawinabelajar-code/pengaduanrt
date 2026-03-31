<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$query = "SELECT * FROM bantuan WHERE id = $id";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Data not found']);
    exit();
}

$data = mysqli_fetch_assoc($result);
echo json_encode(['success' => true, 'data' => $data]);
?>