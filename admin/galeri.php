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

// Buat tabel galeri jika belum ada
$create_table = "CREATE TABLE IF NOT EXISTS galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255) NOT NULL,
    tanggal DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

// Pastikan folder uploads ada dan writable
$target_dir = __DIR__ . '/../uploads/galeri/';
$compressed_dir = __DIR__ . '/../uploads/compressed/';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}
if (!is_dir($compressed_dir)) {
    mkdir($compressed_dir, 0755, true);
}

$error_messages = [];
if (!is_writable($target_dir)) {
    $error_messages[] = "Folder uploads/galeri tidak dapat ditulis. Ubah permission menjadi 755 atau 777.";
}
if (!is_writable($compressed_dir)) {
    $error_messages[] = "Folder uploads/compressed tidak dapat ditulis.";
}

// Proses upload
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload') {
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $message = "Error upload file: " . ($_FILES['foto']['error'] ?? 'No file');
            $message_type = "error";
        } else {
            $file = $_FILES['foto'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $target_dir . $file_name;
            
            $check = getimagesize($file['tmp_name']);
            if ($check === false) {
                $message = "File bukan gambar valid.";
                $message_type = "error";
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $message = "Ukuran file terlalu besar (maks 10MB).";
                $message_type = "error";
            } elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $message = "Hanya file JPG, JPEG, PNG, GIF & WEBP yang diperbolehkan.";
                $message_type = "error";
            } elseif (move_uploaded_file($file['tmp_name'], $target_file)) {
                $foto_path = "uploads/galeri/" . $file_name;
                $query = "INSERT INTO galeri (judul, deskripsi, foto, tanggal) VALUES ('$judul', '$deskripsi', '$foto_path', '$tanggal')";
                if (mysqli_query($conn, $query)) {
                    $message = "✅ Foto berhasil diupload!";
                    $message_type = "success";
                    $compressed_file = $compressed_dir . str_replace('/', '_', $foto_path);
                    if (file_exists($compressed_file)) unlink($compressed_file);
                } else {
                    $message = "Gagal menyimpan ke database: " . mysqli_error($conn);
                    $message_type = "error";
                    unlink($target_file);
                }
            } else {
                $message = "Gagal memindahkan file. Cek folder uploads/galeri.";
                $message_type = "error";
            }
        }
    }
    elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        
        $query = "UPDATE galeri SET judul='$judul', deskripsi='$deskripsi', tanggal='$tanggal' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Data berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
    elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $query = "SELECT foto FROM galeri WHERE id=$id";
        $result = mysqli_query($conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            $foto_path = $row['foto'];
            $full_path = __DIR__ . '/../' . $foto_path;
            if (file_exists($full_path)) unlink($full_path);
            $compressed_file = $compressed_dir . str_replace('/', '_', $foto_path);
            if (file_exists($compressed_file)) unlink($compressed_file);
        }
        $query = "DELETE FROM galeri WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Foto berhasil dihapus.";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Ambil data galeri
$query = "SELECT * FROM galeri ORDER BY tanggal DESC, id DESC";
$result = mysqli_query($conn, $query);
$galeri = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Galeri - e-RT Digital</title>
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
        
        .header {
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
        
        .header h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        
        .btn-back {
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
        
        .btn-back:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            padding: 25px;
            border: 1px solid var(--glass-border);
            margin-bottom: 40px;
        }
        
        .card h2 {
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36, 158, 148, 0.4);
        }
        
        .message {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .message.success { background: rgba(6, 214, 160, 0.2); border: 1px solid var(--success); color: white; }
        .message.error { background: rgba(239, 71, 111, 0.2); border: 1px solid var(--danger); color: white; }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .gallery-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            transition: 0.3s;
        }
        
        .gallery-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.2);
        }
        
        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .card-body {
            padding: 18px;
        }
        
        .card-body h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }
        
        .card-body .date {
            font-size: 12px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .card-body p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .card-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-edit {
            background: var(--warning);
            color: #1a1a2e;
        }
        
        .btn-delete {
            background: var(--danger);
        }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            border: 1px solid var(--glass-border);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-size: 22px;
            font-weight: 700;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .close:hover {
            color: var(--danger);
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
            .gallery-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="galeri.php" class="active"><i class="fas fa-images"></i> Galeri</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-images"></i> Admin Galeri</h1>
                <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            </div>

            <?php if (!empty($error_messages)): ?>
                <div class="message error">
                    <?php foreach ($error_messages as $err): ?>
                        <div><i class="fas fa-exclamation-triangle"></i> <?php echo $err; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Form Upload -->
            <div class="card">
                <h2><i class="fas fa-upload"></i> Tambah Foto Baru</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" required placeholder="Masukkan judul foto">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3" required placeholder="Deskripsi kegiatan atau keterangan foto"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" accept="image/*" required>
                        <small style="opacity:0.7;">Format: JPG, PNG, GIF, WEBP (max 10MB)</small>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload Foto</button>
                </form>
            </div>

            <!-- Daftar Foto -->
            <div class="gallery-grid">
                <?php foreach ($galeri as $item): ?>
                <div class="gallery-card" data-id="<?php echo $item['id']; ?>">
                    <img src="../<?php echo htmlspecialchars($item['foto']); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                    <div class="card-body">
                        <h3><?php echo htmlspecialchars($item['judul']); ?></h3>
                        <div class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['tanggal'])); ?></div>
                        <p><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 100)); if (strlen($item['deskripsi']) > 100) echo '...'; ?></p>
                        <div class="card-actions">
                            <button class="btn-edit btn btn-small" onclick="editItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['judul']); ?>', '<?php echo addslashes($item['deskripsi']); ?>', '<?php echo $item['tanggal']; ?>')"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus foto ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-delete btn btn-small"><i class="fas fa-trash"></i> Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($galeri)): ?>
                    <p style="text-align:center; grid-column:1/-1;">Belum ada foto. Upload foto pertama Anda.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
            <p><a href="profil.php?edit=1" class="edit-link">Edit Profil</a></p>
            <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
        </footer>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Foto</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" id="edit_judul" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="edit_tanggal" required>
                </div>
                <button type="submit" class="btn">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        function editItem(id, judul, deskripsi, tanggal) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_judul').value = judul;
            document.getElementById('edit_deskripsi').value = deskripsi;
            document.getElementById('edit_tanggal').value = tanggal;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>