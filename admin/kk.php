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

// ========== CEK DAN BUAT TABEL JIKA BELUM ADA ==========
// Tabel kartu_keluarga
$check_kk = mysqli_query($conn, "SHOW TABLES LIKE 'kartu_keluarga'");
if (mysqli_num_rows($check_kk) == 0) {
    $sql_kk = "CREATE TABLE kartu_keluarga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        no_kk VARCHAR(20) NOT NULL UNIQUE,
        alamat TEXT NOT NULL,
        rt_rw VARCHAR(15) NOT NULL,
        desa_kelurahan VARCHAR(100) NOT NULL,
        kecamatan VARCHAR(100) NOT NULL,
        kabupaten VARCHAR(100) NOT NULL,
        provinsi VARCHAR(100) NOT NULL,
        kode_pos VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql_kk);
} else {
    // Cek apakah kolom user_id ada, jika tidak tambahkan
    $check_user_id = mysqli_query($conn, "SHOW COLUMNS FROM kartu_keluarga LIKE 'user_id'");
    if (mysqli_num_rows($check_user_id) == 0) {
        mysqli_query($conn, "ALTER TABLE kartu_keluarga ADD user_id INT NOT NULL AFTER id");
        mysqli_query($conn, "ALTER TABLE kartu_keluarga ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    }
}

// Tabel anggota_keluarga
$check_anggota = mysqli_query($conn, "SHOW TABLES LIKE 'anggota_keluarga'");
if (mysqli_num_rows($check_anggota) == 0) {
    $sql_anggota = "CREATE TABLE anggota_keluarga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kk_id INT NOT NULL,
        nik VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        tempat_lahir VARCHAR(50) NOT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('L','P') NOT NULL,
        agama VARCHAR(20) NOT NULL,
        pendidikan VARCHAR(50) NOT NULL,
        pekerjaan VARCHAR(50) NOT NULL,
        status_perkawinan VARCHAR(20) NOT NULL,
        status_keluarga VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kk_id) REFERENCES kartu_keluarga(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql_anggota);
} else {
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM anggota_keluarga LIKE 'kk_id'");
    if (mysqli_num_rows($check_column) == 0) {
        mysqli_query($conn, "ALTER TABLE anggota_keluarga ADD kk_id INT NOT NULL AFTER id");
        mysqli_query($conn, "ALTER TABLE anggota_keluarga ADD FOREIGN KEY (kk_id) REFERENCES kartu_keluarga(id) ON DELETE CASCADE");
    }
}

// Ambil daftar warga untuk dropdown
$query_warga = "SELECT id, nama, username FROM users WHERE role = 'warga' ORDER BY nama";
$result_warga = mysqli_query($conn, $query_warga);
$warga_list = [];
while ($row = mysqli_fetch_assoc($result_warga)) {
    $warga_list[] = $row;
}

// Inisialisasi pesan
$message = '';
$error = '';

// ========== PROSES TAMBAH / EDIT KK ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_kk') {
        $user_id_kk = (int)$_POST['user_id'];
        $no_kk = mysqli_real_escape_string($conn, $_POST['no_kk']);
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
        $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
        $desa_kelurahan = mysqli_real_escape_string($conn, $_POST['desa_kelurahan']);
        $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
        $kabupaten = mysqli_real_escape_string($conn, $_POST['kabupaten']);
        $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
        $kode_pos = mysqli_real_escape_string($conn, $_POST['kode_pos']);
        $kk_id = isset($_POST['kk_id']) ? (int)$_POST['kk_id'] : 0;

        if ($user_id_kk == 0 || empty($no_kk) || empty($alamat) || empty($rt_rw) || empty($desa_kelurahan) || empty($kecamatan) || empty($kabupaten) || empty($provinsi) || empty($kode_pos)) {
            $error = "Semua field KK harus diisi, termasuk pilih kepala keluarga.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                if ($kk_id > 0) {
                    $query_kk = "UPDATE kartu_keluarga SET user_id='$user_id_kk', no_kk='$no_kk', alamat='$alamat', rt_rw='$rt_rw', desa_kelurahan='$desa_kelurahan', kecamatan='$kecamatan', kabupaten='$kabupaten', provinsi='$provinsi', kode_pos='$kode_pos' WHERE id=$kk_id";
                    mysqli_query($conn, $query_kk);
                } else {
                    $query_kk = "INSERT INTO kartu_keluarga (user_id, no_kk, alamat, rt_rw, desa_kelurahan, kecamatan, kabupaten, provinsi, kode_pos) VALUES ('$user_id_kk', '$no_kk', '$alamat', '$rt_rw', '$desa_kelurahan', '$kecamatan', '$kabupaten', '$provinsi', '$kode_pos')";
                    mysqli_query($conn, $query_kk);
                    $kk_id = mysqli_insert_id($conn);
                }

                if (isset($_POST['nik']) && is_array($_POST['nik'])) {
                    if ($kk_id > 0 && isset($_POST['kk_id']) && $_POST['kk_id'] > 0) {
                        $delete = "DELETE FROM anggota_keluarga WHERE kk_id = $kk_id";
                        mysqli_query($conn, $delete);
                    }

                    for ($i = 0; $i < count($_POST['nik']); $i++) {
                        $nik = mysqli_real_escape_string($conn, $_POST['nik'][$i]);
                        $nama = mysqli_real_escape_string($conn, $_POST['nama'][$i]);
                        $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir'][$i]);
                        $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir'][$i]);
                        $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'][$i]);
                        $agama = mysqli_real_escape_string($conn, $_POST['agama'][$i]);
                        $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan'][$i]);
                        $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan'][$i]);
                        $status_perkawinan = mysqli_real_escape_string($conn, $_POST['status_perkawinan'][$i]);
                        $status_keluarga = mysqli_real_escape_string($conn, $_POST['status_keluarga'][$i]);

                        if (!empty($nik) && !empty($nama)) {
                            $insert = "INSERT INTO anggota_keluarga (kk_id, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_perkawinan, status_keluarga) 
                                       VALUES ('$kk_id', '$nik', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama', '$pendidikan', '$pekerjaan', '$status_perkawinan', '$status_keluarga')";
                            mysqli_query($conn, $insert);
                        }
                    }
                }

                mysqli_commit($conn);
                $message = $kk_id > 0 ? "Data KK berhasil diperbarui." : "Data KK berhasil ditambahkan.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Gagal menyimpan data: " . mysqli_error($conn);
            }
        }
    }
}

// ========== PROSES HAPUS KK ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $query = "DELETE FROM kartu_keluarga WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "Data KK berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data KK: " . mysqli_error($conn);
    }
}

// ========== AMBIL DATA UNTUK EDIT ==========
$edit_data = null;
$edit_anggota = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $query_kk = "SELECT * FROM kartu_keluarga WHERE id = $id";
    $result_kk = mysqli_query($conn, $query_kk);
    if ($result_kk && mysqli_num_rows($result_kk) > 0) {
        $edit_data = mysqli_fetch_assoc($result_kk);
        $query_anggota = "SELECT * FROM anggota_keluarga WHERE kk_id = $id ORDER BY id";
        $result_anggota = mysqli_query($conn, $query_anggota);
        if ($result_anggota) {
            while ($row = mysqli_fetch_assoc($result_anggota)) {
                $edit_anggota[] = $row;
            }
        }
    }
}

// ========== AMBIL SEMUA KK UNTUK DITAMPILKAN ==========
$query_all = "SELECT k.*, u.nama as nama_kepala,
              (SELECT COUNT(*) FROM anggota_keluarga WHERE kk_id = k.id) as jumlah_anggota 
              FROM kartu_keluarga k 
              LEFT JOIN users u ON k.user_id = u.id
              ORDER BY k.created_at DESC";
$result_all = mysqli_query($conn, $query_all);
if (!$result_all) {
    $error = "Error mengambil data: " . mysqli_error($conn);
    $result_all = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kartu Keluarga - Admin e-RT Digital</title>
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
        
        .sidebar .user-profile .info h4 { font-size: 14px; color: white; }
        .sidebar .user-profile .info p { font-size: 12px; color: rgba(255,255,255,0.7); }
        
        .sidebar .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid rgba(255,255,255,0.2);
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white; border: none; padding: 12px 25px; border-radius: 40px;
            font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex;
            align-items: center; gap: 8px; text-decoration: none;
        }
        
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(36,158,148,0.4); }
        
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
            border-radius: 30px; padding: 25px; border: 1px solid var(--glass-border);
            margin-top: 30px;
        }
        
        .table-container h2 {
            margin-bottom: 20px; color: white; font-size: 24px;
            display: flex; align-items: center; gap: 10px;
        }
        
        table { width: 100%; border-collapse: collapse; color: white; }
        
        th { text-align: left; padding: 15px 10px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.2); }
        td { padding: 15px 10px; border-bottom: 1px solid rgba(255,255,255,0.1); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        .badge {
            background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px;
            font-size: 12px; display: inline-block;
        }
        
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .btn-action {
            padding: 6px 12px; border-radius: 20px; font-size: 12px; text-decoration: none;
            transition: 0.3s; display: inline-flex; align-items: center; gap: 5px;
            border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1);
            color: white; cursor: pointer;
        }
        
        .btn-action:hover { transform: translateY(-2px); }
        .btn-edit { background: rgba(255,209,102,0.2); color: #FFD166; }
        .btn-edit:hover { background: rgba(255,209,102,0.4); }
        .btn-delete { background: rgba(239,71,111,0.2); color: #EF476F; }
        .btn-delete:hover { background: rgba(239,71,111,0.4); }
        .btn-view { background: rgba(17,138,178,0.2); color: #118AB2; }
        .btn-view:hover { background: rgba(17,138,178,0.4); }
        
        /* Modal */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 1000;
            justify-content: center; align-items: center; overflow-y: auto; padding: 20px;
        }
        
        .modal-content {
            background: var(--glass-bg); backdrop-filter: blur(20px);
            border-radius: 32px; width: 100%; max-width: 1000px; max-height: 90vh;
            overflow-y: auto; border: 1px solid var(--glass-border);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3); padding: 30px;
        }
        
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        
        .modal-header h2 { color: white; font-size: 24px; }
        
        .close-modal {
            background: none; border: none; color: white; font-size: 28px;
            cursor: pointer; width: 45px; height: 45px; display: flex;
            align-items: center; justify-content: center; border-radius: 50%;
            transition: 0.3s;
        }
        
        .close-modal:hover { background: rgba(255,255,255,0.2); }
        
        .form-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;
        }
        
        .form-group { margin-bottom: 15px; }
        
        label { display: block; margin-bottom: 5px; font-weight: 600; color: white; }
        
        .form-control, select.form-control {
            width: 100%; padding: 12px 15px; border: 2px solid var(--glass-border);
            border-radius: 30px; background: rgba(255,255,255,0.1); color: white;
            font-size: 14px; font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus, select.form-control:focus {
            outline: none; border-color: var(--accent); background: rgba(255,255,255,0.2);
        }
        
        select.form-control option { background: var(--primary); color: white; }
        
        .section-title {
            color: var(--accent); margin: 20px 0 15px; font-size: 20px;
            border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .anggota-row {
            background: rgba(0,0,0,0.3); border-radius: 20px; padding: 15px;
            margin-bottom: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px; align-items: end; position: relative;
        }
        
        .remove-anggota {
            position: absolute; top: 10px; right: 10px;
            background: rgba(239,71,111,0.2); border: 1px solid rgba(239,71,111,0.3);
            color: #EF476F; width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.3s;
        }
        
        .remove-anggota:hover { background: rgba(239,71,111,0.5); }
        
        .btn-add-anggota {
            background: rgba(6,214,160,0.2); color: var(--success);
            border: 1px solid rgba(6,214,160,0.3); padding: 10px 20px;
            border-radius: 40px; font-weight: 600; cursor: pointer;
            transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
            margin-top: 10px;
        }
        
        .btn-add-anggota:hover { background: rgba(6,214,160,0.4); }
        
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
            .form-grid { grid-template-columns: 1fr; }
            .menu-toggle { display: block; position: fixed; top: 20px; left: 20px; z-index: 200;
                background: var(--secondary); border: none; color: white; width: 45px; height: 45px;
                border-radius: 50%; font-size: 20px; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php" class="active"><i class="fas fa-address-card"></i> Data KK</a>
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
            <h1><i class="fas fa-address-card"></i> Kelola Kartu Keluarga</h1>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Data KK</button>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Daftar Kartu Keluarga</h2>
            <?php if ($result_all && mysqli_num_rows($result_all) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No. KK</th>
                            <th>Kepala Keluarga</th>
                            <th>Alamat</th>
                            <th>RT/RW</th>
                            <th>Jumlah Anggota</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_all)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['no_kk']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kepala'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                            <td><?php echo htmlspecialchars($row['rt_rw']); ?></td>
                            <td><span class="badge"><?php echo $row['jumlah_anggota']; ?> orang</span></td>
                            <td class="actions">
                                <a href="#" onclick="editKK(<?php echo $row['id']; ?>)" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus KK ini? Semua anggota keluarga akan ikut terhapus.')"><i class="fas fa-trash"></i> Hapus</a>
                                <a href="#" onclick="viewKK(<?php echo $row['id']; ?>)" class="btn-action btn-view"><i class="fas fa-eye"></i> Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 30px;">Belum ada data Kartu Keluarga. Silakan tambahkan data baru.</p>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
            <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
        </footer>
    </div>

    <!-- Modal Tambah/Edit KK -->
    <div id="kkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Kartu Keluarga</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="" id="formKK">
                <input type="hidden" name="action" value="save_kk">
                <input type="hidden" name="kk_id" id="kk_id" value="">

                <!-- Data KK -->
                <h3 class="section-title"><i class="fas fa-home"></i> Data Kartu Keluarga</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>No. KK *</label>
                        <input type="text" name="no_kk" id="no_kk" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kepala Keluarga (User) *</label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <option value="">-- Pilih Warga --</option>
                            <?php foreach ($warga_list as $w): ?>
                                <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['nama']) . ' (' . htmlspecialchars($w['username']) . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>RT/RW *</label>
                        <input type="text" name="rt_rw" id="rt_rw" class="form-control" placeholder="001/003" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat *</label>
                        <input type="text" name="alamat" id="alamat" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Desa/Kelurahan *</label>
                        <input type="text" name="desa_kelurahan" id="desa_kelurahan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kecamatan *</label>
                        <input type="text" name="kecamatan" id="kecamatan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kabupaten *</label>
                        <input type="text" name="kabupaten" id="kabupaten" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Provinsi *</label>
                        <input type="text" name="provinsi" id="provinsi" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Pos *</label>
                        <input type="text" name="kode_pos" id="kode_pos" class="form-control" required>
                    </div>
                </div>

                <!-- Data Anggota Keluarga -->
                <h3 class="section-title"><i class="fas fa-users"></i> Anggota Keluarga</h3>
                <div id="anggota-container"></div>
                <button type="button" class="btn-add-anggota" onclick="tambahAnggota()"><i class="fas fa-plus"></i> Tambah Anggota</button>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()" style="background:rgba(239,71,111,0.2); border:1px solid rgba(239,71,111,0.3); color:white; padding:12px 25px; border-radius:40px; cursor:pointer;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail KK -->
    <div id="detailModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Detail Kartu Keluarga</h2>
                <button class="close-modal" onclick="closeDetailModal()">&times;</button>
            </div>
            <div id="detailContent" style="color: white;">
                <!-- Akan diisi dengan AJAX -->
            </div>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <script>
        let anggotaCount = 0;

        <?php if ($edit_data): ?>
        var editData = <?php echo json_encode($edit_data); ?>;
        var editAnggota = <?php echo json_encode($edit_anggota); ?>;
        <?php else: ?>
        var editData = null;
        var editAnggota = [];
        <?php endif; ?>

        function openModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Kartu Keluarga';
            document.getElementById('kk_id').value = '';
            document.getElementById('no_kk').value = '';
            document.getElementById('user_id').value = '';
            document.getElementById('rt_rw').value = '';
            document.getElementById('alamat').value = '';
            document.getElementById('desa_kelurahan').value = '';
            document.getElementById('kecamatan').value = '';
            document.getElementById('kabupaten').value = '';
            document.getElementById('provinsi').value = '';
            document.getElementById('kode_pos').value = '';
            document.getElementById('anggota-container').innerHTML = '';
            anggotaCount = 0;
            tambahAnggota();
            document.getElementById('kkModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('kkModal').style.display = 'none';
        }

        function editKK(id) {
            window.location.href = '?edit=' + id;
        }

        <?php if ($edit_data): ?>
        window.onload = function() {
            if (editData) {
                document.getElementById('modalTitle').innerText = 'Edit Kartu Keluarga';
                document.getElementById('kk_id').value = editData.id;
                document.getElementById('no_kk').value = editData.no_kk;
                document.getElementById('user_id').value = editData.user_id;
                document.getElementById('rt_rw').value = editData.rt_rw;
                document.getElementById('alamat').value = editData.alamat;
                document.getElementById('desa_kelurahan').value = editData.desa_kelurahan;
                document.getElementById('kecamatan').value = editData.kecamatan;
                document.getElementById('kabupaten').value = editData.kabupaten;
                document.getElementById('provinsi').value = editData.provinsi;
                document.getElementById('kode_pos').value = editData.kode_pos;
                document.getElementById('anggota-container').innerHTML = '';
                anggotaCount = 0;
                if (editAnggota.length > 0) {
                    editAnggota.forEach(function(anggota) {
                        tambahAnggota(anggota);
                    });
                } else {
                    tambahAnggota();
                }
                document.getElementById('kkModal').style.display = 'flex';
            }
        };
        <?php endif; ?>

        function tambahAnggota(data = null) {
            const container = document.getElementById('anggota-container');
            const index = anggotaCount;
            const row = document.createElement('div');
            row.className = 'anggota-row';
            row.id = 'anggota-' + index;

            let html = `
                <div class="form-group">
                    <label>NIK *</label>
                    <input type="text" name="nik[${index}]" class="form-control" value="${data ? data.nik : ''}" required>
                </div>
                <div class="form-group">
                    <label>Nama *</label>
                    <input type="text" name="nama[${index}]" class="form-control" value="${data ? data.nama : ''}" required>
                </div>
                <div class="form-group">
                    <label>Tempat Lahir *</label>
                    <input type="text" name="tempat_lahir[${index}]" class="form-control" value="${data ? data.tempat_lahir : ''}" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir *</label>
                    <input type="date" name="tanggal_lahir[${index}]" class="form-control" value="${data ? data.tanggal_lahir : ''}" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin *</label>
                    <select name="jenis_kelamin[${index}]" class="form-control" required>
                        <option value="L" ${data && data.jenis_kelamin == 'L' ? 'selected' : ''}>Laki-laki</option>
                        <option value="P" ${data && data.jenis_kelamin == 'P' ? 'selected' : ''}>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agama *</label>
                    <input type="text" name="agama[${index}]" class="form-control" value="${data ? data.agama : ''}" required>
                </div>
                <div class="form-group">
                    <label>Pendidikan *</label>
                    <input type="text" name="pendidikan[${index}]" class="form-control" value="${data ? data.pendidikan : ''}" required>
                </div>
                <div class="form-group">
                    <label>Pekerjaan *</label>
                    <input type="text" name="pekerjaan[${index}]" class="form-control" value="${data ? data.pekerjaan : ''}" required>
                </div>
                <div class="form-group">
                    <label>Status Perkawinan *</label>
                    <input type="text" name="status_perkawinan[${index}]" class="form-control" value="${data ? data.status_perkawinan : ''}" required>
                </div>
                <div class="form-group">
                    <label>Status Keluarga *</label>
                    <input type="text" name="status_keluarga[${index}]" class="form-control" value="${data ? data.status_keluarga : ''}" required>
                </div>
                <div class="remove-anggota" onclick="hapusAnggota(${index})">
                    <i class="fas fa-times"></i>
                </div>
            `;

            row.innerHTML = html;
            container.appendChild(row);
            anggotaCount++;
        }

        function hapusAnggota(index) {
            const row = document.getElementById('anggota-' + index);
            if (row) row.remove();
        }

        function viewKK(id) {
            fetch('get_kk_detail.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <h3 style="margin-bottom: 15px; color: var(--accent);"><i class="fas fa-home"></i> Data Kartu Keluarga</h3>
                            <table style="width: 100%; margin-bottom: 30px; color: white;">
                                <tr><td style="width: 180px; padding: 8px 0;"><strong>No. KK</strong></td><td style="padding: 8px 0;">: ${data.kk.no_kk}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Alamat</strong></td><td style="padding: 8px 0;">: ${data.kk.alamat}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>RT/RW</strong></td><td style="padding: 8px 0;">: ${data.kk.rt_rw}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Desa/Kelurahan</strong></td><td style="padding: 8px 0;">: ${data.kk.desa_kelurahan}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Kecamatan</strong></td><td style="padding: 8px 0;">: ${data.kk.kecamatan}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Kabupaten</strong></td><td style="padding: 8px 0;">: ${data.kk.kabupaten}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Provinsi</strong></td><td style="padding: 8px 0;">: ${data.kk.provinsi}</td></tr>
                                <tr><td style="padding: 8px 0;"><strong>Kode Pos</strong></td><td style="padding: 8px 0;">: ${data.kk.kode_pos}</td></tr>
                            90
                            <h3 style="margin-bottom: 15px; color: var(--accent);"><i class="fas fa-users"></i> Anggota Keluarga</h3>
                        `;
                        if (data.anggota.length > 0) {
                            html += '<table style="width: 100%; color: white; border-collapse: collapse;">';
                            html += '<thead><tr style="border-bottom: 1px solid rgba(255,255,255,0.2);"><th style="padding: 10px 5px;">NIK</th><th style="padding: 10px 5px;">Nama</th><th style="padding: 10px 5px;">Tempat Lahir</th><th style="padding: 10px 5px;">Tanggal Lahir</th><th style="padding: 10px 5px;">JK</th><th style="padding: 10px 5px;">Status Keluarga</th></tr></thead><tbody>';
                            data.anggota.forEach(a => {
                                html += `<tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 10px 5px;">${a.nik}</td>
                                    <td style="padding: 10px 5px;">${a.nama}</td>
                                    <td style="padding: 10px 5px;">${a.tempat_lahir}</td>
                                    <td style="padding: 10px 5px;">${a.tanggal_lahir}</td>
                                    <td style="padding: 10px 5px;">${a.jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                                    <td style="padding: 10px 5px;">${a.status_keluarga}</td>
                                </tr>`;
                            });
                            html += '</tbody></table>';
                        } else {
                            html += '<p style="color: rgba(255,255,255,0.7);">Tidak ada anggota keluarga.</p>';
                        }
                        document.getElementById('detailContent').innerHTML = html;
                        document.getElementById('detailModal').style.display = 'flex';
                    } else {
                        alert('Gagal mengambil data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengambil data.');
                });
        }

        function closeDetailModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        // Mobile toggle
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