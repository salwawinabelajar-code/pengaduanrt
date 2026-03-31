<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user dari database
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    if ($result_user && mysqli_num_rows($result_user) > 0) {
        $user = mysqli_fetch_assoc($result_user);
    }
    mysqli_stmt_close($stmt_user);
} else {
    die("Error preparing user query: " . mysqli_error($conn));
}

// Deklarasi variabel
$success = "";
$error = "";

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $urgensi = mysqli_real_escape_string($conn, $_POST['urgensi'] ?? 'sedang');
    
    // Validasi
    if (empty($judul) || empty($kategori) || empty($deskripsi)) {
        $error = "Harap isi semua field yang wajib diisi!";
    } elseif (strlen($deskripsi) < 20) {
        $error = "Deskripsi terlalu pendek. Minimal 20 karakter!";
    } else {
        $foto_path = null;
        
        // Proses upload foto jika ada
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $maxFileSize = 20 * 1024 * 1024; // 20MB
            
            if ($_FILES['foto']['size'] > $maxFileSize) {
                $error = "Ukuran file foto melebihi batas maksimal 20MB!";
            } else {
                // Validasi tipe file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $file_type = mime_content_type($_FILES['foto']['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Format file tidak didukung. Gunakan format JPG, PNG, atau GIF.";
                } else {
                    // Buat nama file unik
                    $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'foto_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_dir = '../uploads/pengaduan/';
                    
                    // Buat folder jika belum ada
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $target_file = $upload_dir . $new_filename;
                    
                    // Upload file
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                        $foto_path = 'uploads/pengaduan/' . $new_filename;
                    } else {
                        $error = "Gagal mengupload file foto.";
                    }
                }
            }
        } elseif ($_FILES['foto']['error'] !== 4 && $_FILES['foto']['error'] !== 0) {
            // Error selain "no file uploaded"
            $error = "Terjadi kesalahan saat mengupload file: Error code " . $_FILES['foto']['error'];
        }
        
        // Jika tidak ada error, simpan ke database
        if (empty($error)) {
            // Generate nomor tiket unik
            $no_tiket = 'TKT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Query untuk menyimpan pengaduan
            $query = "INSERT INTO pengaduan (user_id, no_tiket, judul, kategori, lokasi, deskripsi, foto, urgensi, status, tanggal) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'baru', NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $no_tiket, $judul, $kategori, $lokasi, $deskripsi, $foto_path, $urgensi);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Pengaduan berhasil dikirim! Nomor tiket: " . $no_tiket;
                    
                    // Reset form values
                    $_POST = [];
                    
                    // Redirect ke riwayat setelah 3 detik (opsional)
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "riwayat.php?tab=pengaduan";
                        }, 3000);
                    </script>';
                } else {
                    $error = "Gagal menyimpan pengaduan ke database: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Error preparing query: " . mysqli_error($conn);
            }
        }
    }
}

// Pastikan tabel pengaduan ada di database dengan pengecekan yang aman
$table_exists = false;
$check_table = "SHOW TABLES LIKE 'pengaduan'";
$table_result = mysqli_query($conn, $check_table);
if ($table_result) {
    $table_exists = mysqli_num_rows($table_result) > 0;
} else {
    // Jika query gagal, kita asumsikan tabel belum ada, tapi bisa juga error koneksi
    // Tampilkan error jika diperlukan, tapi kita tetap lanjut
    error_log("Error checking table existence: " . mysqli_error($conn));
}

if (!$table_exists) {
    // Buat tabel jika belum ada
    $create_table = "CREATE TABLE pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        no_tiket VARCHAR(20) NOT NULL UNIQUE,
        judul VARCHAR(255) NOT NULL,
        kategori VARCHAR(50) NOT NULL,
        lokasi VARCHAR(255),
        deskripsi TEXT NOT NULL,
        foto VARCHAR(255),
        urgensi ENUM('rendah', 'sedang', 'tinggi') DEFAULT 'sedang',
        status ENUM('baru', 'diproses', 'selesai', 'ditolak') DEFAULT 'baru',
        tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        $error = "Error creating table: " . mysqli_error($conn);
    } else {
        // Opsional: set tabel sudah ada setelah berhasil dibuat
        $table_exists = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengaduan - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== RESET & VARIABEL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

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

        /* Overlay dengan gradasi tema */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }

        /* ===== NAVBAR GLASS ===== */
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
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            background: var(--accent);
            width: 45px;
            height: 45px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .logo-text h1 {
            font-size: 22px;
            color: white;
            font-weight: 700;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .nav-menu {
            display: flex;
            gap: 15px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 30px;
            transition: 0.3s;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .nav-menu a:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }

        .nav-menu a.active {
            background: var(--secondary);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            border: 2px solid white;
        }

        .user-info {
            color: white;
        }

        .user-info h4 {
            font-size: 16px;
        }

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

        .logout-btn:hover {
            background: var(--danger);
        }

        /* ===== KONTAINER UTAMA ===== */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* ===== HEADER HALAMAN ===== */
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

        /* ===== ALERT ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 30px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .alert-success {
            background: rgba(6,214,160,0.2);
            color: white;
        }

        .alert-danger {
            background: rgba(239,71,111,0.2);
            color: white;
        }

        .alert-info {
            background: rgba(17,138,178,0.2);
            color: white;
        }

        /* ===== FORM CONTAINER GLASS ===== */
        .form-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            color: white;
        }

        .form-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--accent);
        }

        .form-title h2 {
            font-size: 28px;
            font-weight: 700;
        }

        .form-title p {
            color: rgba(255,255,255,0.8);
        }

        /* ===== FORM GRID ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 16px;
            transition: all 0.3s;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 3px rgba(59,193,168,0.2);
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }

        textarea.form-control {
            min-height: 140px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ffffff' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 16px;
            padding-right: 45px;
        }

        select.form-control option {
            background: var(--primary);
            color: white;
        }

        /* ===== FILE UPLOAD ===== */
        .file-upload {
            position: relative;
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--accent);
            background: rgba(255,255,255,0.1);
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 48px;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .upload-text h4 {
            color: white;
            margin-bottom: 5px;
        }

        .upload-text p {
            color: rgba(255,255,255,0.7);
        }

        .file-preview {
            margin-top: 15px;
            display: none;
        }

        .file-preview img {
            max-width: 200px;
            border-radius: 15px;
            border: 2px solid var(--accent);
        }

        /* ===== URGENSI RADIO ===== */
        .urgensi-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .urgensi-option {
            flex: 1;
            text-align: center;
        }

        .urgensi-option input {
            display: none;
        }

        .urgensi-label {
            display: block;
            padding: 15px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .urgensi-label:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
        }

        .urgensi-option input:checked + .urgensi-label {
            border-color: var(--accent);
            background: rgba(59,193,168,0.2);
            box-shadow: 0 5px 15px rgba(59,193,168,0.3);
        }

        .urgensi-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .urgensi-rendah .urgensi-icon { color: var(--success); }
        .urgensi-sedang .urgensi-icon { color: var(--warning); }
        .urgensi-tinggi .urgensi-icon { color: var(--danger); }

        /* ===== FORM ACTIONS ===== */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid rgba(255,255,255,0.2);
        }

        .btn {
            padding: 14px 30px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 140px;
        }

        .btn-reset {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-reset:hover {
            background: rgba(255,255,255,0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            box-shadow: 0 8px 20px rgba(36,158,148,0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(36,158,148,0.5);
        }

        /* ===== INFO BOX ===== */
        .info-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 25px;
            border-left: 6px solid var(--accent);
            color: white;
        }

        .info-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .info-content ul {
            list-style: none;
        }

        .info-content li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
            color: rgba(255,255,255,0.9);
        }

        .info-content li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-weight: bold;
        }

        /* ===== FOOTER ===== */
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

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer p {
            opacity: 0.9;
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }

        @media (max-width: 600px) {
            .urgensi-options {
                flex-direction: column;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php">Beranda</a>
            <a href="pengaduan.php" class="active">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php">Data KK</a>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($user['nama'] ?? 'User'); ?></h4>
                <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
            </div>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <!-- Main container -->
    <div class="container">
        <!-- Page Header dengan tombol kembali -->
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-comment-medical"></i>
                <h1>Buat Pengaduan</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?php echo htmlspecialchars($success); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <?php if (!$table_exists): ?>
        <div class="alert alert-info">
            <i class="fas fa-database"></i>
            <div>
                <strong>Perhatian:</strong> Tabel pengaduan belum ada di database. 
                Sistem akan membuat tabel secara otomatis saat pertama kali mengirim pengaduan.
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="form-title">
                    <h2>Form Pengaduan Warga</h2>
                    <p>Isi dengan lengkap dan jelas</p>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" id="pengaduanForm">
                <input type="hidden" name="MAX_FILE_SIZE" value="20971520">
                
                <div class="form-grid">
                    <!-- Judul -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-heading"></i> Judul Pengaduan <span class="required">*</span></label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Jalan Berlubang di Depan Rumah No. 15" value="<?php echo htmlspecialchars($_POST['judul'] ?? ''); ?>" required>
                    </div>

                    <!-- Kategori -->
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Kategori <span class="required">*</span></label>
                       <?php
// Ambil kategori dari database (sesuaikan path koneksi)
require_once(__DIR__ . '/../config/db.php');
$query_kategori = "SELECT * FROM kategori_pengaduan ORDER BY nama";
$result_kategori = mysqli_query($conn, $query_kategori);
?>
<select id="kategori" name="kategori" class="form-control" required>
    <option value="">Pilih Kategori</option>
    <?php while ($kategori = mysqli_fetch_assoc($result_kategori)): ?>
        <option value="<?php echo htmlspecialchars($kategori['nama']); ?>" 
            <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == $kategori['nama']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($kategori['nama']); ?>
        </option>
    <?php endwhile; ?>
</select>
                    </div>

                    <!-- Lokasi -->
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Lokasi Kejadian</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: RT 05, depan rumah No. 15" value="<?php echo htmlspecialchars($_POST['lokasi'] ?? ''); ?>">
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Deskripsi Lengkap <span class="required">*</span></label>
                        <textarea name="deskripsi" class="form-control" placeholder="Jelaskan masalah Anda secara detail. Kapan terjadi? Bagaimana kondisi saat ini? Apa yang Anda harapkan?" required><?php echo htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
                        <small style="color: rgba(255,255,255,0.6); margin-top: 5px; display: block;">Semakin detail, semakin cepat ditangani</small>
                    </div>

                    <!-- Upload Foto -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-camera"></i> Upload Foto Bukti</label>
                        <div class="file-upload">
                            <input type="file" name="foto" accept="image/*">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <div class="upload-text">
                                <h4>Klik untuk Upload Foto</h4>
                                <p>Format: JPG, PNG, GIF (Maks. 20MB)</p>
                            </div>
                            <div class="file-preview" id="filePreview"></div>
                        </div>
                    </div>

                    <!-- Tingkat Urgensi -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-exclamation-triangle"></i> Tingkat Urgensi</label>
                        <div class="urgensi-options">
                            <div class="urgensi-option urgensi-rendah">
                                <input type="radio" id="urgensi_rendah" name="urgensi" value="rendah" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'rendah' ? 'checked' : ''; ?>>
                                <label for="urgensi_rendah" class="urgensi-label">
                                    <div class="urgensi-icon"><i class="far fa-smile"></i></div>
                                    <div>Rendah</div>
                                    <small>Bisa ditunda</small>
                                </label>
                            </div>
                            <div class="urgensi-option urgensi-sedang">
                                <input type="radio" id="urgensi_sedang" name="urgensi" value="sedang" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'sedang' ? 'checked' : ''; ?> checked>
                                <label for="urgensi_sedang" class="urgensi-label">
                                    <div class="urgensi-icon"><i class="far fa-meh"></i></div>
                                    <div>Sedang</div>
                                    <small>Perlu penanganan</small>
                                </label>
                            </div>
                            <div class="urgensi-option urgensi-tinggi">
                                <input type="radio" id="urgensi_tinggi" name="urgensi" value="tinggi" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'tinggi' ? 'checked' : ''; ?>>
                                <label for="urgensi_tinggi" class="urgensi-label">
                                    <div class="urgensi-icon"><i class="far fa-frown"></i></div>
                                    <div>Tinggi</div>
                                    <small>Harus segera</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="reset" class="btn btn-reset"><i class="fas fa-redo"></i> Reset Form</button>
                    <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Kirim Pengaduan</button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <div class="info-title"><i class="fas fa-info-circle"></i> Informasi Penting</div>
            <div class="info-content">
                <ul>
                    <li>Pengaduan Anda akan diproses maksimal 3 hari kerja</li>
                    <li>Pastikan data yang diisi akurat dan dapat dipertanggungjawabkan</li>
                    <li>Foto bukti akan membantu mempercepat proses penanganan</li>
                    <li>Anda dapat melacak status pengaduan di menu "Riwayat"</li>
                    <li>Pengaduan palsu atau tidak bertanggung jawab akan dikenai sanksi</li>
                    <li>Simpan nomor tiket untuk referensi dan pengecekan status</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 e-RT Digital - Sistem Pengaduan Warga</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Bantuan</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Kebijakan Privasi</a>
                <a href="#"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>

    <script>
        // File upload preview
        document.querySelector('.file-upload input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                if (file.size > 20 * 1024 * 1024) {
                    alert('Ukuran file maksimal 20MB!');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan format JPG, PNG, atau GIF.');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('pengaduanForm').addEventListener('submit', function(e) {
            const judul = document.querySelector('input[name="judul"]').value.trim();
            const kategori = document.querySelector('select[name="kategori"]').value;
            const deskripsi = document.querySelector('textarea[name="deskripsi"]').value.trim();
            
            if (!judul || !kategori || !deskripsi) {
                e.preventDefault();
                alert('Harap isi semua field yang wajib diisi!');
                return false;
            }
            if (deskripsi.length < 20) {
                e.preventDefault();
                alert('Deskripsi terlalu pendek. Minimal 20 karakter!');
                return false;
            }
        });

        // Auto-resize textarea
        const textarea = document.querySelector('textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>