<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ============================================
// Konfigurasi database (WAJIB SAMA DENGAN ADMIN)
// ============================================
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pengaduan_rt';  // HARUS SAMA DENGAN ADMIN

// Koneksi ke database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ============================================
// Ambil parameter pencarian (search)
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mengambil semua KK dengan join ke users (kepala keluarga)
$sql_kk = "SELECT k.*, u.nama as kepala_keluarga 
           FROM kartu_keluarga k 
           LEFT JOIN users u ON k.user_id = u.id 
           WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql_kk .= " AND (k.no_kk LIKE :search OR u.nama LIKE :search OR k.alamat LIKE :search)";
    $params[':search'] = "%$search%";
}
$sql_kk .= " ORDER BY k.id DESC";  // urutkan dari yang terbaru

$stmt_kk = $pdo->prepare($sql_kk);
$stmt_kk->execute($params);
$keluarga_list = $stmt_kk->fetchAll(PDO::FETCH_ASSOC);

// Untuk setiap KK, ambil anggota keluarganya
foreach ($keluarga_list as &$kk) {
    $stmt_anggota = $pdo->prepare("SELECT * FROM anggota_keluarga WHERE kk_id = ? ORDER BY 
        CASE status_keluarga 
            WHEN 'KEPALA KELUARGA' THEN 1 
            WHEN 'ISTRI' THEN 2 
            WHEN 'SUAMI' THEN 2 
            ELSE 3 
        END, tanggal_lahir");
    $stmt_anggota->execute([$kk['id']]);
    $kk['anggota'] = $stmt_anggota->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kartu Keluarga - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter','Segoe UI',sans-serif; }
        :root { --primary:#005461; --secondary:#249E94; --accent:#3BC1A8; --danger:#EF476F; }
        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover; min-height:100vh; position:relative; color:#fff;
        }
        body::before {
            content:''; position:fixed; top:0; left:0; width:100%; height:100%;
            background:linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter:blur(3px); z-index:-1;
        }
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
            flex-wrap: wrap;
        }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            background: var(--accent);
            width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 22px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .logo-text h1 { font-size: 22px; color: white; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-menu { display: flex; gap: 15px; flex-wrap: wrap; }
        .nav-menu a {
            color: white; text-decoration: none; font-weight: 500; padding: 8px 16px;
            border-radius: 30px; transition: 0.3s; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);
            white-space: nowrap;
        }
        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        .user-profile { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .user-profile a { text-decoration: none; color: white; display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 20px; border: 2px solid white;
        }
        .user-info { color: white; }
        .user-info h4 { font-size: 16px; line-height: 1.2; }
        .user-info small { font-size: 12px; opacity: 0.8; }
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
        .logout-btn:hover { background: var(--danger); }
        .container { max-width:1200px; margin:30px auto; padding:0 20px; }
        .page-header {
            display: flex; justify-content:space-between; align-items:center; margin-bottom:30px;
            padding:15px 25px; background:rgba(255,255,255,0.15); backdrop-filter:blur(12px);
            border-radius:50px; border:1px solid rgba(255,255,255,0.2);
        }
        .page-header-left { display:flex; align-items:center; gap:15px; }
        .page-header-left i { font-size:28px; color:var(--accent); }
        .page-header-left h1 { font-size:28px; font-weight:700; color:white; }
        .back-btn {
            background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3);
            color:white; padding:10px 20px; border-radius:40px; text-decoration:none;
            font-weight:600; transition:0.3s; display:flex; align-items:center; gap:8px;
        }
        .back-btn:hover { background:var(--secondary); border-color:var(--secondary); }
        /* Filter / Search */
        .filter-section {
            background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px;
            padding:20px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.2);
        }
        .filter-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .filter-form input {
            flex:1; min-width:200px; padding:12px 18px; border:2px solid rgba(255,255,255,0.2);
            border-radius:30px; background:rgba(255,255,255,0.1); color:white; font-size:16px;
        }
        .filter-form input:focus { outline:none; border-color:var(--accent); background:rgba(255,255,255,0.2); }
        .filter-form button {
            padding:12px 25px; border-radius:40px; font-weight:600; border:none; cursor:pointer;
            background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; transition:0.3s;
        }
        .filter-form button:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(36,158,148,0.5); }
        .family-card {
            background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px;
            margin-bottom:25px; border:1px solid rgba(255,255,255,0.2); overflow:hidden;
        }
        .family-header {
            background:rgba(0,0,0,0.2); padding:20px 25px; display:flex; flex-wrap:wrap;
            justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1);
        }
        .family-header h3 { color:white; font-size:20px; font-weight:700; }
        .family-header .no-kk {
            background:rgba(255,255,255,0.1); padding:5px 15px; border-radius:30px;
            font-size:14px; border:1px solid rgba(255,255,255,0.2);
        }
        .family-body { padding:25px; color:white; }
        .family-info-grid {
            display: grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:15px;
            background:rgba(0,0,0,0.2); padding:15px; border-radius:20px; margin-bottom:20px;
        }
        .info-item { font-size:14px; }
        .info-item strong { color:var(--accent); display:block; margin-bottom:5px; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th { background:rgba(0,0,0,0.3); color:white; padding:12px; font-size:14px; text-align:left; }
        td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.9); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.1); }
        .empty-state {
            background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px;
            padding:60px 20px; text-align:center; color:white; border:1px solid rgba(255,255,255,0.2);
        }
        .empty-state i { font-size:64px; color:rgba(255,255,255,0.3); margin-bottom:20px; }
        @media (max-width:768px) {
            .navbar { flex-direction:column; gap:15px; padding:1rem; }
            .nav-menu { justify-content:center; width:100%; }
            .user-profile { width:100%; justify-content:center; }
            .page-header { flex-direction:column; gap:15px; text-align:center; }
            .filter-form { flex-direction:column; }
            .filter-form input { width:100%; }
            .family-header { flex-direction:column; align-items:flex-start; gap:10px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php">Beranda</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php" class="active">Data KK</a>        
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
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-address-card"></i>
                <h1>Kartu Keluarga</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Form Pencarian -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="text" name="search" placeholder="Cari No. KK, Nama Kepala Keluarga, atau Alamat..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="kk.php" style="background:rgba(255,255,255,0.1); color:white; padding:12px 25px; border-radius:40px; text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Daftar Semua KK -->
        <?php if (count($keluarga_list) > 0): ?>
            <?php foreach ($keluarga_list as $kk): ?>
                <div class="family-card">
                    <div class="family-header">
                        <h3><i class="fas fa-users"></i> Keluarga <?php echo htmlspecialchars($kk['kepala_keluarga'] ?? 'Tidak Diketahui'); ?></h3>
                        <span class="no-kk"><i class="fas fa-id-card"></i> No. KK: <?php echo htmlspecialchars($kk['no_kk']); ?></span>
                    </div>
                    <div class="family-body">
                        <div class="family-info-grid">
                            <div class="info-item"><strong>Alamat</strong> <?php echo htmlspecialchars($kk['alamat']); ?></div>
                            <div class="info-item"><strong>RT/RW</strong> <?php echo htmlspecialchars($kk['rt_rw']); ?></div>
                            <div class="info-item"><strong>Desa/Kelurahan</strong> <?php echo htmlspecialchars($kk['desa_kelurahan']); ?></div>
                            <div class="info-item"><strong>Kecamatan</strong> <?php echo htmlspecialchars($kk['kecamatan']); ?></div>
                            <div class="info-item"><strong>Kabupaten</strong> <?php echo htmlspecialchars($kk['kabupaten']); ?></div>
                            <div class="info-item"><strong>Provinsi</strong> <?php echo htmlspecialchars($kk['provinsi']); ?></div>
                            <div class="info-item"><strong>Kode Pos</strong> <?php echo htmlspecialchars($kk['kode_pos']); ?></div>
                        </div>
                        <h4 style="margin-bottom:10px; color:var(--accent);"><i class="fas fa-list"></i> Daftar Anggota</h4>
                        <?php if (count($kk['anggota']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Tempat, Tgl Lahir</th>
                                        <th>JK</th>
                                        <th>Hubungan</th>
                                        <th>Status Kawin</th>
                                        <th>Pekerjaan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kk['anggota'] as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['nik']); ?></td>
                                        <td><?php echo htmlspecialchars($a['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($a['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($a['tanggal_lahir']))); ?></td>
                                        <td><?php echo $a['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                        <td><?php echo htmlspecialchars($a['status_keluarga']); ?></td>
                                        <td><?php echo htmlspecialchars($a['status_perkawinan']); ?></td>
                                        <td><?php echo htmlspecialchars($a['pekerjaan']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:rgba(255,255,255,0.8);">Tidak ada anggota keluarga.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-address-book"></i>
                <h3>Data KK Tidak Ditemukan</h3>
                <p style="color:rgba(255,255,255,0.8);">
                    <?php if (!empty($search)): ?>
                        Tidak ada hasil untuk pencarian "<?php echo htmlspecialchars($search); ?>".
                    <?php else: ?>
                        Belum ada data Kartu Keluarga.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>