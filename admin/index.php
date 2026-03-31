<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data admin
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// ========== CEK TABEL YANG DIPERLUKAN ==========
// Tabel iuran_payments
$check_payments = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_payments'");
if (mysqli_num_rows($check_payments) == 0) {
    $create_payments = "CREATE TABLE iuran_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keluarga_id INT NOT NULL,
        week_start DATE NOT NULL,
        amount INT NOT NULL DEFAULT 10000,
        status ENUM('lunas','belum','pending') DEFAULT 'belum',
        payment_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_payments);
}

// Tabel iuran_kas
$check_kas = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_kas'");
if (mysqli_num_rows($check_kas) == 0) {
    $create_kas = "CREATE TABLE iuran_kas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        pemasukan INT DEFAULT 0,
        pengeluaran INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_kas);
}

// Tabel iuran_saldo
$check_saldo = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_saldo'");
if (mysqli_num_rows($check_saldo) == 0) {
    $create_saldo = "CREATE TABLE iuran_saldo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        saldo INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_saldo);
    mysqli_query($conn, "INSERT INTO iuran_saldo (id, saldo) VALUES (1, 0)");
}

// ========== STATISTIK ==========
$stats = [];

// Total pengaduan
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengaduan");
$stats['total_pengaduan'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Pengaduan per status
$result = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM pengaduan GROUP BY status");
$pengaduan_status = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pengaduan_status[$row['status']] = $row['jumlah'];
}
$stats['pengaduan_baru'] = $pengaduan_status['baru'] ?? 0;
$stats['pengaduan_diproses'] = $pengaduan_status['diproses'] ?? 0;
$stats['pengaduan_selesai'] = $pengaduan_status['selesai'] ?? 0;
$stats['pengaduan_ditolak'] = $pengaduan_status['ditolak'] ?? 0;

// Total surat
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_surat");
$stats['total_surat'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Surat per status
$result = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM pengajuan_surat GROUP BY status");
$surat_status = [];
while ($row = mysqli_fetch_assoc($result)) {
    $surat_status[$row['status']] = $row['jumlah'];
}
$stats['surat_menunggu'] = $surat_status['menunggu'] ?? 0;
$stats['surat_diproses'] = $surat_status['diproses'] ?? 0;
$stats['surat_selesai'] = $surat_status['selesai'] ?? 0;
$stats['surat_ditolak'] = $surat_status['ditolak'] ?? 0;

// Total warga
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='warga'");
$stats['total_warga'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Total KK
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
$stats['total_kk'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// ========== STATISTIK IURAN dari tabel iuran_payments ==========

// Total iuran keseluruhan (semua yang sudah lunas)
$query_total_iuran = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas'";
$result_total_iuran = mysqli_query($conn, $query_total_iuran);
if ($result_total_iuran && mysqli_num_rows($result_total_iuran) > 0) {
    $row = mysqli_fetch_assoc($result_total_iuran);
    $stats['total_iuran_keseluruhan'] = (int)($row['total'] ?? 0);
} else {
    $stats['total_iuran_keseluruhan'] = 0;
}

// Iuran bulan ini (week_start bulan ini, status lunas)
$bulan_ini = date('Y-m');
$query_iuran_bulan = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'";
$result_iuran_bulan = mysqli_query($conn, $query_iuran_bulan);
if ($result_iuran_bulan && mysqli_num_rows($result_iuran_bulan) > 0) {
    $row = mysqli_fetch_assoc($result_iuran_bulan);
    $stats['iuran_bulan_ini'] = (int)($row['total'] ?? 0);
} else {
    $stats['iuran_bulan_ini'] = 0;
}

// Iuran bulan lalu (untuk perbandingan)
$bulan_lalu = date('Y-m', strtotime('-1 month'));
$query_iuran_bulan_lalu = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_lalu'";
$result_iuran_bulan_lalu = mysqli_query($conn, $query_iuran_bulan_lalu);
if ($result_iuran_bulan_lalu && mysqli_num_rows($result_iuran_bulan_lalu) > 0) {
    $row = mysqli_fetch_assoc($result_iuran_bulan_lalu);
    $stats['iuran_bulan_lalu'] = (int)($row['total'] ?? 0);
} else {
    $stats['iuran_bulan_lalu'] = 0;
}

// Persentase perubahan iuran
if ($stats['iuran_bulan_lalu'] > 0) {
    $stats['iuran_persen'] = round(($stats['iuran_bulan_ini'] - $stats['iuran_bulan_lalu']) / $stats['iuran_bulan_lalu'] * 100, 1);
} else {
    $stats['iuran_persen'] = $stats['iuran_bulan_ini'] > 0 ? 100 : 0;
}

// Jumlah keluarga yang sudah bayar iuran bulan ini (dari iuran_payments)
$query_keluarga_bayar = "SELECT COUNT(DISTINCT keluarga_id) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'";
$result_keluarga_bayar = mysqli_query($conn, $query_keluarga_bayar);
if ($result_keluarga_bayar && mysqli_num_rows($result_keluarga_bayar) > 0) {
    $row = mysqli_fetch_assoc($result_keluarga_bayar);
    $stats['keluarga_bayar_bulan_ini'] = (int)($row['total'] ?? 0);
} else {
    $stats['keluarga_bayar_bulan_ini'] = 0;
}

// Target iuran (asumsi 10rb per minggu = 40rb per bulan per KK)
$stats['target_iuran'] = $stats['total_kk'] * 40000;
$stats['pencapaian_iuran'] = $stats['target_iuran'] > 0 ? round(($stats['iuran_bulan_ini'] / $stats['target_iuran']) * 100, 1) : 0;

// Jumlah total transaksi iuran (yang sudah lunas)
$query_total_transaksi = "SELECT COUNT(*) as total FROM iuran_payments WHERE status = 'lunas'";
$result_total_transaksi = mysqli_query($conn, $query_total_transaksi);
if ($result_total_transaksi && mysqli_num_rows($result_total_transaksi) > 0) {
    $row = mysqli_fetch_assoc($result_total_transaksi);
    $stats['total_transaksi_iuran'] = (int)($row['total'] ?? 0);
} else {
    $stats['total_transaksi_iuran'] = 0;
}

// Ambil saldo kas saat ini
$saldo_result = mysqli_query($conn, "SELECT saldo FROM iuran_saldo WHERE id = 1");
$current_saldo = $saldo_result ? (int)mysqli_fetch_assoc($saldo_result)['saldo'] : 0;

// Data untuk grafik pengaduan per bulan (6 bulan terakhir)
$bulan_labels = [];
$pengaduan_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_labels[] = date('M Y', strtotime($bulan . '-01'));
    $query = "SELECT COUNT(*) as total FROM pengaduan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $pengaduan_bulan[] = $row['total'] ?? 0;
}

// Data untuk grafik surat per status (doughnut)
$surat_chart_data = [
    $stats['surat_menunggu'],
    $stats['surat_diproses'],
    $stats['surat_selesai'],
    $stats['surat_ditolak']
];

// Data untuk grafik iuran per bulan (6 bulan terakhir)
$iuran_bulan_labels = [];
$iuran_bulan_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $iuran_bulan_labels[] = date('M Y', strtotime($bulan . '-01'));
    $query = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $iuran_bulan_data[] = (int)($row['total'] ?? 0);
}

// Ambil 5 pengaduan terbaru
$query = "SELECT p.*, u.nama FROM pengaduan p JOIN users u ON p.user_id = u.id ORDER BY p.tanggal DESC LIMIT 5";
$result_pengaduan = mysqli_query($conn, $query);

// Ambil 5 surat terbaru
$query = "SELECT s.*, u.nama FROM pengajuan_surat s JOIN users u ON s.user_id = u.id ORDER BY s.tanggal_pengajuan DESC LIMIT 5";
$result_surat = mysqli_query($conn, $query);

// Ambil 5 iuran terbaru dari iuran_payments
$query_iuran_terbaru = "SELECT ip.*, k.no_kk, u.nama as kepala_keluarga 
                         FROM iuran_payments ip 
                         LEFT JOIN kartu_keluarga k ON ip.keluarga_id = k.id
                         LEFT JOIN users u ON k.user_id = u.id
                         ORDER BY ip.created_at DESC LIMIT 5";
$result_iuran_terbaru = mysqli_query($conn, $query_iuran_terbaru);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        :root {
            --primary: #005461;
            --secondary: #249E94;
            --accent: #3BC1A8;
            --danger: #EF476F;
            --warning: #FFD166;
            --success: #06D6A0;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            color: #fff;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.85) 0%, rgba(36,158,148,0.75) 100%);
            backdrop-filter: blur(4px);
            z-index: -1;
        }
        .app { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--glass-border);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            padding-left: 10px;
        }
        .sidebar .logo-icon {
            background: var(--accent);
            width: 50px; height: 50px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 24px; box-shadow: var(--shadow);
        }
        .sidebar .logo-text h2 {
            font-size: 20px; color: white; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .sidebar .logo-text p {
            font-size: 12px; color: rgba(255,255,255,0.7);
        }
        .sidebar .nav-menu {
            flex: 1; display: flex; flex-direction: column; gap: 5px;
        }
        .sidebar .nav-menu a {
            display: flex; align-items: center; gap: 15px; padding: 12px 15px;
            color: rgba(255,255,255,0.9); text-decoration: none; border-radius: 15px;
            transition: 0.3s; font-weight: 500;
        }
        .sidebar .nav-menu a i { width: 24px; font-size: 18px; }
        .sidebar .nav-menu a:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .sidebar .nav-menu a.active { background: var(--secondary); box-shadow: 0 5px 15px rgba(36,158,148,0.3); }
        .sidebar .user-profile {
            margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--glass-border);
        }
        .sidebar .user-profile a {
            display: flex; align-items: center; gap: 12px; text-decoration: none; color: white;
        }
        .sidebar .user-profile .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 18px; border: 2px solid white;
        }
        .sidebar .user-profile .info { flex: 1; }
        .sidebar .user-profile .info h4 { font-size: 14px; color: white; }
        .sidebar .user-profile .info p { font-size: 12px; color: rgba(255,255,255,0.7); }
        .sidebar .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid var(--glass-border);
            color: white; padding: 8px 12px; border-radius: 30px; text-decoration: none;
            font-size: 13px; font-weight: 600; transition: 0.3s; display: flex;
            align-items: center; justify-content: center; gap: 5px; margin-top: 10px;
        }
        .sidebar .logout-btn:hover { background: var(--danger); }
        .main-content {
            flex: 1; margin-left: 280px; padding: 30px;
        }
        .content-header {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 60px;
            padding: 20px 30px; margin-bottom: 30px; display: flex; justify-content: space-between;
            align-items: center; border: 1px solid var(--glass-border);
        }
        .content-header h1 {
            font-size: 28px; font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--accent));
            background-clip: text; -webkit-background-clip: text;
            color: transparent;
        }
        .content-header .date {
            background: rgba(0,0,0,0.2); padding: 8px 20px; border-radius: 40px;
            color: white; display: flex; align-items: center; gap: 8px;
            border: 1px solid var(--glass-border);
        }
        .welcome-message {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 30px; margin-bottom: 30px; border: 1px solid var(--glass-border);
            display: flex; align-items: center; gap: 20px;
        }
        .welcome-message .avatar-large {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 36px; font-weight: bold; border: 3px solid white;
        }
        .welcome-message h2 {
            font-size: 24px; font-weight: 600; color: white; margin-bottom: 8px;
        }
        .welcome-message p {
            color: rgba(255,255,255,0.8); font-size: 16px;
        }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 28px;
            padding: 25px; border: 1px solid var(--glass-border); transition: 0.4s;
            box-shadow: var(--shadow);
        }
        .stat-card:hover {
            transform: translateY(-5px); background: rgba(255,255,255,0.2); border-color: var(--accent);
        }
        .stat-card .stat-icon {
            font-size: 32px; margin-bottom: 15px; display: inline-block; width: 60px; height: 60px;
            line-height: 60px; border-radius: 50%; background: rgba(255,255,255,0.2);
            color: var(--accent); border: 1px solid var(--glass-border); text-align: center;
        }
        .stat-card h3 {
            font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 10px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .stat-card .number { font-size: 32px; font-weight: 800; color: white; }
        .stat-card .trend { font-size: 12px; margin-top: 8px; color: var(--success); }
        .stat-card .trend.down { color: var(--danger); }
        .stat-card .small-text { font-size: 12px; margin-top: 5px; color: rgba(255,255,255,0.6); }
        .charts-row {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px;
        }
        @media (max-width: 1200px) {
            .charts-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .charts-row { grid-template-columns: 1fr; }
        }
        .chart-card {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 28px;
            padding: 25px; border: 1px solid var(--glass-border);
        }
        .chart-card h3 {
            margin-bottom: 20px; font-size: 18px; color: white; display: flex; align-items: center; gap: 10px;
        }
        .chart-card canvas { max-height: 300px; }
        .recent-section {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px;
        }
        @media (max-width: 1200px) {
            .recent-section { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .recent-section { grid-template-columns: 1fr; }
        }
        .recent-card {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 28px;
            padding: 25px; border: 1px solid var(--glass-border);
        }
        .recent-card .header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        .recent-card .header h3 {
            font-size: 18px; color: white; display: flex; align-items: center; gap: 10px;
        }
        .recent-card .header a {
            color: var(--accent); text-decoration: none; font-weight: 600; font-size: 14px;
        }
        .recent-card .header a:hover { text-decoration: underline; }
        .recent-item {
            padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-item .title {
            font-weight: 600; color: white; margin-bottom: 5px;
        }
        .recent-item .meta {
            display: flex; gap: 15px; font-size: 12px; color: rgba(255,255,255,0.7); flex-wrap: wrap;
        }
        .recent-item .status {
            display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .status-baru, .status-menunggu { background: rgba(255,209,102,0.2); color: #FFD166; }
        .status-diproses { background: rgba(17,138,178,0.2); color: #118AB2; }
        .status-selesai { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .status-ditolak { background: rgba(239,71,111,0.2); color: #EF476F; }
        .status-lunas { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .status-belum { background: rgba(239,71,111,0.2); color: #EF476F; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; position: fixed; z-index: 1000; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; position: fixed; top: 20px; left: 20px; z-index: 200;
                background: var(--secondary); border: none; color: white; width: 45px; height: 45px;
                border-radius: 50%; font-size: 20px; cursor: pointer; box-shadow: var(--shadow);
            }
        }
        .menu-toggle { display: none; }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
                <div class="logo-text">
                    <h2>e-RT Digital</h2>
                    <p>Panel Admin</p>
                </div>
            </div>

            <div class="nav-menu">
                <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
                <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
                <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
                <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
                <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
                <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
                <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
            </div>

            <div class="user-profile">
                <a href="profil.php">
                    <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                    <div class="info">
                        <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </a>
            </div>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>

        <!-- Konten Utama -->
        <div class="main-content">
            <div class="content-header">
                <h1>Dashboard Admin</h1>
                <div class="date">
                    <i class="far fa-calendar-alt"></i> <?php echo date('d M Y'); ?>
                </div>
            </div>

            <!-- Selamat Datang -->
            <div class="welcome-message">
                <div class="avatar-large"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div>
                    <h2>Selamat datang kembali, <?php echo htmlspecialchars($user['nama']); ?>!</h2>
                    <p>Ini adalah ringkasan aktivitas terkini di sistem e-RT Digital. Anda dapat memantau pengaduan, surat, iuran, dan data warga dengan mudah.</p>
                </div>
            </div>

            <!-- Statistik Cepat -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <h3>Total Pengaduan</h3>
                    <div class="number"><?php echo $stats['total_pengaduan']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Pengaduan Baru</h3>
                    <div class="number"><?php echo $stats['pengaduan_baru']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Pengaduan Diproses</h3>
                    <div class="number"><?php echo $stats['pengaduan_diproses']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Pengaduan Selesai</h3>
                    <div class="number"><?php echo $stats['pengaduan_selesai']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Surat Menunggu</h3>
                    <div class="number"><?php echo $stats['surat_menunggu']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h3>Total Warga</h3>
                    <div class="number"><?php echo $stats['total_warga']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-address-card"></i></div>
                    <h3>Kartu Keluarga</h3>
                    <div class="number"><?php echo $stats['total_kk']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h3>Total Iuran Keseluruhan</h3>
                    <div class="number">Rp <?php echo number_format($stats['total_iuran_keseluruhan'], 0, ',', '.'); ?></div>
                    <div class="small-text"><?php echo $stats['total_transaksi_iuran']; ?> transaksi lunas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Iuran Bulan Ini</h3>
                    <div class="number">Rp <?php echo number_format($stats['iuran_bulan_ini'], 0, ',', '.'); ?></div>
                    <div class="trend <?php echo $stats['iuran_persen'] >= 0 ? '' : 'down'; ?>">
                        <?php if ($stats['iuran_persen'] > 0): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $stats['iuran_persen']; ?>% dari bulan lalu
                        <?php elseif ($stats['iuran_persen'] < 0): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo abs($stats['iuran_persen']); ?>% dari bulan lalu
                        <?php else: ?>
                            Sama seperti bulan lalu
                        <?php endif; ?>
                    </div>
                    <div class="small-text"><?php echo $stats['keluarga_bayar_bulan_ini']; ?> KK sudah bayar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Saldo Kas</h3>
                    <div class="number">Rp <?php echo number_format($current_saldo, 0, ',', '.'); ?></div>
                    <div class="small-text">Saldo kas saat ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
                    <h3>Target Iuran Bulan Ini</h3>
                    <div class="number">Rp <?php echo number_format($stats['target_iuran'], 0, ',', '.'); ?></div>
                    <div class="small-text">Pencapaian: <?php echo $stats['pencapaian_iuran']; ?>%</div>
                </div>
            </div>

            <!-- Grafik -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line" style="color: var(--accent);"></i> Tren Pengaduan (6 Bulan Terakhir)</h3>
                    <canvas id="pengaduanChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie" style="color: var(--accent);"></i> Status Surat</h3>
                    <canvas id="suratChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar" style="color: var(--accent);"></i> Tren Iuran (6 Bulan Terakhir)</h3>
                    <canvas id="iuranChart"></canvas>
                </div>
            </div>

            <!-- Aktivitas Terkini -->
            <div class="recent-section">
                <!-- Pengaduan Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-comment-medical" style="color: var(--accent);"></i> Pengaduan Terbaru</h3>
                        <a href="pengaduan.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_pengaduan) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_pengaduan)): ?>
                        <div class="recent-item">
                            <div class="title"><?php echo htmlspecialchars($row['judul']); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nama']); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('d M H:i', strtotime($row['tanggal'])); ?></span>
                                <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">Belum ada pengaduan.</p>
                    <?php endif; ?>
                </div>

                <!-- Surat Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-envelope-open-text" style="color: var(--accent);"></i> Pengajuan Surat Terbaru</h3>
                        <a href="surat.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_surat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_surat)): ?>
                        <div class="recent-item">
                            <div class="title"><?php echo htmlspecialchars(ucfirst($row['jenis_surat'])); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nama']); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('d M H:i', strtotime($row['tanggal_pengajuan'])); ?></span>
                                <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">Belum ada pengajuan surat.</p>
                    <?php endif; ?>
                </div>

                <!-- Iuran Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-money-bill-wave" style="color: var(--accent);"></i> Pembayaran Iuran Terbaru</h3>
                        <a href="iuran.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_iuran_terbaru) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_iuran_terbaru)): ?>
                        <div class="recent-item">
                            <div class="title">Iuran Mingguan - <?php echo htmlspecialchars($row['kepala_keluarga'] ?? 'KK'); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-address-card"></i> No KK: <?php echo htmlspecialchars($row['no_kk']); ?></span>
                                <span><i class="far fa-calendar"></i> Periode: <?php echo date('d M Y', strtotime($row['week_start'])); ?></span>
                                <span class="status status-lunas">Lunas</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">Belum ada pembayaran iuran.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol toggle untuk mobile -->
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <script>
        const toggleBtn = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'block';
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Grafik Pengaduan
        const ctx1 = document.getElementById('pengaduanChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($bulan_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pengaduan',
                    data: <?php echo json_encode($pengaduan_bulan); ?>,
                    borderColor: 'rgba(59, 193, 168, 1)',
                    backgroundColor: 'rgba(59, 193, 168, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: 'white',
                    pointBorderColor: '#3BC1A8',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: 'white' } }
                },
                scales: {
                    x: { ticks: { color: 'rgba(255,255,255,0.8)' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    y: { ticks: { color: 'rgba(255,255,255,0.8)' }, grid: { color: 'rgba(255,255,255,0.1)' } }
                }
            }
        });

        // Grafik Surat (Doughnut)
        const ctx2 = document.getElementById('suratChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'],
                datasets: [{
                    data: <?php echo json_encode($surat_chart_data); ?>,
                    backgroundColor: ['#FFD166', '#118AB2', '#06D6A0', '#EF476F'],
                    borderColor: 'rgba(255,255,255,0.2)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: 'white' } }
                }
            }
        });

        // Grafik Iuran (Bar)
        const ctx3 = document.getElementById('iuranChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($iuran_bulan_labels); ?>,
                datasets: [{
                    label: 'Total Iuran (Rp)',
                    data: <?php echo json_encode($iuran_bulan_data); ?>,
                    backgroundColor: 'rgba(59, 193, 168, 0.6)',
                    borderColor: 'rgba(59, 193, 168, 1)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: 'white' } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: 'rgba(255,255,255,0.8)' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    y: { 
                        ticks: { 
                            color: 'rgba(255,255,255,0.8)',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }, 
                        grid: { color: 'rgba(255,255,255,0.1)' } 
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>
