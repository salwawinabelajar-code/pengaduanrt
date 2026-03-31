<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "1=1";
if (!empty($filter_status)) $where .= " AND s.status = '$filter_status'";
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.jenis_surat LIKE '%$search%' OR s.keperluan LIKE '%$search%' OR s.keterangan LIKE '%$search%')";
}
if (!empty($start_date)) $where .= " AND DATE(s.tanggal_pengajuan) >= '$start_date'";
if (!empty($end_date)) $where .= " AND DATE(s.tanggal_pengajuan) <= '$end_date'";

$query = "SELECT s.*, u.nama, u.username FROM pengajuan_surat s JOIN users u ON s.user_id = u.id WHERE $where ORDER BY s.tanggal_pengajuan DESC";
$result = mysqli_query($conn, $query);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="surat_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM

$output = fopen('php://output', 'w');
$delimiter = ';';
fputcsv($output, ['ID', 'Warga', 'Username', 'Jenis Surat', 'Keperluan', 'Keterangan', 'Tanggal Pengajuan', 'Status', 'Nomor Surat', 'File Pendukung', 'File Hasil'], $delimiter);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'], $row['nama'], $row['username'], $row['jenis_surat'],
        $row['keperluan'], $row['keterangan'], $row['tanggal_pengajuan'],
        $row['status'], $row['nomor_surat'], $row['file_pendukung'], $row['file_hasil']
    ], $delimiter);
}
fclose($output);
exit();
?>