<?php
session_start();
require_once(__DIR__ . '/../config/db.php'); // koneksi ke pengaduan_rt

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

// Ambil semua KK (kartu_keluarga) untuk ditampilkan
$query_kk = "SELECT k.*, u.nama as kepala_keluarga 
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
$filter_minggu = isset($_GET['minggu']) ? $_GET['minggu'] : ''; // format 'Y-m-d' week_start

// Tentukan rentang tanggal berdasarkan filter
if (!empty($filter_minggu)) {
    $week_start = $filter_minggu;
    $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
    $where_periode = "week_start = '$week_start'";
} else {
    // Ambil bulan yang dipilih
    $start_date = "$filter_tahun-$filter_bulan-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    $where_periode = "week_start BETWEEN '$start_date' AND '$end_date'";
}

// Ambil data pembayaran berdasarkan filter
$payments = [];
$query = "SELECT keluarga_id, status, payment_date, amount FROM iuran_payments WHERE $where_periode";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $payments[$row['keluarga_id']] = $row;
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
// Ambil minggu-minggu yang ada di database untuk bulan ini
$week_query = "SELECT DISTINCT week_start FROM iuran_payments WHERE YEAR(week_start)=$filter_tahun AND MONTH(week_start)=$filter_bulan ORDER BY week_start";
$week_result = $conn->query($week_query);
while ($row = $week_result->fetch_assoc()) {
    $weeks[] = $row['week_start'];
}
$weeks = array_unique($weeks);
sort($weeks);

// ========== PROSES MARK PAID ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $keluarga_id = (int)$_POST['keluarga_id'];
    $week_start = $_POST['week_start'];
    $payment_date = date('Y-m-d');
    
    // Cek apakah sudah ada record
    $check = $conn->prepare("SELECT id FROM iuran_payments WHERE keluarga_id = ? AND week_start = ?");
    $check->bind_param("is", $keluarga_id, $week_start);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        // Update status
        $update = $conn->prepare("UPDATE iuran_payments SET status = 'lunas', payment_date = ? WHERE keluarga_id = ? AND week_start = ?");
        $update->bind_param("sis", $payment_date, $keluarga_id, $week_start);
        $update->execute();
    } else {
        // Insert baru
        $insert = $conn->prepare("INSERT INTO iuran_payments (keluarga_id, week_start, amount, status, payment_date) VALUES (?, ?, 10000, 'lunas', ?)");
        $insert->bind_param("iss", $keluarga_id, $week_start, $payment_date);
        $insert->execute();
    }
    // Update saldo (tambah pemasukan)
    $conn->query("INSERT INTO iuran_kas (tanggal, keterangan, pemasukan) VALUES (CURDATE(), 'Iuran mingguan KK', 10000)");
    // Update saldo di tabel saldo
    $conn->query("UPDATE iuran_saldo SET saldo = saldo + 10000 WHERE id = 1");
    
    // Redirect dengan mempertahankan filter
    $query_string = http_build_query($_GET);
    header("Location: iuran.php?" . $query_string);
    exit();
}

// ========== DATA UNTUK GRAFIK (per bulan dalam tahun yang dipilih) ==========
$chart_tahun = $filter_tahun ?: date('Y');
$chart_labels = [];
$chart_data = [];
for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = date('M', mktime(0,0,0,$m,1,$chart_tahun));
    $bulan = sprintf("%04d-%02d", $chart_tahun, $m);
    // Gunakan week_start untuk menentukan bulan iuran
    $sum = $conn->query("SELECT SUM(amount) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan'")->fetch_assoc();
    $chart_data[] = (int)($sum['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Iuran - Admin e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS sama seperti sebelumnya */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        :root { --primary:#005461; --secondary:#249E94; --accent:#3BC1A8; --danger:#EF476F; }
        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover; min-height:100vh; position:relative; color:#fff; display:flex;
        }
        body::before {
            content:''; position:fixed; top:0; left:0; width:100%; height:100%;
            background:linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter:blur(3px); z-index:-1;
        }
        .sidebar { width:280px; background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.2); padding:30px 20px; display:flex; flex-direction:column; position:fixed; height:100vh; overflow-y:auto; }
        .sidebar .logo { display:flex; align-items:center; gap:10px; margin-bottom:40px; }
        .sidebar .logo-icon { background:var(--accent); width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; }
        .sidebar .nav-menu { flex:1; display:flex; flex-direction:column; gap:5px; }
        .sidebar .nav-menu a { display:flex; align-items:center; gap:15px; padding:12px 15px; color:rgba(255,255,255,0.9); text-decoration:none; border-radius:15px; transition:0.3s; }
        .sidebar .nav-menu a:hover, .sidebar .nav-menu a.active { background:var(--secondary); }
        .sidebar .user-profile { margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.2); }
        .sidebar .user-profile a { display:flex; align-items:center; gap:12px; color:white; text-decoration:none; }
        .sidebar .user-profile .avatar { width:45px; height:45px; border-radius:50%; background:linear-gradient(145deg, var(--secondary), var(--accent)); display:flex; align-items:center; justify-content:center; font-weight:bold; border:2px solid white; }
        .sidebar .logout-btn { background:rgba(239,71,111,0.2); border:1px solid rgba(255,255,255,0.2); color:white; padding:8px 12px; border-radius:30px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:10px; }
        .main-content { flex:1; margin-left:280px; padding:30px; }
        .content-header { background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px; padding:20px 30px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; }
        .btn-primary { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:12px 25px; border-radius:40px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-secondary { background:rgba(239,71,111,0.2); color:white; border:1px solid rgba(239,71,111,0.3); padding:12px 25px; border-radius:40px; text-decoration:none; }
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:30px; }
        .stat-card { background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-radius:30px; padding:20px; }
        .stat-card .number { font-size:32px; font-weight:700; }
        .table-container { background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-top:30px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; color:white; }
        th, td { padding:15px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:12px; }
        .lunas { background:rgba(6,214,160,0.2); color:#06D6A0; }
        .belum { background:rgba(239,71,111,0.2); color:#EF476F; }
        .filter-section { background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-bottom:30px; }
        .filter-form { display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap; }
        .filter-group { flex:1 1 150px; }
        .filter-group label { display:block; margin-bottom:5px; font-size:14px; color:rgba(255,255,255,0.9); }
        .filter-group select, .filter-group input { width:100%; padding:10px; border:2px solid rgba(255,255,255,0.2); border-radius:30px; background:rgba(255,255,255,0.1); color:white; }
        .filter-group select option { background:var(--primary); }
        .btn-filter { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:10px 25px; border-radius:40px; cursor:pointer; }
        .btn-reset { background:rgba(239,71,111,0.2); color:white; border:1px solid rgba(239,71,111,0.3); padding:10px 25px; border-radius:40px; text-decoration:none; display:inline-block; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); justify-content:center; align-items:center; }
        .modal-content { background:rgba(255,255,255,0.15); backdrop-filter:blur(20px); border-radius:30px; padding:30px; max-width:400px; width:90%; }
        @media (max-width:768px) { .sidebar { transform:translateX(-100%); } .main-content { margin-left:0; } .filter-form { flex-direction:column; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
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

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label>Tahun</label>
                    <select name="tahun">
                        <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php if($filter_tahun == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Bulan</label>
                    <select name="bulan">
                        <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php if($filter_bulan == $m) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Minggu (opsional)</label>
                    <select name="minggu">
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
                <h3>Total KK</h3>
                <div class="number"><?php echo $total_kk; ?></div>
            </div>
            <div class="stat-card">
                <h3>Lunas</h3>
                <div class="number"><?php echo $total_lunas; ?></div>
            </div>
            <div class="stat-card">
                <h3>Belum Bayar</h3>
                <div class="number"><?php echo $total_belum; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Uang</h3>
                <div class="number">Rp <?php echo number_format($total_uang,0,',','.'); ?></div>
            </div>
        </div>

        <!-- Grafik Pemasukan per Bulan (Tahun <?php echo $chart_tahun; ?>) -->
        <div style="background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-bottom:30px;">
            <h3>Grafik Pemasukan Iuran Tahun <?php echo $chart_tahun; ?></h3>
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
                        $status = isset($payments[$kk['id']]) ? $payments[$kk['id']]['status'] : 'belum';
                        $payment_date = isset($payments[$kk['id']]) ? $payments[$kk['id']]['payment_date'] : null;
                        $class = ($status == 'lunas') ? 'lunas' : 'belum';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kk['no_kk']); ?></td>
                        <td><?php echo htmlspecialchars($kk['kepala_keluarga'] ?? '-'); ?></td>
                        <td><span class="status-badge <?php echo $class; ?>"><?php echo ucfirst($status); ?></span></td>
                        <td><?php echo $payment_date ? date('d M Y', strtotime($payment_date)) : '-'; ?></td>
                        <td>
                            <?php if ($status != 'lunas'): ?>
                            <button class="btn-primary" onclick="markPaid(<?php echo $kk['id']; ?>, '<?php echo $filter_minggu ?: ''; ?>')">Tandai Lunas</button>
                            <?php else: ?>
                            <span>Lunas</span>
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
            <form method="POST">
                <input type="hidden" name="keluarga_id" id="keluarga_id">
                <input type="hidden" name="week_start" id="week_start">
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" name="mark_paid" class="btn-primary">Ya, Lunas</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function markPaid(id, week) {
            if (!week) {
                alert('Pilih minggu terlebih dahulu!');
                return;
            }
            document.getElementById('keluarga_id').value = id;
            document.getElementById('week_start').value = week;
            document.getElementById('paidModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('paidModal').style.display = 'none';
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
                    backgroundColor: 'rgba(59, 193, 168, 0.5)',
                    borderColor: '#3BC1A8',
                    borderWidth: 1
                }]
            },
            options: { 
                scales: { y: { beginAtZero: true } },
                plugins: {
                    title: { display: true, text: 'Pemasukan Iuran Tahun <?php echo $chart_tahun; ?>' }
                }
            }
        });
    </script>
</body>
</html>