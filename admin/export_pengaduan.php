<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil parameter filter dari URL
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "1=1";
if (!empty($filter_status)) {
    $where .= " AND p.status = '$filter_status'";
}
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.judul LIKE '%$search%' OR p.deskripsi LIKE '%$search%' OR p.lokasi LIKE '%$search%')";
}
if (!empty($start_date)) {
    $where .= " AND DATE(p.tanggal) >= '$start_date'";
}
if (!empty($end_date)) {
    $where .= " AND DATE(p.tanggal) <= '$end_date'";
}

$query = "SELECT p.*, u.nama, u.username 
          FROM pengaduan p 
          JOIN users u ON p.user_id = u.id 
          WHERE $where 
          ORDER BY p.tanggal DESC";
$result = mysqli_query($conn, $query);

// Set header untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pengaduan_' . date('Y-m-d') . '.csv"');

// Tambahkan BOM untuk UTF-8 agar Excel membaca encoding dengan benar
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Gunakan delimiter titik koma (;) karena Excel Indonesia biasanya menggunakan titik koma
$delimiter = ';';

// Header kolom
fputcsv($output, ['ID', 'Warga', 'Username', 'Judul', 'Kategori', 'Lokasi', 'Deskripsi', 'Tanggal', 'Status', 'Urgensi', 'Foto'], $delimiter);

while ($row = mysqli_fetch_assoc($result)) {
    // Bersihkan teks dari karakter yang dapat mengganggu (misalnya kutip)
    $row['judul'] = str_replace('"', '""', $row['judul']);
    $row['deskripsi'] = str_replace('"', '""', $row['deskripsi']);
    $row['lokasi'] = str_replace('"', '""', $row['lokasi']);
    
    fputcsv($output, [
        $row['id'],
        $row['nama'],
        $row['username'],
        $row['judul'],
        $row['kategori'],
        $row['lokasi'],
        $row['deskripsi'],
        $row['tanggal'],
        $row['status'],
        $row['urgensi'],
        $row['foto']
    ], $delimiter);
}

fclose($output);
exit();
?>