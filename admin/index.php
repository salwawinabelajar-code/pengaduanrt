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

// Statistik umum
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

// Iuran bulan ini
$bulan_ini = date('Y-m');
$query = "SELECT SUM(jumlah) as total FROM iuran WHERE status='lunas' AND DATE_FORMAT(periode, '%Y-%m') = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $bulan_ini);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['iuran_bulan_ini'] = ($result && mysqli_num_rows($result) > 0) ? (int)mysqli_fetch_assoc($result)['total'] : 0;
    mysqli_stmt_close($stmt);
} else {
    $stats['iuran_bulan_ini'] = 0;
}

// Data untuk grafik pengaduan per bulan (6 bulan terakhir)
$bulan_labels = [];
$pengaduan_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_labels[] = date('M Y', strtotime($bulan . '-01'));
    $query = "SELECT COUNT(*) as total FROM pengaduan WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $bulan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $pengaduan_bulan[] = $row['total'] ?? 0;
    mysqli_stmt_close($stmt);
}

// Data untuk grafik surat per status (doughnut)
$surat_chart_data = [
    $stats['surat_menunggu'],
    $stats['surat_diproses'],
    $stats['surat_selesai'],
    $stats['surat_ditolak']
];

// Ambil 5 pengaduan terbaru
$query = "SELECT p.*, u.nama FROM pengaduan p JOIN users u ON p.user_id = u.id ORDER BY p.tanggal DESC LIMIT 5";
$result_pengaduan = mysqli_query($conn, $query);

// Ambil 5 surat terbaru
$query = "SELECT s.*, u.nama FROM pengajuan_surat s JOIN users u ON s.user_id = u.id ORDER BY s.tanggal_pengajuan DESC LIMIT 5";
$result_surat = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        :root {
            --primary: #ffffff;
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
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }
        .app { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,0.2);
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
            color: white; font-size: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
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
            margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);
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
            background: rgba(239,71,111,0.2); border: 1px solid rgba(255,255,255,0.2);
            color: white; padding: 8px 12px; border-radius: 30px; text-decoration: none;
            font-size: 13px; font-weight: 600; transition: 0.3s; display: flex;
            align-items: center; justify-content: center; gap: 5px; margin-top: 10px;
        }
        .sidebar .logout-btn:hover { background: var(--danger); }
        .main-content {
            flex: 1; margin-left: 280px; padding: 30px;
        }
        .content-header {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 20px 30px; margin-bottom: 30px; display: flex; justify-content: space-between;
            align-items: center; border: 1px solid rgba(255,255,255,0.2);
        }
        .content-header h1 { font-size: 28px; font-weight: 700; color: white; }
        .content-header .date {
            background: rgba(0,0,0,0.2); padding: 8px 20px; border-radius: 40px;
            color: white; display: flex; align-items: center; gap: 8px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .welcome-message {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 30px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2);
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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 25px; border: 1px solid rgba(255,255,255,0.2); transition: 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px); background: rgba(255,255,255,0.2); border-color: var(--accent);
        }
        .stat-card .stat-icon {
            font-size: 32px; margin-bottom: 15px; display: inline-block; width: 60px; height: 60px;
            line-height: 60px; border-radius: 50%; background: rgba(255,255,255,0.2);
            color: var(--accent); border: 1px solid rgba(255,255,255,0.3); text-align: center;
        }
        .stat-card h3 {
            font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 10px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .stat-card .number { font-size: 32px; font-weight: 800; color: white; }
        .charts-row {
            display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px;
        }
        @media (max-width: 900px) {
            .charts-row { grid-template-columns: 1fr; }
        }
        .chart-card {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 25px; border: 1px solid rgba(255,255,255,0.2);
        }
        .chart-card h3 {
            margin-bottom: 20px; font-size: 18px; color: white; display: flex; align-items: center; gap: 10px;
        }
        .chart-card canvas { max-height: 300px; }
        .recent-section {
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;
        }
        @media (max-width: 900px) {
            .recent-section { grid-template-columns: 1fr; }
        }
        .recent-card {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 25px; border: 1px solid rgba(255,255,255,0.2);
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
            display: flex; gap: 15px; font-size: 12px; color: rgba(255,255,255,0.7);
        }
        .recent-item .status {
            display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .status-baru, .status-menunggu { background: rgba(255,209,102,0.2); color: #FFD166; }
        .status-diproses { background: rgba(17,138,178,0.2); color: #118AB2; }
        .status-selesai { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .status-ditolak { background: rgba(239,71,111,0.2); color: #EF476F; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; position: fixed; top: 20px; left: 20px; z-index: 200;
                background: var(--secondary); border: none; color: white; width: 45px; height: 45px;
                border-radius: 50%; font-size: 20px; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
        }
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
                    <h3>Diproses</h3>
                    <div class="number"><?php echo $stats['pengaduan_diproses']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Selesai</h3>
                    <div class="number"><?php echo $stats['pengaduan_selesai']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Surat Menunggu</h3>
                    <div class="number"><?php echo $stats['surat_menunggu']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h3>Warga</h3>
                    <div class="number"><?php echo $stats['total_warga']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-address-card"></i></div>
                    <h3>Kartu Keluarga</h3>
                    <div class="number"><?php echo $stats['total_kk']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h3>Iuran Bulan Ini</h3>
                    <div class="number">Rp <?php echo number_format($stats['iuran_bulan_ini'], 0, ',', '.'); ?></div>
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
            </div>
        </div>
    </div>

    <!-- Tombol toggle untuk mobile -->
    <button class="menu-toggle" id="menuToggle" style="display: none;"><i class="fas fa-bars"></i></button>

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
                    backgroundColor: [
                        '#FFD166',
                        '#118AB2',
                        '#06D6A0',
                        '#EF476F'
                    ],
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
    </script>
</body>
</html>
<?php
// Tutup koneksi
mysqli_close($conn);
?>