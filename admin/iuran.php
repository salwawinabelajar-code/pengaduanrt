<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user untuk sidebar
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// ========== CEK DAN BUAT TABEL YANG DIPERLUKAN ==========
// Tabel iuran_payments (struktur sesuai gambar)
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

// Tabel iuran_kas untuk mencatat pemasukan/pengeluaran
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

// Ambil semua KK (kartu_keluarga) untuk ditampilkan
$query_kk = "SELECT k.*, u.nama as kepala_keluarga, u.id as user_id 
             FROM kartu_keluarga k 
             LEFT JOIN users u ON k.user_id = u.id 
             ORDER BY u.nama";
$result_kk = mysqli_query($conn, $query_kk);
$keluarga_list = [];
while ($row = mysqli_fetch_assoc($result_kk)) {
    $keluarga_list[] = $row;
}

// ========== FILTER ==========
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_minggu = isset($_GET['minggu']) ? $_GET['minggu'] : '';

// Tentukan periode yang dipilih untuk filter
if (!empty($filter_minggu)) {
    $selected_periode = $filter_minggu;
} else {
    $selected_periode = "$filter_tahun-$filter_bulan-01";
}

// Ambil data pembayaran berdasarkan filter
$payments = [];
if (!empty($filter_minggu)) {
    $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start = '$selected_periode'";
} else {
    // Untuk filter bulan, ambil semua payment dalam bulan tersebut
    $start_date = "$filter_tahun-$filter_bulan-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start BETWEEN '$start_date' AND '$end_date'";
}
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[$row['keluarga_id']] = $row;
    }
}

// Hitung statistik untuk periode yang dipilih
$total_kk = count($keluarga_list);
$total_lunas = 0;
$total_belum = 0;
$total_uang = 0;
foreach ($keluarga_list as $kk) {
    if (isset($payments[$kk['id']]) && $payments[$kk['id']]['status'] == 'lunas') {
        $total_lunas++;
        $total_uang += $payments[$kk['id']]['amount'];
    } else {
        $total_belum++;
    }
}

// Dapatkan daftar minggu dalam bulan yang dipilih untuk dropdown
$weeks = [];
$start = new DateTime("$filter_tahun-$filter_bulan-01");
$end = new DateTime("$filter_tahun-$filter_bulan-" . $start->format('t'));
$interval = new DateInterval('P1D');
$daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));
foreach ($daterange as $date) {
    if ($date->format('l') == 'Saturday') {
        $weeks[] = $date->format('Y-m-d');
    }
}
// Ambil juga minggu yang sudah ada di database
$week_query = "SELECT DISTINCT week_start FROM iuran_payments WHERE YEAR(week_start)=$filter_tahun AND MONTH(week_start)=$filter_bulan ORDER BY week_start";
$week_result = mysqli_query($conn, $week_query);
if ($week_result) {
    while ($row = mysqli_fetch_assoc($week_result)) {
        $weeks[] = $row['week_start'];
    }
}
$weeks = array_unique($weeks);
sort($weeks);

// ========== PROSES MARK PAID ==========
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $keluarga_id = (int)$_POST['keluarga_id'];
    $week_start = mysqli_real_escape_string($conn, $_POST['week_start']);
    $payment_date = date('Y-m-d');
    $amount = 10000; // Default iuran mingguan 10rb
    
    // Cek apakah sudah ada record
    $check = mysqli_query($conn, "SELECT id, status FROM iuran_payments WHERE keluarga_id = '$keluarga_id' AND week_start = '$week_start'");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        if ($row['status'] == 'lunas') {
            $error = "Data sudah lunas sebelumnya.";
        } else {
            // Update status
            $update = mysqli_query($conn, "UPDATE iuran_payments SET status = 'lunas', payment_date = '$payment_date' WHERE keluarga_id = '$keluarga_id' AND week_start = '$week_start'");
            if ($update) {
                $message = "Berhasil menandai lunas.";
                // Update kas
                mysqli_query($conn, "INSERT INTO iuran_kas (tanggal, keterangan, pemasukan) VALUES ('$payment_date', 'Iuran mingguan KK periode $week_start', $amount)");
                // Update saldo
                mysqli_query($conn, "UPDATE iuran_saldo SET saldo = saldo + $amount WHERE id = 1");
            } else {
                $error = "Gagal update: " . mysqli_error($conn);
            }
        }
    } else {
        // Insert baru
        $insert = mysqli_query($conn, "INSERT INTO iuran_payments (keluarga_id, week_start, amount, status, payment_date) VALUES ('$keluarga_id', '$week_start', $amount, 'lunas', '$payment_date')");
        if ($insert) {
            $message = "Berhasil menandai lunas.";
            // Update kas
            mysqli_query($conn, "INSERT INTO iuran_kas (tanggal, keterangan, pemasukan) VALUES ('$payment_date', 'Iuran mingguan KK periode $week_start', $amount)");
            // Update saldo
            mysqli_query($conn, "UPDATE iuran_saldo SET saldo = saldo + $amount WHERE id = 1");
        } else {
            $error = "Gagal insert: " . mysqli_error($conn);
        }
    }
    
    // Redirect dengan mempertahankan filter
    $query_string = http_build_query($_GET);
    header("Location: iuran.php?" . $query_string . "&message=" . urlencode($message) . "&error=" . urlencode($error));
    exit();
}

// Ambil pesan dari redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// ========== DATA UNTUK GRAFIK (per bulan dalam tahun yang dipilih) ==========
$chart_tahun = $filter_tahun ?: date('Y');
$chart_labels = [];
$chart_data = [];
for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = date('M', mktime(0,0,0,$m,1,$chart_tahun));
    $bulan = sprintf("%04d-%02d", $chart_tahun, $m);
    $sum_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan'");
    $sum = mysqli_fetch_assoc($sum_result);
    $chart_data[] = (int)($sum['total'] ?? 0);
}

// Ambil saldo kas saat ini
$saldo_result = mysqli_query($conn, "SELECT saldo FROM iuran_saldo WHERE id = 1");
$current_saldo = $saldo_result ? (int)mysqli_fetch_assoc($saldo_result)['saldo'] : 0;

// Ambil total iuran keseluruhan
$total_iuran_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas'");
$total_iuran = $total_iuran_result ? (int)mysqli_fetch_assoc($total_iuran_result)['total'] : 0;

// Ambil iuran bulan ini
$bulan_ini = date('Y-m');
$iuran_bulan_ini_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'");
$iuran_bulan_ini = $iuran_bulan_ini_result ? (int)mysqli_fetch_assoc($iuran_bulan_ini_result)['total'] : 0;

// Ambil jumlah keluarga yang sudah bayar bulan ini
$keluarga_bayar_bulan_ini_result = mysqli_query($conn, "SELECT COUNT(DISTINCT keluarga_id) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'");
$keluarga_bayar_bulan_ini = $keluarga_bayar_bulan_ini_result ? (int)mysqli_fetch_assoc($keluarga_bayar_bulan_ini_result)['total'] : 0;

// Total KK
$total_kk_all = count($keluarga_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Iuran - Admin e-RT Digital</title>
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
            background-size: cover; min-height:100vh; position:relative; color:#fff; display:flex;
        }
        body::before {
            content:''; position:fixed; top:0; left:0; width:100%; height:100%;
            background:linear-gradient(145deg, rgba(0,84,97,0.85) 0%, rgba(36,158,148,0.75) 100%);
            backdrop-filter:blur(4px); z-index:-1;
        }
        .sidebar {
            width: 280px; background:rgba(255,255,255,0.15); backdrop-filter:blur(12px);
            border-right:1px solid var(--glass-border); padding:30px 20px; display:flex;
            flex-direction:column; position:fixed; height:100vh; overflow-y:auto;
        }
        .sidebar .logo { display:flex; align-items:center; gap:10px; margin-bottom:40px; }
        .sidebar .logo-icon { background:var(--accent); width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; box-shadow:var(--shadow); }
        .sidebar .logo-text h2 { font-size:20px; color:white; font-weight:700; }
        .sidebar .logo-text p { font-size:12px; color:rgba(255,255,255,0.7); }
        .sidebar .nav-menu { flex:1; display:flex; flex-direction:column; gap:5px; }
        .sidebar .nav-menu a { display:flex; align-items:center; gap:15px; padding:12px 15px; color:rgba(255,255,255,0.9); text-decoration:none; border-radius:15px; transition:0.3s; }
        .sidebar .nav-menu a i { width:24px; }
        .sidebar .nav-menu a:hover, .sidebar .nav-menu a.active { background:var(--secondary); transform:translateX(5px); box-shadow:0 5px 15px rgba(36,158,148,0.3); }
        .sidebar .user-profile { margin-top:20px; padding-top:20px; border-top:1px solid var(--glass-border); }
        .sidebar .user-profile a { display:flex; align-items:center; gap:12px; color:white; text-decoration:none; }
        .sidebar .user-profile .avatar { width:45px; height:45px; border-radius:50%; background:linear-gradient(145deg, var(--secondary), var(--accent)); display:flex; align-items:center; justify-content:center; font-weight:bold; border:2px solid white; }
        .sidebar .user-profile .info h4 { font-size:14px; }
        .sidebar .user-profile .info p { font-size:12px; color:rgba(255,255,255,0.7); }
        .sidebar .logout-btn { background:rgba(239,71,111,0.2); border:1px solid var(--glass-border); color:white; padding:8px 12px; border-radius:30px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:10px; transition:0.3s; }
        .sidebar .logout-btn:hover { background:var(--danger); }
        .main-content { flex:1; margin-left:280px; padding:30px; }
        .content-header { background:var(--glass-bg); backdrop-filter:blur(12px); border-radius:60px; padding:20px 30px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; border:1px solid var(--glass-border); }
        .content-header h1 { font-size:28px; font-weight:800; background:linear-gradient(135deg, #fff, var(--accent)); background-clip:text; -webkit-background-clip:text; color:transparent; }
        .btn-primary { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:12px 25px; border-radius:40px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.3s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(36,158,148,0.4); }
        .btn-secondary { background:rgba(239,71,111,0.2); color:white; border:1px solid rgba(239,71,111,0.3); padding:12px 25px; border-radius:40px; text-decoration:none; }
        .message { padding:12px 20px; border-radius:30px; margin-bottom:20px; }
        .message.success { background:rgba(6,214,160,0.2); border:1px solid var(--success); color:white; }
        .message.error { background:rgba(239,71,111,0.2); border:1px solid var(--danger); color:white; }
        .stats-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:20px; margin-bottom:30px; }
        @media (max-width:1200px) { .stats-grid { grid-template-columns:repeat(3,1fr); } }
        @media (max-width:768px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        .stat-card { background:var(--glass-bg); backdrop-filter:blur(12px); border-radius:28px; padding:20px; border:1px solid var(--glass-border); transition:0.3s; }
        .stat-card:hover { transform:translateY(-5px); background:rgba(255,255,255,0.2); border-color:var(--accent); }
        .stat-card .stat-icon { font-size:32px; margin-bottom:10px; color:var(--accent); }
        .stat-card .number { font-size:32px; font-weight:800; }
        .stat-card .small-text { font-size:12px; color:rgba(255,255,255,0.7); margin-top:5px; }
        .filter-section { background:var(--glass-bg); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-bottom:30px; border:1px solid var(--glass-border); }
        .filter-form { display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap; }
        .filter-group { flex:1 1 150px; }
        .filter-group label { display:block; margin-bottom:5px; font-size:13px; font-weight:600; color:rgba(255,255,255,0.9); }
        .filter-group select, .filter-group input { width:100%; padding:10px; border:2px solid var(--glass-border); border-radius:30px; background:rgba(255,255,255,0.1); color:white; }
        .filter-group select:focus, .filter-group input:focus { outline:none; border-color:var(--accent); }
        .filter-group select option { background:var(--primary); }
        .btn-filter { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:10px 25px; border-radius:40px; cursor:pointer; font-weight:600; }
        .btn-reset { background:rgba(239,71,111,0.2); color:white; border:1px solid rgba(239,71,111,0.3); padding:10px 25px; border-radius:40px; text-decoration:none; display:inline-block; font-weight:600; }
        .btn-reset:hover { background:var(--danger); }
        .table-container { background:var(--glass-bg); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-top:30px; overflow-x:auto; border:1px solid var(--glass-border); }
        table { width:100%; border-collapse:collapse; color:white; }
        th, td { padding:15px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        th { background:rgba(0,0,0,0.3); font-weight:600; }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lunas { background:rgba(6,214,160,0.2); color:#06D6A0; }
        .belum { background:rgba(239,71,111,0.2); color:#EF476F; }
        .chart-container { background:var(--glass-bg); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-bottom:30px; border:1px solid var(--glass-border); }
        .chart-container h3 { margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:var(--glass-bg); backdrop-filter:blur(20px); border-radius:32px; padding:30px; max-width:400px; width:90%; border:1px solid var(--glass-border); }
        .modal-content h3 { margin-bottom:15px; }
        .modal-buttons { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        @media (max-width:768px) { .sidebar { transform:translateX(-100%); transition:0.3s; position:fixed; z-index:1000; } .sidebar.active { transform:translateX(0); } .main-content { margin-left:0; } .filter-form { flex-direction:column; } .menu-toggle { display:block; position:fixed; top:20px; left:20px; z-index:200; background:var(--secondary); border:none; color:white; width:45px; height:45px; border-radius:50%; font-size:20px; cursor:pointer; box-shadow:var(--shadow); } }
        .menu-toggle { display:none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h2>e-RT Digital</h2><p>Panel Admin</p></div>
        </div>
        <div class="nav-menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
            <a href="iuran.php" class="active"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>             
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
            <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama']??'A',0,1)); ?></div>
                <div class="info"><h4><?php echo htmlspecialchars($user['nama']??'Admin'); ?></h4><p>admin</p></div>
            </a>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-money-bill-wave"></i> Kelola Iuran</h1>
            <div>
                <a href="iuran_kas.php" class="btn-primary"><i class="fas fa-cash-register"></i> Manajemen Kas</a>
                <a href="export_iuran.php?<?php echo http_build_query($_GET); ?>" class="btn-primary" style="margin-left:10px;"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            </div>
        </div>

        <!-- Pesan -->
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistik Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="number">Rp <?php echo number_format($total_iuran,0,',','.'); ?></div>
                <div class="small-text">Total Iuran Keseluruhan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="number">Rp <?php echo number_format($iuran_bulan_ini,0,',','.'); ?></div>
                <div class="small-text">Iuran Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="number"><?php echo $keluarga_bayar_bulan_ini; ?></div>
                <div class="small-text">KK Bayar Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-address-card"></i></div>
                <div class="number"><?php echo $total_kk_all; ?></div>
                <div class="small-text">Total Kartu Keluarga</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="number">Rp <?php echo number_format($current_saldo,0,',','.'); ?></div>
                <div class="small-text">Saldo Kas Saat Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-percent"></i></div>
                <div class="number"><?php echo $total_kk_all > 0 ? round(($keluarga_bayar_bulan_ini / $total_kk_all) * 100, 1) : 0; ?>%</div>
                <div class="small-text">Partisipasi Bulan Ini</div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="filter-group">
                    <label>Tahun</label>
                    <select name="tahun" id="filterTahun">
                        <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php if($filter_tahun == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Bulan</label>
                    <select name="bulan" id="filterBulan">
                        <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php if($filter_bulan == $m) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Minggu (opsional)</label>
                    <select name="minggu" id="filterMinggu">
                        <option value="">-- Semua Minggu --</option>
                        <?php foreach($weeks as $w): ?>
                        <option value="<?php echo $w; ?>" <?php if($filter_minggu == $w) echo 'selected'; ?>><?php echo date('d M Y', strtotime($w)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
                </div>
                <div class="filter-group">
                    <a href="iuran.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Ringkasan Periode -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="number"><?php echo $total_kk; ?></div>
                <div class="small-text">Total KK</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?php echo $total_lunas; ?></div>
                <div class="small-text">Lunas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="number"><?php echo $total_belum; ?></div>
                <div class="small-text">Belum Bayar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="number">Rp <?php echo number_format($total_uang,0,',','.'); ?></div>
                <div class="small-text">Total Uang</div>
            </div>
        </div>

        <!-- Grafik Pemasukan per Bulan -->
        <div class="chart-container">
            <h3><i class="fas fa-chart-bar" style="color:var(--accent);"></i> Grafik Pemasukan Iuran Tahun <?php echo $chart_tahun; ?></h3>
            <canvas id="iuranChart" style="max-height:300px;"></canvas>
        </div>

        <!-- Tabel Iuran per KK -->
        <h2 style="margin-bottom:20px;">Status Iuran Periode: <?php echo empty($filter_minggu) ? date('F Y', mktime(0,0,0,$filter_bulan,1,$filter_tahun)) : date('d M Y', strtotime($filter_minggu)); ?></h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No. KK</th>
                        <th>Kepala Keluarga</th>
                        <th>Status</th>
                        <th>Tanggal Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keluarga_list as $kk): ?>
                    <?php 
                        $status = isset($payments[$kk['id']]) ? 'lunas' : 'belum';
                        $payment_date = isset($payments[$kk['id']]) ? $payments[$kk['id']]['payment_date'] : null;
                        $class = ($status == 'lunas') ? 'lunas' : 'belum';
                        $week_start_value = !empty($filter_minggu) ? $filter_minggu : $selected_periode;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kk['no_kk']); ?></td>
                        <td><?php echo htmlspecialchars($kk['kepala_keluarga'] ?? '-'); ?></td>
                        <td><span class="status-badge <?php echo $class; ?>"><?php echo ucfirst($status); ?></span></td>
                        <td><?php echo $payment_date ? date('d M Y', strtotime($payment_date)) : '-'; ?></td>
                        <td>
                            <?php if ($status != 'lunas'): ?>
                            <button class="btn-primary" onclick="markPaid(<?php echo $kk['id']; ?>, '<?php echo $week_start_value; ?>')" style="padding:8px 16px; font-size:12px;">Tandai Lunas</button>
                            <?php else: ?>
                            <span style="color:var(--success);">✅ Lunas</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="paidModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Pembayaran</h3>
            <p>Apakah KK ini sudah membayar iuran untuk periode ini?</p>
            <form method="POST" action="">
                <input type="hidden" name="keluarga_id" id="keluarga_id">
                <input type="hidden" name="week_start" id="week_start">
                <input type="hidden" name="mark_paid" value="1">
                <div class="modal-buttons">
                    <button type="button" class="btn-reset" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-primary">Ya, Lunas</button>
                </div>
            </form>
        </div>
    </div>

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

        function markPaid(keluargaId, weekStart) {
            document.getElementById('keluarga_id').value = keluargaId;
            document.getElementById('week_start').value = weekStart;
            document.getElementById('paidModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('paidModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('paidModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Chart
        const ctx = document.getElementById('iuranChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Total Iuran (Rp)',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(59, 193, 168, 0.6)',
                    borderColor: '#3BC1A8',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    } 
                },
                plugins: {
                    legend: { labels: { color: 'white' } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
