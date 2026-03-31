<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user untuk sidebar
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Cek apakah tabel pengumuman ada, jika tidak buat
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'pengumuman'");
if (mysqli_num_rows($table_check) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS pengumuman (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        tanggal DATE NOT NULL,
        penting TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_table);
} else {
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM pengumuman LIKE 'penting'");
    if (mysqli_num_rows($column_check) == 0) {
        mysqli_query($conn, "ALTER TABLE pengumuman ADD penting TINYINT DEFAULT 0 AFTER tanggal");
    }
}

$message = '';
$error = '';

// Proses tambah/edit pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $tanggal = !empty($_POST['tanggal']) ? mysqli_real_escape_string($conn, $_POST['tanggal']) : date('Y-m-d');
    $penting = isset($_POST['penting']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (empty($judul) || empty($isi)) {
        $error = "Judul dan isi pengumuman harus diisi.";
    } else {
        if ($id > 0) {
            $query = "UPDATE pengumuman SET judul='$judul', isi='$isi', tanggal='$tanggal', penting='$penting' WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = "Pengumuman berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui pengumuman: " . mysqli_error($conn);
            }
        } else {
            $query = "INSERT INTO pengumuman (judul, isi, tanggal, penting) VALUES ('$judul', '$isi', '$tanggal', '$penting')";
            if (mysqli_query($conn, $query)) {
                $message = "Pengumuman berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan pengumuman: " . mysqli_error($conn);
            }
        }
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $query = "DELETE FROM pengumuman WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "Pengumuman berhasil dihapus.";
    } else {
        $error = "Gagal menghapus pengumuman: " . mysqli_error($conn);
    }
}

// Ambil data untuk diedit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $query = "SELECT * FROM pengumuman WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    }
}

// Ambil semua pengumuman
$query = "SELECT * FROM pengumuman ORDER BY penting DESC, tanggal DESC, created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman - Admin e-RT Digital</title>
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
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0, 84, 97, 0.85) 0%, rgba(36, 158, 148, 0.75) 100%);
            backdrop-filter: blur(4px);
            z-index: -1;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--glass-border);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
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
        
        .sidebar .nav-menu a:hover,
        .sidebar .nav-menu a.active {
            background: var(--secondary);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(36,158,148,0.3);
        }
        
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
        
        .sidebar .user-profile .info h4 { font-size: 14px; color: white; }
        .sidebar .user-profile .info p { font-size: 12px; color: rgba(255,255,255,0.7); }
        
        .sidebar .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid var(--glass-border);
            color: white; padding: 8px 12px; border-radius: 30px; text-decoration: none;
            font-size: 13px; font-weight: 600; transition: 0.3s; display: flex;
            align-items: center; justify-content: center; gap: 5px; margin-top: 10px;
        }
        
        .sidebar .logout-btn:hover { background: var(--danger); }
        
        /* Main Content */
        .main-content {
            flex: 1; margin-left: 280px; padding: 30px; min-height: 100vh;
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
            color: transparent; display: flex; align-items: center; gap: 15px;
        }
        
        .content-header h1 i { background: none; color: var(--accent); -webkit-background-clip: unset; }
        
        .back-btn {
            background: rgba(255,255,255,0.15); border: 1px solid var(--glass-border);
            color: white; padding: 10px 24px; border-radius: 40px; text-decoration: none;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        
        .back-btn:hover { background: var(--secondary); transform: translateY(-2px); }
        
        .form-card {
            background: var(--glass-bg); backdrop-filter: blur(12px);
            border-radius: 28px; padding: 30px; border: 1px solid var(--glass-border);
            margin-bottom: 40px;
        }
        
        .form-card h2 {
            margin-bottom: 20px; color: white; font-size: 24px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .form-group { margin-bottom: 20px; }
        
        label { display: block; margin-bottom: 8px; font-weight: 600; color: white; }
        
        .form-control {
            width: 100%; padding: 12px 15px; border: 2px solid var(--glass-border);
            border-radius: 30px; background: rgba(255,255,255,0.1); color: white;
            font-size: 14px; font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none; border-color: var(--accent); background: rgba(255,255,255,0.2);
        }
        
        textarea.form-control { min-height: 150px; resize: vertical; }
        
        .checkbox-group {
            display: flex; align-items: center; gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px; height: 20px; accent-color: var(--accent);
        }
        
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        
        .btn {
            padding: 12px 25px; border: none; border-radius: 40px; font-weight: 600;
            cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
        }
        
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(36,158,148,0.4); }
        
        .btn-secondary {
            background: rgba(239,71,111,0.2); color: white; border: 1px solid rgba(239,71,111,0.3);
        }
        
        .btn-secondary:hover { background: var(--danger); }
        
        .message {
            padding: 12px 20px; border-radius: 30px; margin-bottom: 20px;
            background: rgba(6,214,160,0.2); border: 1px solid rgba(6,214,160,0.3);
            color: white;
        }
        
        .error {
            background: rgba(239,71,111,0.2); border-color: rgba(239,71,111,0.3);
        }
        
        .table-container {
            background: var(--glass-bg); backdrop-filter: blur(12px);
            border-radius: 28px; padding: 25px; border: 1px solid var(--glass-border);
        }
        
        .table-container h2 {
            margin-bottom: 20px; color: white; font-size: 24px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .pengumuman-item {
            background: rgba(255,255,255,0.05); border-radius: 20px; padding: 15px;
            margin-bottom: 15px; border-left: 4px solid transparent; transition: 0.3s;
        }
        
        .pengumuman-item.penting {
            border-left: 6px solid var(--warning);
            background: linear-gradient(90deg, rgba(255,209,102,0.15) 0%, rgba(255,255,255,0.05) 100%);
        }
        
        .pengumuman-item.penting:hover {
            background: linear-gradient(90deg, rgba(255,209,102,0.25) 0%, rgba(255,255,255,0.1) 100%);
        }
        
        .pengumuman-item h3 {
            font-size: 18px; margin-bottom: 5px; color: white;
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        
        .pengumuman-item .penting-icon { color: var(--warning); font-size: 16px; }
        
        .pengumuman-item .penting-badge {
            background: var(--warning); color: var(--dark); font-size: 10px;
            font-weight: 700; padding: 2px 8px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .pengumuman-item .date {
            font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 10px;
            display: flex; align-items: center; gap: 5px;
        }
        
        .pengumuman-item p {
            color: rgba(255,255,255,0.9); line-height: 1.5; margin-bottom: 10px;
        }
        
        .pengumuman-item .actions {
            display: flex; gap: 10px; justify-content: flex-end;
        }
        
        .btn-action {
            padding: 6px 12px; border-radius: 20px; font-size: 12px; text-decoration: none;
            transition: 0.3s; display: inline-flex; align-items: center; gap: 5px;
            border: 1px solid var(--glass-border); background: rgba(255,255,255,0.1);
            color: white; cursor: pointer;
        }
        
        .btn-action:hover { transform: translateY(-2px); }
        .btn-edit { background: rgba(255,209,102,0.2); color: #FFD166; }
        .btn-edit:hover { background: rgba(255,209,102,0.4); }
        .btn-delete { background: rgba(239,71,111,0.2); color: #EF476F; }
        .btn-delete:hover { background: rgba(239,71,111,0.4); }
        
        .footer {
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0; padding: 30px 20px; margin-top: 40px;
            text-align: center; color: white; border-top: 1px solid var(--glass-border);
        }
        
        .footer p { margin: 8px 0; }
        .footer .registered { font-weight: 500; font-size: 1.1rem; }
        .footer .edit-link { color: var(--accent); text-decoration: none; font-weight: 600; }
        .footer .edit-link:hover { text-decoration: underline; }
        .footer .copyright { color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-top: 12px; }
        
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
            <a href="pengumuman.php" class="active"><i class="fas fa-bullhorn"></i> Pengumuman</a>
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

    <div class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-bullhorn"></i> Kelola Pengumuman</h1>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Form Tambah/Edit -->
        <div class="form-card">
            <h2><i class="fas fa-<?php echo $edit_data ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_data ? 'Edit Pengumuman' : 'Tambah Pengumuman Baru'; ?></h2>
            <form method="POST" action="">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="judul">Judul Pengumuman</label>
                    <input type="text" name="judul" id="judul" class="form-control" value="<?php echo $edit_data ? htmlspecialchars($edit_data['judul']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="isi">Isi Pengumuman</label>
                    <textarea name="isi" id="isi" class="form-control" required><?php echo $edit_data ? htmlspecialchars($edit_data['isi']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="tanggal">Tanggal (opsional, default hari ini)</label>
                    <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?php echo $edit_data ? htmlspecialchars($edit_data['tanggal']) : date('Y-m-d'); ?>">
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="penting" id="penting" <?php echo ($edit_data && $edit_data['penting'] == 1) ? 'checked' : ''; ?>>
                    <label for="penting">Tandai sebagai pengumuman penting</label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_data ? 'Perbarui' : 'Simpan'; ?></button>
                    <?php if ($edit_data): ?>
                        <a href="pengumuman.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Daftar Pengumuman -->
        <div class="table-container">
            <h2><i class="fas fa-history"></i> Riwayat Pengumuman</h2>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $penting_class = $row['penting'] ? 'penting' : '';
                ?>
                    <div class="pengumuman-item <?php echo $penting_class; ?>">
                        <h3>
                            <?php if ($row['penting']): ?>
                                <i class="fas fa-exclamation-triangle penting-icon"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($row['judul']); ?>
                            <?php if ($row['penting']): ?>
                                <span class="penting-badge">PENTING</span>
                            <?php endif; ?>
                        </h3>
                        <div class="date">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['tanggal'])); ?>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($row['isi'])); ?></p>
                        <div class="actions">
                            <a href="?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"><i class="fas fa-trash"></i> Hapus</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 30px;">Belum ada pengumuman. Silakan tambahkan pengumuman baru.</p>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
            <p><a href="profil.php?edit=1" class="edit-link">Edit Profil</a></p>
            <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
        </footer>
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
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>