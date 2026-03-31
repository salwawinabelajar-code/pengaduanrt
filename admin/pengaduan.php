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

// ========== PROSES UPDATE STATUS ==========
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE pengaduan SET status='$status' WHERE id=$id");
    header("Location: pengaduan.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// ========== PROSES HAPUS ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Hapus foto jika ada
    $q = mysqli_query($conn, "SELECT foto FROM pengaduan WHERE id=$id");
    $data = mysqli_fetch_assoc($q);
    if ($data && !empty($data['foto'])) {
        $file = '../' . $data['foto'];
        if (file_exists($file)) unlink($file);
    }
    mysqli_query($conn, "DELETE FROM pengaduan WHERE id=$id");
    header("Location: pengaduan.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// ========== FILTER DAN PAGINATION ==========
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "1=1";
if (!empty($filter_status)) {
    $where .= " AND p.status = '$filter_status'";
}
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.judul LIKE '%$search%' OR p.deskripsi LIKE '%$search%' OR p.lokasi LIKE '%$search%')";
}
if (!empty($start_date)) {
    $where .= " AND DATE(p.tanggal) >= '$start_date'";
}
if (!empty($end_date)) {
    $where .= " AND DATE(p.tanggal) <= '$end_date'";
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengaduan p WHERE $where");
$total_row = mysqli_fetch_assoc($total_result);
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT p.*, u.nama, u.username 
          FROM pengaduan p 
          JOIN users u ON p.user_id = u.id 
          WHERE $where 
          ORDER BY p.tanggal DESC 
          LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengaduan - Admin e-RT Digital</title>
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
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,0.2);
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
            margin-bottom: 30px;
            padding: 15px 25px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.2);
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
        }
        .filter-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1 1 150px;
            min-width: 120px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
            color: rgba(255,255,255,0.9);
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(5px);
            font-size: 14px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
        }
        .filter-group button,
        .filter-group a {
            width: 100%;
            padding: 10px 15px;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        .btn-filter {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(36,158,148,0.4);
        }
        .btn-reset {
            background: rgba(239,71,111,0.2);
            color: white;
            border: 1px solid rgba(239,71,111,0.3);
        }
        .btn-reset:hover {
            background: var(--danger);
        }
        .btn-export {
            background: rgba(6,214,160,0.2);
            color: white;
            border: 1px solid rgba(6,214,160,0.3);
        }
        .btn-export:hover {
            background: var(--success);
        }
        .table-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            overflow-x: auto;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
            min-width: 1000px;
        }
        th {
            background: rgba(0,0,0,0.3);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 14px;
        }
        tr:hover td {
            background: rgba(255,255,255,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .status-baru { background: rgba(255,209,102,0.2); color: #FFD166; }
        .status-diproses { background: rgba(17,138,178,0.2); color: #118AB2; }
        .status-selesai { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .status-ditolak { background: rgba(239,71,111,0.2); color: #EF476F; }
        .action-form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .status-select {
            padding: 6px 10px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 12px;
            cursor: pointer;
        }
        .status-select option {
            background: var(--primary);
            color: white;
        }
        .btn-delete {
            background: rgba(239,71,111,0.2);
            color: white;
            border: 1px solid rgba(239,71,111,0.3);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-delete:hover {
            background: var(--danger);
        }
        .btn-detail {
            background: rgba(59,193,168,0.2);
            color: white;
            border: 1px solid rgba(59,193,168,0.3);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .btn-detail:hover {
            background: var(--accent);
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .page-link {
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .page-link:hover,
        .page-link.active {
            background: var(--secondary);
            border-color: var(--secondary);
        }
        
        /* Modal Detail */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .modal-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: white;
        }
        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: white;
            transition: 0.2s;
        }
        .close-modal:hover {
            color: var(--danger);
        }
        .modal-body {
            padding: 25px;
        }
        .detail-group {
            margin-bottom: 20px;
        }
        .detail-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .detail-group .value {
            font-size: 15px;
            color: white;
            line-height: 1.6;
            background: rgba(0,0,0,0.3);
            padding: 10px 15px;
            border-radius: 15px;
            word-break: break-word;
        }
        .detail-group .value i {
            color: var(--accent);
            margin-right: 8px;
        }
        .foto-bukti {
            margin-top: 10px;
        }
        .foto-bukti img {
            max-width: 100%;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.2);
            cursor: pointer;
        }
        .no-foto {
            background: rgba(0,0,0,0.3);
            padding: 20px;
            text-align: center;
            border-radius: 15px;
            color: rgba(255,255,255,0.6);
        }
        .riwayat-section {
            margin-top: 40px;
        }
        .riwayat-section h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
        }
        .footer {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            margin-top: 40px;
            text-align: center;
            color: white;
            border-top: 1px solid rgba(255,255,255,0.2);
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
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .filter-form { flex-direction: column; }
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
            <a href="pengaduan.php" class="active"><i class="fas fa-comment-medical"></i> Pengaduan</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="page-header-left">
                    <i class="fas fa-comment-medical"></i>
                    <h1>Kelola Pengaduan</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua</option>
                            <option value="baru" <?php if ($filter_status == 'baru') echo 'selected'; ?>>Baru</option>
                            <option value="diproses" <?php if ($filter_status == 'diproses') echo 'selected'; ?>>Diproses</option>
                            <option value="selesai" <?php if ($filter_status == 'selesai') echo 'selected'; ?>>Selesai</option>
                            <option value="ditolak" <?php if ($filter_status == 'ditolak') echo 'selected'; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Cari</label>
                        <input type="text" name="search" placeholder="Judul/Deskripsi/Lokasi" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
                    </div>
                    <div class="filter-group">
                        <a href="pengaduan.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                    <div class="filter-group">
                        <a href="export_pengaduan.php?<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn-export"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
                    </div>
                </form>
            </div>

            <!-- Tabel Pengaduan -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID</th>
                            <th>Warga</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Urgensi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($result)):
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                                <td><?php echo htmlspecialchars($row['lokasi']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($row['urgensi']); ?></td>
                                <td>
                                    <div class="action-form">
                                        <!-- Tombol Detail -->
                                        <button class="btn-detail" onclick="showDetail(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <!-- Form update status -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="baru" <?php if ($row['status'] == 'baru') echo 'selected'; ?>>Baru</option>
                                                <option value="diproses" <?php if ($row['status'] == 'diproses') echo 'selected'; ?>>Diproses</option>
                                                <option value="selesai" <?php if ($row['status'] == 'selesai') echo 'selected'; ?>>Selesai</option>
                                                <option value="ditolak" <?php if ($row['status'] == 'ditolak') echo 'selected'; ?>>Ditolak</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        <!-- Hapus -->
                                        <a href="?hapus=<?php echo $row['id']; ?>&<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i> Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <!-- Riwayat Pengaduan -->
            <?php
            $riwayat_query = "SELECT p.*, u.nama 
                              FROM pengaduan p 
                              JOIN users u ON p.user_id = u.id 
                              WHERE p.status IN ('selesai', 'diproses', 'ditolak') 
                              ORDER BY p.tanggal DESC 
                              LIMIT 10";
            $riwayat_result = mysqli_query($conn, $riwayat_query);
            if (mysqli_num_rows($riwayat_result) > 0):
            ?>
                <div class="riwayat-section">
                    <h2><i class="fas fa-history"></i> Riwayat Pengaduan (Terbaru)</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID</th>
                                    <th>Warga</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_riw = 1; while ($r = mysqli_fetch_assoc($riwayat_result)): ?>
                                    <tr>
                                        <td><?php echo $no_riw++; ?></td>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($r['judul']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $r['status']; ?>">
                                                <?php echo ucfirst($r['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($r['tanggal'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
            <p><a href="profil.php?edit=1" class="edit-link">Edit Profil</a></p>
            <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
        </footer>
    </div>

    <!-- Modal Detail -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detail Pengaduan</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Isi akan diisi dengan JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function showDetail(data) {
            const modalBody = document.getElementById('modalBody');
            const fotoPath = data.foto ? '../' + data.foto : null;
            const fotoHtml = fotoPath ? 
                `<div class="foto-bukti">
                    <img src="${fotoPath}" alt="Foto Bukti" onclick="window.open(this.src)">
                </div>` : 
                `<div class="no-foto">
                    <i class="fas fa-camera-slash"></i> Tidak ada foto bukti
                </div>`;
            
            modalBody.innerHTML = `
                <div class="detail-group">
                    <label><i class="fas fa-ticket-alt"></i> ID Tiket</label>
                    <div class="value">${data.no_tiket || data.id}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-user"></i> Pelapor</label>
                    <div class="value">${escapeHtml(data.nama)}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-heading"></i> Judul</label>
                    <div class="value">${escapeHtml(data.judul)}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-tag"></i> Kategori</label>
                    <div class="value">${escapeHtml(data.kategori) || '-'}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-map-marker-alt"></i> Lokasi</label>
                    <div class="value">${escapeHtml(data.lokasi) || '-'}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-align-left"></i> Deskripsi</label>
                    <div class="value">${escapeHtml(data.deskripsi)}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-image"></i> Foto Bukti</label>
                    ${fotoHtml}
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-chart-line"></i> Tingkat Urgensi</label>
                    <div class="value">${capitalize(data.urgensi)}</div>
                </div>
                <div class="detail-group">
                    <label><i class="fas fa-flag-checkered"></i> Status</label>
                    <div class="value">
                        <span class="status-badge status-${data.status}">${capitalize(data.status)}</span>
                    </div>
                </div>
                <div class="detail-group">
                    <label><i class="far fa-calendar-alt"></i> Tanggal Laporan</label>
                    <div class="value">${formatDate(data.tanggal)}</div>
                </div>
                <div class="detail-group">
                    <label><i class="far fa-clock"></i> Dibuat Pada</label>
                    <div class="value">${formatDateTime(data.created_at)}</div>
                </div>
            `;
            
            document.getElementById('detailModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        function escapeHtml(str) {
            if (!str) return '-';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            }).replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(c) {
                return c;
            });
        }
        
        function capitalize(str) {
            if (!str) return '-';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }
        
        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '-';
            const date = new Date(dateTimeStr);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
        
        // Tutup modal dengan klik di luar
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
<?php
// Clean up
if (isset($result) && is_object($result)) mysqli_free_result($result);
if (isset($riwayat_result) && is_object($riwayat_result)) mysqli_free_result($riwayat_result);
mysqli_close($conn);
?>