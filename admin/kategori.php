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

// Proses tambah kategori
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    if (!empty($nama)) {
        $query = "INSERT INTO kategori_pengaduan (nama, deskripsi) VALUES ('$nama', '$deskripsi')";
        mysqli_query($conn, $query);
    }
    header("Location: kategori.php");
    exit();
}

// Proses edit kategori
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    if (!empty($nama)) {
        $query = "UPDATE kategori_pengaduan SET nama='$nama', deskripsi='$deskripsi' WHERE id=$id";
        mysqli_query($conn, $query);
    }
    header("Location: kategori.php");
    exit();
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah kategori digunakan di pengaduan
    $check = mysqli_query($conn, "SELECT id FROM pengaduan WHERE kategori='$id'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "DELETE FROM kategori_pengaduan WHERE id=$id");
    }
    header("Location: kategori.php");
    exit();
}

// Ambil semua kategori
$query = "SELECT * FROM kategori_pengaduan ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
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
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
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
            top: 0; z-index: 100;
        }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            background: var(--accent); width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 22px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .logo-text h1 { font-size: 22px; color: white; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-menu { display: flex; gap: 15px; }
        .nav-menu a {
            color: white; text-decoration: none; font-weight: 500; padding: 8px 16px;
            border-radius: 30px; transition: 0.3s; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);
        }
        .nav-menu a:hover, .nav-menu a.active {
            background: var(--secondary); transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-profile a { text-decoration: none; color: white; display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 20px; border: 2px solid white;
        }
        .user-info { color: white; }
        .user-info h4 { font-size: 16px; }
        .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid rgba(255,255,255,0.2);
            color: white; padding: 8px 16px; border-radius: 30px; text-decoration: none;
            font-size: 14px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 5px;
        }
        .logout-btn:hover { background: var(--danger); }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .page-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            padding: 15px 25px; background: rgba(255,255,255,0.15); backdrop-filter: blur(12px);
            border-radius: 50px; border: 1px solid rgba(255,255,255,0.2);
        }
        .page-header-left { display: flex; align-items: center; gap: 15px; }
        .page-header-left i { font-size: 28px; color: var(--accent); }
        .page-header-left h1 { font-size: 28px; font-weight: 700; color: white; }
        .back-btn {
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
            color: white; padding: 10px 20px; border-radius: 40px; text-decoration: none;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .back-btn:hover { background: var(--secondary); border-color: var(--secondary); }
        .card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: white;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(5px);
            font-size: 16px;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
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
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        .btn-danger {
            background: rgba(239,71,111,0.2);
            border: 1px solid rgba(239,71,111,0.3);
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
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        th {
            background: rgba(0,0,0,0.2);
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .edit-btn, .delete-btn {
            padding: 5px 15px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .edit-btn:hover { background: var(--secondary); }
        .delete-btn:hover { background: var(--danger); }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital Admin</h1></div>
        </div>
        <div class="nav-menu">
            <a href="index.php">Dashboard</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="surat.php">Surat</a>
            <a href="iuran.php">Iuran</a>
            <a href="akun.php">Akun Warga</a>
            <a href="pengumuman.php">Pengumuman</a>
            <a href="kk.php">Data KK</a>
            <a href="kategori.php" class="active">Kategori</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <small>Admin</small>
                </div>
            </a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-tags"></i>
                <h1>Kelola Kategori Pengaduan</h1>
            </div>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Form Tambah Kategori -->
        <div class="card">
            <h3 style="margin-bottom:20px; color:white;">Tambah Kategori Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama" required placeholder="Contoh: Kebersihan">
                </div>
                <div class="form-group">
                    <label>Deskripsi (opsional)</label>
                    <textarea name="deskripsi" rows="3" placeholder="Penjelasan kategori"></textarea>
                </div>
                <button type="submit" name="tambah" class="btn"><i class="fas fa-plus"></i> Tambah</button>
            </form>
        </div>

        <!-- Daftar Kategori -->
        <div class="card">
            <h3 style="margin-bottom:20px; color:white;">Daftar Kategori</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-btn" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama']); ?>', '<?php echo htmlspecialchars($row['deskripsi']); ?>')"><i class="fas fa-edit"></i> Edit</button>
                                <a href="?hapus=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Yakin ingin menghapus? Pastikan tidak ada pengaduan dengan kategori ini.')"><i class="fas fa-trash"></i> Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="color:white; margin-bottom:20px;">Edit Kategori</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="edit" class="btn"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nama, deskripsi) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_deskripsi').value = deskripsi;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>