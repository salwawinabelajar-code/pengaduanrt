<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// ========== PROSES AKUN WARGA ==========
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $new_status = $_GET['status'] == 'aktif' ? 'nonaktif' : 'aktif';
    mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id=$id");
    header("Location: pengaturan.php");
    exit();
}

if (isset($_GET['hapus_akun'])) {
    $id = (int)$_GET['hapus_akun'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
    header("Location: pengaturan.php");
    exit();
}

$query_warga = "SELECT * FROM users WHERE role='warga' ORDER BY id DESC";
$result_warga = mysqli_query($conn, $query_warga);

// ========== PROSES KATEGORI ==========
if (isset($_POST['tambah_kategori'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    if (!empty($nama)) {
        $query = "INSERT INTO kategori_pengaduan (nama, deskripsi) VALUES ('$nama', '$deskripsi')";
        mysqli_query($conn, $query);
    }
    header("Location: pengaturan.php");
    exit();
}

if (isset($_GET['hapus_kategori'])) {
    $id = (int)$_GET['hapus_kategori'];
    $check = mysqli_query($conn, "SELECT id FROM pengaduan WHERE kategori='$id'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "DELETE FROM kategori_pengaduan WHERE id=$id");
    }
    header("Location: pengaturan.php");
    exit();
}

$query_kategori = "SELECT * FROM kategori_pengaduan ORDER BY id DESC";
$result_kategori = mysqli_query($conn, $query_kategori);
if (!$result_kategori) {
    $create = "CREATE TABLE IF NOT EXISTS kategori_pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(50) NOT NULL UNIQUE,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create);
    $defaults = [
        ['Kebersihan', 'Laporan terkait kebersihan'],
        ['Keamanan', 'Laporan terkait keamanan'],
        ['Infrastruktur', 'Laporan terkait infrastruktur'],
        ['Sosial', 'Laporan sosial'],
        ['Lainnya', 'Kategori lainnya']
    ];
    foreach ($defaults as $d) {
        mysqli_query($conn, "INSERT IGNORE INTO kategori_pengaduan (nama, deskripsi) VALUES ('$d[0]', '$d[1]')");
    }
    $result_kategori = mysqli_query($conn, $query_kategori);
}

// ========== PROSES FAQ / BANTUAN ==========
$create_faq = "CREATE TABLE IF NOT EXISTS bantuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    kategori VARCHAR(50) DEFAULT 'umum',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_faq);

if (isset($_POST['tambah_faq'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = (int)$_POST['urutan'];
    $query = "INSERT INTO bantuan (judul, konten, kategori, urutan) VALUES ('$judul', '$konten', '$kategori', '$urutan')";
    mysqli_query($conn, $query);
    header("Location: pengaturan.php");
    exit();
}

if (isset($_POST['edit_faq'])) {
    $id = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = (int)$_POST['urutan'];
    $query = "UPDATE bantuan SET judul='$judul', konten='$konten', kategori='$kategori', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: pengaturan.php");
    exit();
}

if (isset($_GET['hapus_faq'])) {
    $id = (int)$_GET['hapus_faq'];
    mysqli_query($conn, "DELETE FROM bantuan WHERE id=$id");
    header("Location: pengaturan.php");
    exit();
}

$query_faq = "SELECT * FROM bantuan ORDER BY urutan ASC, id DESC";
$result_faq = mysqli_query($conn, $query_faq);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
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
            font-family: 'Inter', sans-serif;
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #fff;
            position: relative;
            display: flex;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, rgba(0, 84, 97, 0.85) 0%, rgba(36, 158, 148, 0.75) 100%);
            backdrop-filter: blur(4px);
            z-index: -1;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,0.2);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }
        
        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
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
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .sidebar .nav-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 15px;
            transition: 0.3s;
            font-weight: 500;
        }
        
        .sidebar .nav-menu a i { width: 24px; font-size: 18px; }
        
        .sidebar .nav-menu a:hover,
        .sidebar .nav-menu a.active {
            background: var(--secondary);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(36,158,148,0.3);
        }
        
        .sidebar .user-profile {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .sidebar .user-profile a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }
        
        .sidebar .user-profile .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 18px; border: 2px solid white;
        }
        
        .sidebar .user-profile .info h4 {
            font-size: 14px; color: white;
        }
        
        .sidebar .user-profile .info p {
            font-size: 12px; color: rgba(255,255,255,0.7);
        }
        
        .sidebar .logout-btn {
            background: rgba(239,71,111,0.2);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 12px;
            border-radius: 30px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-top: 10px;
            transition: 0.3s;
        }
        
        .sidebar .logout-btn:hover {
            background: var(--danger);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 16px 32px;
            border: 1px solid var(--glass-border);
        }
        
        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header-left i {
            font-size: 28px;
            color: var(--accent);
            filter: drop-shadow(0 0 6px rgba(59, 193, 168, 0.4));
        }
        
        .page-header-left h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 10px 24px;
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
            transform: translateY(-2px);
        }
        
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            padding: 25px;
            border: 1px solid var(--glass-border);
            margin-bottom: 30px;
        }
        
        .card h3 {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: 600;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--glass-border);
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(5px);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        
        .btn-danger {
            background: rgba(239,71,111,0.2);
            border-color: rgba(239,71,111,0.3);
        }
        
        .btn-danger:hover {
            background: var(--danger);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        th {
            background: rgba(0,0,0,0.2);
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-aktif { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .status-nonaktif { background: rgba(239,71,111,0.2); color: #EF476F; }
        
        .table-container {
            overflow-x: auto;
        }
        
        .footer {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            margin-top: 40px;
            text-align: center;
            color: white;
            border-top: 1px solid var(--glass-border);
        }
        
        .footer p {
            margin: 8px 0;
        }
        
        .footer .registered {
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .footer .edit-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer .edit-link:hover {
            text-decoration: underline;
        }
        
        .footer .copyright {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            margin-top: 12px;
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; position: fixed; z-index: 1000; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
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
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
            <a href="pengaturan.php" class="active"><i class="fas fa-cog"></i> Pengaturan</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="page-header-left">
                    <i class="fas fa-cog"></i>
                    <h1>Pengaturan Admin</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            </div>

            <!-- Manajemen Akun Warga -->
            <div class="card">
                <h3><i class="fas fa-users"></i> Manajemen Akun Warga</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($w = mysqli_fetch_assoc($result_warga)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $w['id']; ?></td>
                                <td><?php echo htmlspecialchars($w['nama']); ?></td>
                                <td><?php echo htmlspecialchars($w['username']); ?></td>
                                <td><?php echo htmlspecialchars($w['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $w['status']; ?>">
                                        <?php echo ucfirst($w['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle_status=<?php echo $w['id']; ?>&status=<?php echo $w['status']; ?>" class="btn" style="padding:5px 12px; font-size:12px;">
                                        <?php echo $w['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                    </a>
                                    <a href="?hapus_akun=<?php echo $w['id']; ?>" class="btn btn-danger" style="padding:5px 12px; font-size:12px;" onclick="return confirm('Yakin hapus akun ini? Semua data terkait akan ikut terhapus.')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Manajemen Kategori Pengaduan -->
            <div class="card">
                <h3><i class="fas fa-tags"></i> Kategori Pengaduan</h3>
                <div style="display:flex; gap:30px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:250px;">
                        <h4 style="color:var(--accent); margin-bottom:15px;">Tambah Kategori</h4>
                        <form method="POST">
                            <div class="form-group">
                                <input type="text" name="nama" placeholder="Nama kategori" required>
                            </div>
                            <div class="form-group">
                                <textarea name="deskripsi" placeholder="Deskripsi (opsional)" rows="2"></textarea>
                            </div>
                            <button type="submit" name="tambah_kategori" class="btn">Tambah Kategori</button>
                        </form>
                    </div>
                    <div style="flex:2;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr><th>No</th><th>Nama</th><th>Deskripsi</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php $no_kat = 1; while ($k = mysqli_fetch_assoc($result_kategori)): ?>
                                    <tr>
                                        <td><?php echo $no_kat++; ?></td>
                                        <td><?php echo htmlspecialchars($k['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                                        <td>
                                            <a href="?hapus_kategori=<?php echo $k['id']; ?>" class="btn btn-danger" style="padding:5px 12px;" onclick="return confirm('Yakin hapus? Pastikan tidak ada pengaduan dengan kategori ini.')">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manajemen FAQ / Bantuan -->
            <div class="card">
                <h3><i class="fas fa-question-circle"></i> FAQ / Bantuan</h3>
                
                <div style="margin-bottom:30px;">
                    <h4 style="color:var(--accent); margin-bottom:15px;">Tambah FAQ</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" required>
                        </div>
                        <div class="form-group">
                            <label>Konten</label>
                            <textarea name="konten" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori">
                                <option value="umum">Umum</option>
                                <option value="pengaduan">Pengaduan</option>
                                <option value="surat">Surat</option>
                                <option value="iuran">Iuran</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Urutan (semakin kecil semakin atas)</label>
                            <input type="number" name="urutan" value="0">
                        </div>
                        <button type="submit" name="tambah_faq" class="btn">Tambah FAQ</button>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Urutan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no_faq = 1; while ($f = mysqli_fetch_assoc($result_faq)): ?>
                            <tr>
                                <td><?php echo $no_faq++; ?></td>
                                <td><?php echo htmlspecialchars($f['judul']); ?></td>
                                <td><?php echo $f['kategori']; ?></td>
                                <td><?php echo $f['urutan']; ?></td>
                                <td>
                                    <a href="#" onclick="editFaq(<?php echo $f['id']; ?>)" class="btn" style="padding:5px 12px;">Edit</a>
                                    <a href="?hapus_faq=<?php echo $f['id']; ?>" class="btn btn-danger" style="padding:5px 12px;" onclick="return confirm('Hapus FAQ ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
            <p><a href="profil.php?edit=1" class="edit-link">Edit Profil</a></p>
            <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
        </footer>
    </div>

    <!-- Modal Edit FAQ -->
    <div id="editFaqModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); justify-content:center; align-items:center; z-index:2000;">
        <div style="background:var(--glass-bg); backdrop-filter:blur(20px); border-radius:32px; padding:30px; max-width:600px; width:90%; border:1px solid var(--glass-border);">
            <h3 style="color:white; margin-bottom:20px;">Edit FAQ</h3>
            <form method="POST" id="editFaqForm">
                <input type="hidden" name="id" id="edit_faq_id">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" id="edit_faq_judul" required>
                </div>
                <div class="form-group">
                    <label>Konten</label>
                    <textarea name="konten" id="edit_faq_konten" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" id="edit_faq_kategori">
                        <option value="umum">Umum</option>
                        <option value="pengaduan">Pengaduan</option>
                        <option value="surat">Surat</option>
                        <option value="iuran">Iuran</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Urutan</label>
                    <input type="number" name="urutan" id="edit_faq_urutan">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="edit_faq" class="btn">Simpan</button>
                    <button type="button" onclick="closeEditFaq()" class="btn btn-danger">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editFaq(id) {
            fetch(`get_faq.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_faq_id').value = data.data.id;
                        document.getElementById('edit_faq_judul').value = data.data.judul;
                        document.getElementById('edit_faq_konten').value = data.data.konten;
                        document.getElementById('edit_faq_kategori').value = data.data.kategori;
                        document.getElementById('edit_faq_urutan').value = data.data.urutan;
                        document.getElementById('editFaqModal').style.display = 'flex';
                    } else {
                        alert('Gagal memuat data');
                    }
                });
        }
        function closeEditFaq() {
            document.getElementById('editFaqModal').style.display = 'none';
        }
    </script>
</body>
</html>