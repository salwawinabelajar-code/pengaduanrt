<?php
session_start();

// Koneksi database
$host = 'localhost';
$dbname = 'pengaduan_rt';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$error_msg = '';
$success = false;

// Proses form pengajuan surat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jenis_surat'])) {
    $jenis_surat = htmlspecialchars($_POST['jenis_surat']);
    $keperluan = htmlspecialchars($_POST['keperluan']);
    $keterangan = htmlspecialchars($_POST['keterangan'] ?? '');
    
    if (empty($jenis_surat) || empty($keperluan)) {
        $error_msg = 'Jenis surat dan keperluan harus diisi!';
    } else {
        $file_name = null;
        if (isset($_FILES['file_pendukung']) && $_FILES['file_pendukung']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_pendukung'];
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_msg = 'Format file tidak didukung. Gunakan: PDF, DOC, DOCX, JPG, PNG';
            } elseif ($file_size > $max_size) {
                $error_msg = 'Ukuran file terlalu besar. Maksimal 5MB';
            } else {
                $file_name = 'surat_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_dir = 'uploads/surat/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_path = $upload_dir . $file_name;
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    $error_msg = 'Gagal mengupload file.';
                }
            }
        } elseif ($_FILES['file_pendukung']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error_msg = 'Terjadi error saat upload file.';
        }
        
        if (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO pengajuan_surat 
                    (user_id, nama_user, jenis_surat, keperluan, keterangan, file_pendukung, status, tanggal_pengajuan) 
                    VALUES (:user_id, :nama_user, :jenis_surat, :keperluan, :keterangan, :file_pendukung, 'menunggu', NOW())");
                
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':nama_user' => $user['nama'],
                    ':jenis_surat' => $jenis_surat,
                    ':keperluan' => $keperluan,
                    ':keterangan' => $keterangan,
                    ':file_pendukung' => $file_name
                ]);
                
                // Redirect ke halaman riwayat tab surat
                header('Location: riwayat.php?tab=surat');
                exit();
            } catch(PDOException $e) {
                $error_msg = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Surat - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        /* Navbar glass */
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

        .user-profile a {
            text-decoration: none;
            color: white;
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

        /* Container utama */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page header */
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

        /* Alert messages */
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

        /* Form container glass */
        .form-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            color: white;
        }

        .card-icon {
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

        .card-title {
            font-size: 28px;
            font-weight: 700;
        }

        .card-subtitle {
            color: rgba(255,255,255,0.8);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 30px;
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

        /* Surat options (card pilihan jenis surat) */
        .surat-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 10px;
        }

        .surat-option {
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 25px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            min-height: 200px;
            justify-content: center;
        }

        .surat-option:hover {
            border-color: var(--accent);
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
        }

        .surat-option.selected {
            border-color: var(--accent);
            background: rgba(59,193,168,0.2);
            font-weight: 600;
        }

        .surat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .surat-label {
            font-weight: 600;
            color: white;
            font-size: 18px;
        }

        .surat-desc {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            line-height: 1.4;
        }

        /* File upload */
        .file-upload {
            position: relative;
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 35px;
            text-align: center;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--accent);
            background: rgba(255,255,255,0.1);
        }

        .file-upload input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload .icon {
            font-size: 48px;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .file-upload p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 8px;
        }

        .file-upload .file-info {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }

        #file_name {
            color: var(--accent) !important;
            font-weight: 600;
            margin-top: 15px;
        }

        /* Submit button */
        .btn {
            padding: 16px 30px;
            border-radius: 40px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
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

        /* Tips box */
        .tips-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-left: 6px solid var(--accent);
            border-radius: 30px;
            padding: 25px 30px;
            margin-top: 40px;
            color: white;
        }

        .tips-title {
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .tips-list {
            list-style-type: none;
        }

        .tips-list li {
            margin-bottom: 12px;
            color: rgba(255,255,255,0.9);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 15px;
            line-height: 1.5;
        }

        .tips-list li:before {
            content: "•";
            color: var(--accent);
            font-weight: bold;
            font-size: 18px;
        }

        /* Footer */
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

        /* Responsive */
        @media (max-width: 900px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
            .surat-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .surat-options {
                grid-template-columns: 1fr;
            }
            .btn {
                font-size: 16px;
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
            <a href="pengaduan.php">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="surat.php" class="active">Surat</a>
            <a href="kk.php">Data KK</a>
        </div>
        
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama'] ?? 'User'); ?></h4>
                    <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
                </div>
            </a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <!-- Main container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-envelope-open-text"></i>
                <h1>Pengajuan Surat</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>Berhasil! Pengajuan surat Anda telah dikirim. Anda dapat melacak statusnya di halaman Riwayat.</div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>Gagal! <?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h2 class="card-title">Form Pengajuan Surat</h2>
                    <p class="card-subtitle">Isi form untuk mengajukan pembuatan surat</p>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" id="suratForm">
                <!-- Jenis Surat -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i> Jenis Surat <span class="required">*</span>
                    </label>
                    <div class="surat-options">
                        <div class="surat-option" data-value="surat pengantar">
                            <div class="surat-icon"><i class="fas fa-file-export"></i></div>
                            <div>
                                <div class="surat-label">Surat Pengantar</div>
                                <div class="surat-desc">Untuk pengurusan KTP, KK, BPJS, dan dokumen resmi lainnya</div>
                            </div>
                        </div>
                        <div class="surat-option" data-value="surat keterangan tidak mampu">
                            <div class="surat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                            <div>
                                <div class="surat-label">Surat Keterangan Tidak Mampu</div>
                                <div class="surat-desc">Untuk bantuan sosial, beasiswa, atau program pemerintah</div>
                            </div>
                        </div>
                        <div class="surat-option" data-value="surat keterangan">
                            <div class="surat-icon"><i class="fas fa-file-certificate"></i></div>
                            <div>
                                <div class="surat-label">Surat Keterangan</div>
                                <div class="surat-desc">Untuk administrasi kerja, studi, atau keperluan umum</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="jenis_surat" name="jenis_surat" value="" required>
                </div>

                <!-- Keperluan -->
                <div class="form-group">
                    <label for="keperluan"><i class="fas fa-list-alt"></i> Keperluan Surat <span class="required">*</span></label>
                    <textarea id="keperluan" name="keperluan" class="form-control" placeholder="Jelaskan secara detail untuk keperluan apa surat ini dibutuhkan." required></textarea>
                </div>

                <!-- Keterangan Tambahan -->
                <div class="form-group">
                    <label for="keterangan"><i class="fas fa-info-circle"></i> Keterangan Tambahan (Opsional)</label>
                    <textarea id="keterangan" name="keterangan" class="form-control" placeholder="Tambahkan keterangan lain jika diperlukan."></textarea>
                </div>

                <!-- File Pendukung -->
                <div class="form-group">
                    <label><i class="fas fa-paperclip"></i> File Pendukung (Opsional)</label>
                    <div class="file-upload">
                        <input type="file" name="file_pendukung" id="file_pendukung" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <p>Klik atau drag file untuk upload</p>
                        <p class="file-info">Maksimal ukuran file: 5MB - Format: PDF, DOC, DOCX, JPG, PNG</p>
                        <p id="file_name"></p>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Kirim Pengajuan Surat</button>
            </form>

            <!-- Tips Box -->
            <div class="tips-box">
                <div class="tips-title"><i class="fas fa-lightbulb"></i> Informasi Penting</div>
                <ul class="tips-list">
                    <li>Surat akan diproses dalam 1-3 hari kerja setelah pengajuan diterima.</li>
                    <li>Lampirkan dokumen pendukung (KTP, KK, dll) jika diperlukan untuk mempercepat verifikasi.</li>
                    <li>Status pengajuan dapat dilacak di halaman Riwayat > Tab Surat.</li>
                    <li>Pastikan informasi yang diberikan lengkap dan jelas.</li>
                    <li>Surat yang sudah selesai dapat diambil di sekretariat RT.</li>
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
        const suratOptions = document.querySelectorAll('.surat-option');
        const jenisSuratInput = document.getElementById('jenis_surat');

        suratOptions.forEach(option => {
            option.addEventListener('click', function() {
                suratOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                jenisSuratInput.value = this.dataset.value;
                this.style.transform = 'translateY(-2px)';
                setTimeout(() => this.style.transform = '', 150);
            });
        });

        if (suratOptions.length > 0 && !jenisSuratInput.value) {
            suratOptions[0].click();
        }

        document.getElementById('file_pendukung').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileNameDisplay = document.getElementById('file_name');
            const fileUploadArea = document.querySelector('.file-upload');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                const fileName = file.name.length > 30 ? file.name.substring(0,30)+'...' : file.name;
                fileNameDisplay.textContent = `File terpilih: ${fileName} (${fileSize} MB)`;
                fileNameDisplay.style.color = 'var(--success)';
                fileUploadArea.style.borderColor = 'var(--success)';
                fileUploadArea.style.backgroundColor = 'rgba(6,214,160,0.05)';
                setTimeout(() => {
                    fileUploadArea.style.borderColor = '';
                    fileUploadArea.style.backgroundColor = '';
                }, 2000);
            } else {
                fileNameDisplay.textContent = '';
            }
        });

        document.getElementById('suratForm').addEventListener('submit', function(e) {
            const jenisSurat = document.getElementById('jenis_surat').value;
            const keperluan = document.getElementById('keperluan').value.trim();
            
            if (!jenisSurat) {
                e.preventDefault();
                alert('Mohon pilih jenis surat yang dibutuhkan!');
                suratOptions.forEach(opt => opt.style.borderColor = 'var(--danger)');
                return false;
            }
            if (!keperluan) {
                e.preventDefault();
                alert('Mohon isi keperluan surat!');
                document.getElementById('keperluan').focus();
                return false;
            }
            
            const fileInput = document.getElementById('file_pendukung');
            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size;
                if (fileSize > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file terlalu besar! Maksimal 5MB.');
                    fileInput.value = '';
                    document.getElementById('file_name').textContent = 'File terlalu besar, pilih file lain';
                    return false;
                }
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim Pengajuan...';
            submitBtn.disabled = true;
        });

        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        const fileUpload = document.querySelector('.file-upload');
        fileUpload.addEventListener('dragover', e => {
            e.preventDefault();
            fileUpload.style.borderColor = 'var(--secondary)';
            fileUpload.style.backgroundColor = 'rgba(36,158,148,0.1)';
        });
        fileUpload.addEventListener('dragleave', e => {
            e.preventDefault();
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
        });
        fileUpload.addEventListener('drop', e => {
            e.preventDefault();
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('file_pendukung').files = files;
                document.getElementById('file_pendukung').dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>