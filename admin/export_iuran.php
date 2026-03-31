<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil filter dari URL
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_minggu = isset($_GET['minggu']) ? $_GET['minggu'] : '';

// Tentukan rentang tanggal berdasarkan filter
if (!empty($filter_minggu)) {
    $week_start = $filter_minggu;
    $where_periode = "week_start = '$week_start'";
    $periode_text = "Minggu " . date('d M Y', strtotime($week_start));
} else {
    $start_date = "$filter_tahun-$filter_bulan-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    $where_periode = "week_start BETWEEN '$start_date' AND '$end_date'";
    $periode_text = date('F Y', mktime(0,0,0,$filter_bulan,1,$filter_tahun));
}

// Ambil semua KK
$query_kk = "SELECT k.*, u.nama as kepala_keluarga 
             FROM kartu_keluarga k 
             LEFT JOIN users u ON k.user_id = u.id 
             ORDER BY u.nama";
$result_kk = $conn->query($query_kk);
$keluarga_list = [];
while ($row = $result_kk->fetch_assoc()) {
    $keluarga_list[] = $row;
}

// Ambil data pembayaran untuk periode tersebut
$payments = [];
$query = "SELECT keluarga_id, status, payment_date, amount FROM iuran_payments WHERE $where_periode";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $payments[$row['keluarga_id']] = $row;
}

// Set header untuk download CSV (delimiter koma)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="iuran_' . $periode_text . '.csv"');

// BOM untuk UTF-8 (agar karakter khusus terbaca)
echo "\xEF\xBB\xBF";

// Buat file output
$output = fopen('php://output', 'w');

// Tulis informasi periode sebagai baris pertama
fputcsv($output, ['Periode:', $periode_text]);

// Baris kosong
fputcsv($output, []);

// Tulis header kolom
fputcsv($output, ['No. KK', 'Kepala Keluarga', 'Status', 'Tanggal Bayar']);

// Tulis data per KK
foreach ($keluarga_list as $kk) {
    $status = isset($payments[$kk['id']]) ? $payments[$kk['id']]['status'] : 'belum';
    $payment_date = isset($payments[$kk['id']]) ? $payments[$kk['id']]['payment_date'] : '-';
    fputcsv($output, [
        $kk['no_kk'],
        $kk['kepala_keluarga'] ?? '-',
        ucfirst($status),
        $payment_date != '-' ? date('d-m-Y', strtotime($payment_date)) : '-'
    ]);
}

fclose($output);
exit;
?>