<?php
session_start();

// Konfigurasi database
$host = 'localhost';
$dbname = 'pengaduan_rt';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
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

$role = $user['role'] ?? 'warga';
$success_msg = '';
$error_msg = '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email)) {
        $error_msg = 'Nama dan email harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Format email tidak valid.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_msg = 'Email sudah digunakan oleh pengguna lain.';
        } else {
            $update = $pdo->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?");
            if ($update->execute([$nama, $email, $no_hp, $alamat, $user_id])) {
                $success_msg = 'Profil berhasil diperbarui.';
                // Refresh data user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_msg = 'Gagal memperbarui profil.';
            }
        }
    }
}

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error_msg = 'Semua field password harus diisi.';
    } elseif ($new !== $confirm) {
        $error_msg = 'Konfirmasi password baru tidak cocok.';
    } elseif (strlen($new) < 6) {
        $error_msg = 'Password baru minimal 6 karakter.';
    } else {
        if (password_verify($current, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$hash, $user_id])) {
                $success_msg = 'Password berhasil diubah.';
            } else {
                $error_msg = 'Gagal mengubah password.';
            }
        } else {
            $error_msg = 'Password lama salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - e-RT Digital</title>
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
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
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

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
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

        .message {
            padding: 12px 18px;
            border-radius: 30px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-left: 6px solid;
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }

        .message.success {
            border-left-color: var(--success);
        }

        .message.error {
            border-left-color: var(--danger);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }

        .settings-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .settings-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
            border-color: var(--accent);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            color: white;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--accent);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .card-header h2 {
            font-size: 24px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: var(--accent);
            font-size: 18px;
        }

        .form-control {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
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
            box-shadow: 0 0 0 4px rgba(59,193,168,0.2);
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }

        textarea.form-control {
            padding: 14px 20px 14px 48px;
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 14px 25px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            box-shadow: 0 8px 20px rgba(36,158,148,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(36,158,148,0.5);
        }

        .footer {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            margin-top: 50px;
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

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar (tanpa menu Pengaturan) -->
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'],0,1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
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
                <i class="fas fa-user-circle"></i>
                <h1>Profil Saya</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Pesan -->
        <?php if ($success_msg): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Grid dua card -->
        <div class="settings-grid">
            <!-- Edit Profil -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user-edit"></i></div>
                    <h2>Edit Profil</h2>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="nama" name="nama" class="form-control" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="no_hp">No. HP</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone-alt input-icon"></i>
                            <input type="text" id="no_hp" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" placeholder="Contoh: 08123456789">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <textarea id="alamat" name="alamat" class="form-control" rows="3" placeholder="Jl. ... RT/RW ..."><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Ganti Password -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-lock"></i></div>
                    <h2>Ganti Password</h2>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Password Lama</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Masukkan password lama" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Ubah Password
                    </button>
                </form>
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
</body>
</html>