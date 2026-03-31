<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Cari keluarga berdasarkan user_id di tabel kartu_keluarga
$keluarga = $conn->query("SELECT * FROM kartu_keluarga WHERE user_id = $user_id")->fetch_assoc();

// Ambil parameter filter
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;

$iuran_list = [];
$stats = ['total' => 0, 'lunas' => 0, 'belum' => 0, 'total_uang' => 0];

if ($keluarga) {
    $keluarga_id = $keluarga['id'];
    
    // Bangun query dengan filter
    $sql = "SELECT * FROM iuran_payments WHERE keluarga_id = $keluarga_id";
    $params = [];
    $types = "";
    
    if ($filter_tahun > 0) {
        $sql .= " AND YEAR(week_start) = ?";
        $params[] = $filter_tahun;
        $types .= "i";
    }
    if ($filter_bulan > 0) {
        $sql .= " AND MONTH(week_start) = ?";
        $params[] = $filter_bulan;
        $types .= "i";
    }
    $sql .= " ORDER BY week_start DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $iuran = $stmt->get_result();
    
    while ($row = $iuran->fetch_assoc()) {
        $iuran_list[] = $row;
        $stats['total']++;
        if ($row['status'] == 'lunas') {
            $stats['lunas']++;
            $stats['total_uang'] += $row['amount'];
        } else {
            $stats['belum']++;
        }
    }
}

// Ambil daftar tahun yang ada untuk dropdown
$tahun_list = [];
if ($keluarga) {
    $result = $conn->query("SELECT DISTINCT YEAR(week_start) as tahun FROM iuran_payments WHERE keluarga_id = $keluarga_id ORDER BY tahun DESC");
    while ($row = $result->fetch_assoc()) {
        $tahun_list[] = $row['tahun'];
    }
}
if (empty($tahun_list)) {
    // Jika belum ada data, set tahun sekarang
    $tahun_list[] = date('Y');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iuran Saya - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Modern dengan tema glass */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        :root {
            --primary: #005461;
            --primary-light: #0C7779;
            --secondary: #249E94;
            --accent: #3BC1A8;
            --light: #F8F9FA;
            --dark: #1A1A2E;
            --gray: #6C757D;
            --danger: #EF476F;
            --warning: #FFD166;
            --success: #06D6A0;
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
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }

        /* Navbar glass yang lebih menarik */
        .navbar {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            background: var(--accent);
            width: 45px;
            height: 45px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }

        .logo-icon:hover {
            transform: rotate(5deg) scale(1.1);
        }

        .logo-text h1 {
            font-size: 22px;
            color: white;
            font-weight: 700;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .nav-menu {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 30px;
            transition: all 0.3s;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
            white-space: nowrap;
        }

        .nav-menu a:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }

        .nav-menu a.active {
            background: var(--secondary);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .user-profile a {
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            border: 2px solid white;
            transition: transform 0.3s;
        }

        .avatar:hover {
            transform: scale(1.1);
        }

        .user-info {
            color: white;
        }

        .user-info h4 {
            font-size: 16px;
            line-height: 1.2;
        }

        .user-info small {
            font-size: 12px;
            opacity: 0.8;
        }

        .logout-btn {
            background: rgba(239,71,111,0.2);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: var(--danger);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 25px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left i {
            font-size: 28px;
            color: var(--accent);
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--secondary);
            border-color: var(--secondary);
            transform: translateX(-5px);
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            transition: 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
            border-color: var(--accent);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
            display: inline-block;
            width: 70px;
            height: 70px;
            line-height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: var(--accent);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .stat-card h3 {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
        }

        /* Kegunaan Box */
        .kegunaan-box {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .kegunaan-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            z-index: 1;
        }

        .kegunaan-title {
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            position: relative;
            z-index: 2;
        }

        .kegunaan-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .kegunaan-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            padding: 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .kegunaan-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.2);
        }

        .kegunaan-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .kegunaan-text h4 {
            font-size: 16px;
            color: white;
            margin-bottom: 5px;
        }

        .kegunaan-text p {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .filter-title {
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1 1 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
            backdrop-filter: blur(5px);
            transition: 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
        }

        .filter-group select option {
            background: var(--primary);
            color: white;
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(36,158,148,0.4);
        }

        .btn-reset {
            background: rgba(239,71,111,0.2);
            color: white;
            border: 1px solid rgba(239,71,111,0.3);
            padding: 10px 25px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .btn-reset:hover {
            background: var(--danger);
            transform: translateY(-2px);
        }

        /* Table Container */
        .table-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-top: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }

        th {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        tr:hover td {
            background: rgba(255,255,255,0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.lunas {
            background: rgba(6,214,160,0.2);
            color: #06D6A0;
        }

        .status-badge.belum {
            background: rgba(239,71,111,0.2);
            color: #EF476F;
        }

        .footer {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
            color: white;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .empty-state {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 40px;
            text-align: center;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }
            .nav-menu {
                justify-content: center;
                width: 100%;
            }
            .user-profile {
                width: 100%;
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php">Beranda</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php" class="active">Iuran</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php">Data KK</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'],0,1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
                </div>
            </a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-money-bill-wave"></i>
                <h1>Iuran Saya</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if (!$keluarga): ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h2>Anda belum terdaftar sebagai kepala keluarga</h2>
                <p>Silakan hubungi admin untuk menambahkan data KK Anda.</p>
            </div>
        <?php else: ?>
            <!-- Statistik -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <h3>Total Iuran</h3>
                    <div class="number"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Lunas</h3>
                    <div class="number"><?php echo $stats['lunas']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Belum Bayar</h3>
                    <div class="number"><?php echo $stats['belum']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h3>Total Dibayar</h3>
                    <div class="number">Rp <?php echo number_format($stats['total_uang'],0,',','.'); ?></div>
                </div>
            </div>

            <!-- Kegunaan Iuran -->
            <div class="kegunaan-box">
                <div class="kegunaan-title">
                    <i class="fas fa-hand-holding-heart"></i> 
                    <h2>Kegunaan Iuran Warga RT</h2>
                </div>
                <div class="kegunaan-list">
                    <div class="kegunaan-item">
                        <div class="kegunaan-icon"><i class="fas fa-road"></i></div>
                        <div class="kegunaan-text"><h4>Perbaikan Jalan Gang</h4><p>Perbaikan jalan gang dan fasilitas umum RT</p></div>
                    </div>
                    <div class="kegunaan-item">
                        <div class="kegunaan-icon"><i class="fas fa-hand-holding-medical"></i></div>
                        <div class="kegunaan-text"><h4>Bantuan Warga Sakit</h4><p>Bantuan pengobatan untuk warga yang sakit berat</p></div>
                    </div>
                    <div class="kegunaan-item">
                        <div class="kegunaan-icon"><i class="fas fa-heart"></i></div>
                        <div class="kegunaan-text"><h4>Santunan Wafat</h4><p>Santunan untuk keluarga warga yang meninggal</p></div>
                    </div>
                    <div class="kegunaan-item">
                        <div class="kegunaan-icon"><i class="fas fa-hands-helping"></i></div>
                        <div class="kegunaan-text"><h4>Bantuan Warga Tidak Mampu</h4><p>Bantuan sembako dan kebutuhan warga tidak mampu</p></div>
                    </div>
                    <div class="kegunaan-item">
                        <div class="kegunaan-icon"><i class="fas fa-house-damage"></i></div>
                        <div class="kegunaan-text"><h4>Bantuan Kemanusiaan</h4><p>Bencana alam dan kejadian darurat lainnya</p></div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="filter-section">
                <div class="filter-title"><i class="fas fa-filter"></i> Filter Riwayat Iuran</div>
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="tahun">Tahun</label>
                        <select name="tahun" id="tahun">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahun_list as $th): ?>
                                <option value="<?php echo $th; ?>" <?php if ($filter_tahun == $th) echo 'selected'; ?>><?php echo $th; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="bulan">Bulan</label>
                        <select name="bulan" id="bulan">
                            <option value="">Semua Bulan</option>
                            <option value="1" <?php if ($filter_bulan == 1) echo 'selected'; ?>>Januari</option>
                            <option value="2" <?php if ($filter_bulan == 2) echo 'selected'; ?>>Februari</option>
                            <option value="3" <?php if ($filter_bulan == 3) echo 'selected'; ?>>Maret</option>
                            <option value="4" <?php if ($filter_bulan == 4) echo 'selected'; ?>>April</option>
                            <option value="5" <?php if ($filter_bulan == 5) echo 'selected'; ?>>Mei</option>
                            <option value="6" <?php if ($filter_bulan == 6) echo 'selected'; ?>>Juni</option>
                            <option value="7" <?php if ($filter_bulan == 7) echo 'selected'; ?>>Juli</option>
                            <option value="8" <?php if ($filter_bulan == 8) echo 'selected'; ?>>Agustus</option>
                            <option value="9" <?php if ($filter_bulan == 9) echo 'selected'; ?>>September</option>
                            <option value="10" <?php if ($filter_bulan == 10) echo 'selected'; ?>>Oktober</option>
                            <option value="11" <?php if ($filter_bulan == 11) echo 'selected'; ?>>November</option>
                            <option value="12" <?php if ($filter_bulan == 12) echo 'selected'; ?>>Desember</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Terapkan</button>
                    </div>
                    <div class="filter-group">
                        <a href="iuran.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Tabel Iuran -->
            <div class="table-container">
                <?php if (count($iuran_list) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Periode Minggu</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($iuran_list as $i): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($i['week_start'])) . ' - ' . date('d M Y', strtotime($i['week_start'] . ' +6 days')); ?></td>
                            <td>Rp <?php echo number_format($i['amount'],0,',','.'); ?></td>
                            <td><span class="status-badge <?php echo $i['status']; ?>"><?php echo ucfirst($i['status']); ?></span></td>
                            <td><?php echo $i['payment_date'] ? date('d M Y', strtotime($i['payment_date'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5; margin-bottom: 15px;"></i>
                    <p>Tidak ada data iuran untuk periode yang dipilih.</p>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 e-RT Digital - Sistem Informasi RT</p>
    </footer>
</body>
</html>