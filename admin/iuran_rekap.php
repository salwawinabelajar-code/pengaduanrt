<?php
// admin/iuran_rekap.php
session_start();
require_once(__DIR__ . '/../config/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$bulan = $_GET['bulan'] ?? date('Y-m');
$data = $conn->query("SELECT k.no_kk, k.kepala_keluarga, p.week_start, p.payment_date, p.amount 
    FROM iuran_payments p
    JOIN data_kk.kartu_keluarga k ON p.keluarga_id = k.id
    WHERE DATE_FORMAT(p.payment_date, '%Y-%m') = '$bulan' AND p.status='lunas'
    ORDER BY p.payment_date");
?>
<!-- Tampilkan tabel dan tombol download -->