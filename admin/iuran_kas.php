<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Ambil saldo
$saldo_result = $conn->query("SELECT saldo FROM iuran_saldo WHERE id = 1");
$saldo = $saldo_result ? $saldo_result->fetch_assoc()['saldo'] : 0;

// Proses tambah transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $tanggal = $_POST['tanggal'];
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    $pemasukan = (int)$_POST['pemasukan'];
    $pengeluaran = (int)$_POST['pengeluaran'];
    
    $insert = $conn->prepare("INSERT INTO iuran_kas (tanggal, keterangan, pemasukan, pengeluaran) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssii", $tanggal, $keterangan, $pemasukan, $pengeluaran);
    $insert->execute();
    
    // Update saldo
    if ($pemasukan > 0) {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo + $pemasukan WHERE id = 1");
    } else {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo - $pengeluaran WHERE id = 1");
    }
    header("Location: iuran_kas.php");
    exit();
}

// Ambil semua transaksi
$transaksi = $conn->query("SELECT * FROM iuran_kas ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kas - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS minimal, bisa disalin dari halaman iuran */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        :root { --primary:#005461; --secondary:#249E94; --accent:#3BC1A8; --danger:#EF476F; }
        body { background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed; background-size: cover; min-height:100vh; position:relative; color:#fff; display:flex; }
        body::before { content:''; position:fixed; top:0; left:0; width:100%; height:100%; background:linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%); backdrop-filter:blur(3px); z-index:-1; }
        .sidebar { width:280px; background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.2); padding:30px 20px; display:flex; flex-direction:column; position:fixed; height:100vh; }
        .sidebar .logo { display:flex; align-items:center; gap:10px; margin-bottom:40px; }
        .sidebar .logo-icon { background:var(--accent); width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; }
        .sidebar .nav-menu a { display:flex; align-items:center; gap:15px; padding:12px 15px; color:rgba(255,255,255,0.9); text-decoration:none; border-radius:15px; transition:0.3s; }
        .sidebar .nav-menu a:hover, .sidebar .nav-menu a.active { background:var(--secondary); }
        .main-content { flex:1; margin-left:280px; padding:30px; }
        .content-header { background:rgba(255,255,255,0.15); backdrop-filter:blur(12px); border-radius:30px; padding:20px 30px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; }
        .btn-primary { background:linear-gradient(135deg, var(--secondary), var(--accent)); color:white; border:none; padding:12px 25px; border-radius:40px; font-weight:600; cursor:pointer; text-decoration:none; }
        .card { background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border-radius:30px; padding:20px; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; color:white; }
        th, td { padding:15px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        .pemasukan { color:#06D6A0; }
        .pengeluaran { color:#EF476F; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); justify-content:center; align-items:center; }
        .modal-content { background:rgba(255,255,255,0.15); backdrop-filter:blur(20px); border-radius:30px; padding:30px; max-width:500px; width:90%; }
        .form-control { width:100%; padding:12px; border:2px solid rgba(255,255,255,0.2); border-radius:30px; background:rgba(255,255,255,0.1); color:white; margin-bottom:15px; }
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
        </div>
        <a href="../auth/logout.php" class="logout-btn" style="background:rgba(239,71,111,0.2); border:1px solid rgba(255,255,255,0.2); color:white; padding:8px 12px; border-radius:30px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:auto;"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-cash-register"></i> Manajemen Kas</h1>
            <a href="iuran.php" class="btn-primary"><i class="fas fa-arrow-left"></i> Kembali ke Iuran</a>
        </div>

        <div class="card" style="text-align:center;">
            <h2>Saldo Kas</h2>
            <p style="font-size:48px; font-weight:700;">Rp <?php echo number_format($saldo,0,',','.'); ?></p>
        </div>

        <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Transaksi</button>

        <h2 style="margin:30px 0 20px;">Riwayat Transaksi</h2>
        <div class="card">
            <table>
                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Pemasukan</th><th>Pengeluaran</th></tr></thead>
                <tbody>
                    <?php while ($row = $transaksi->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['tanggal']; ?></td>
                        <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                        <td class="pemasukan"><?php echo $row['pemasukan'] ? 'Rp '.number_format($row['pemasukan'],0,',','.') : '-'; ?></td>
                        <td class="pengeluaran"><?php echo $row['pengeluaran'] ? 'Rp '.number_format($row['pengeluaran'],0,',','.') : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Transaksi -->
    <div id="transaksiModal" class="modal">
        <div class="modal-content">
            <h3>Tambah Transaksi Kas</h3>
            <form method="POST">
                <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="text" name="keterangan" class="form-control" placeholder="Keterangan" required>
                <input type="number" name="pemasukan" class="form-control" placeholder="Pemasukan (isi 0 jika pengeluaran)" value="0">
                <input type="number" name="pengeluaran" class="form-control" placeholder="Pengeluaran (isi 0 jika pemasukan)" value="0">
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" name="add_transaction" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('transaksiModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('transaksiModal').style.display = 'none'; }
    </script>
</body>
</html>