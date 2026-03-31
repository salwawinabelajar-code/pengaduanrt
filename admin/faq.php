<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Proses tambah/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $isi = mysqli_real_escape_string($conn, $_POST['isi']);
        $ikon = mysqli_real_escape_string($conn, $_POST['ikon']);
        $urutan = (int)$_POST['urutan'];

        if ($id > 0) {
            $query = "UPDATE faq SET judul='$judul', isi='$isi', ikon='$ikon', urutan='$urutan' WHERE id=$id";
        } else {
            $query = "INSERT INTO faq (judul, isi, ikon, urutan) VALUES ('$judul', '$isi', '$ikon', '$urutan')";
        }
        mysqli_query($conn, $query);
        header("Location: faq.php");
        exit();
    } elseif (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM faq WHERE id=$id");
        header("Location: faq.php");
        exit();
    }
}

// Ambil semua FAQ
$result = mysqli_query($conn, "SELECT * FROM faq ORDER BY urutan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola FAQ - Admin e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS sama seperti halaman admin lainnya (sidebar, dll) */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        :root {
            --primary: #005461;
            --secondary: #249E94;
            --accent: #3BC1A8;
            --danger: #EF476F;
        }
        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            color: #fff;
            display: flex;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }
        .sidebar { width:280px; background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.2); padding:30px 20px; display:flex; flex-direction:column; position:fixed; height:100vh; }
        .sidebar .logo { display:flex; align-items:center; gap:10px; margin-bottom:40px; }
        .sidebar .logo-icon { background:var(--accent); width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; }
        .sidebar .nav-menu { flex:1; display:flex; flex-direction:column; gap:5px; }
        .sidebar .nav-menu a { display:flex; align-items:center; gap:15px; padding:12px 15px; color:rgba(255,255,255,0.9); text-decoration:none; border-radius:15px; transition:0.3s; }
        .sidebar .nav-menu a:hover, .sidebar .nav-menu a.active { background:var(--secondary); }
        .sidebar .user-profile { margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.2); }
        .sidebar .logout-btn { background:rgba(239,71,111,0.2); border:1px solid rgba(255,255,255,0.2); color:white; padding:8px 12px; border-radius:30px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:10px; }
        .main-content { flex:1; margin-left:280px; padding:30px; }
        .content-header { background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px; padding:20px 30px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; }
        .btn-primary { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:12px 25px; border-radius:40px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .table-container { background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-top:30px; }
        table { width:100%; border-collapse:collapse; color:white; }
        th, td { padding:15px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        .btn-action { padding:6px 12px; border-radius:20px; text-decoration:none; color:white; background:rgba(255,255,255,0.1); margin-right:5px; }
        .btn-edit { background:rgba(255,209,102,0.2); }
        .btn-delete { background:rgba(239,71,111,0.2); }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:rgba(255,255,255,0.15); backdrop-filter:blur(20px); border-radius:30px; padding:30px; max-width:600px; width:90%; }
        .form-group { margin-bottom:15px; }
        label { display:block; margin-bottom:5px; }
        input, textarea { width:100%; padding:10px; border:2px solid rgba(255,255,255,0.2); border-radius:30px; background:rgba(255,255,255,0.1); color:white; }
        textarea { min-height:200px; }
        @media (max-width:768px) { .sidebar { transform:translateX(-100%); } .main-content { margin-left:0; } }
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
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
            <a href="faq.php" class="active"><i class="fas fa-question-circle"></i> FAQ</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'],0,1)); ?></div>
                <div class="info"><h4><?php echo htmlspecialchars($user['nama']); ?></h4><p>admin</p></div>
            </a>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-question-circle"></i> Kelola FAQ</h1>
            <button class="btn-primary" onclick="openModal(0)"><i class="fas fa-plus"></i> Tambah FAQ</button>
        </div>

        <div class="table-container">
            <table>
                <thead><tr><th>Urutan</th><th>Judul</th><th>Ikon</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['urutan']; ?></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><i class="<?php echo htmlspecialchars($row['ikon']); ?>"></i> <?php echo htmlspecialchars($row['ikon']); ?></td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openModal(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="hapus" class="btn-action btn-delete" onclick="return confirm('Hapus FAQ ini?')"><i class="fas fa-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit/Tambah -->
    <div id="faqModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Tambah FAQ</h2>
            <form method="POST">
                <input type="hidden" name="id" id="faq_id" value="0">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" id="judul" required>
                </div>
                <div class="form-group">
                    <label>Ikon (contoh: fas fa-question-circle)</label>
                    <input type="text" name="ikon" id="ikon" placeholder="fas fa-question-circle" required>
                </div>
                <div class="form-group">
                    <label>Urutan</label>
                    <input type="number" name="urutan" id="urutan" value="0" required>
                </div>
                <div class="form-group">
                    <label>Isi (HTML)</label>
                    <textarea name="isi" id="isi" required></textarea>
                    <small style="color:rgba(255,255,255,0.7);">Anda dapat menggunakan HTML untuk memformat teks.</small>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-primary" style="background:rgba(239,71,111,0.2);" onclick="closeModal()">Batal</button>
                    <button type="submit" name="simpan" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById('faq_id').value = id;
            if (id == 0) {
                document.getElementById('modalTitle').innerText = 'Tambah FAQ';
                document.getElementById('judul').value = '';
                document.getElementById('ikon').value = 'fas fa-question-circle';
                document.getElementById('urutan').value = '0';
                document.getElementById('isi').value = '';
            } else {
                // Fetch data via AJAX (simplified: we can pass PHP data, but for simplicity we'll redirect to edit page)
                // Alternatively, we can use PHP to fill modal on page load. For now, we'll use separate edit page.
                // But we already have a modal; we need to populate with current data. Since we have the data in the same page, we could use PHP to generate JS variables.
                // Let's simplify: use separate edit page. However, to keep modal, we'd need AJAX. I'll make it a separate edit page for simplicity.
                window.location.href = 'faq_edit.php?id=' + id;
            }
            document.getElementById('faqModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('faqModal').style.display = 'none';
        }
    </script>
</body>
</html>