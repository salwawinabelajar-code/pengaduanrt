<?php
// dashboard.php - TAMPILAN SEPERTI GAMBAR DENGAN DESAIN MODERN
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
if (!$result_user) die("Error: " . mysqli_error($conn));
$user = mysqli_fetch_assoc($result_user);
if (!$user) $user = ['nama' => 'User', 'email' => 'user@example.com', 'role' => 'warga'];

// Ambil pengumuman terbaru
$pengumuman = [];
$query_pengumuman = "SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 6";
$result_pengumuman = mysqli_query($conn, $query_pengumuman);
if ($result_pengumuman) {
    while ($row = mysqli_fetch_assoc($result_pengumuman)) {
        $pengumuman[] = $row;
    }
}

// Ambil foto galeri terbaru (misal 8 foto)
$galeri = [];
$query_galeri = "SELECT * FROM galeri ORDER BY tanggal DESC, id DESC LIMIT 8";
$result_galeri = mysqli_query($conn, $query_galeri);
if ($result_galeri) {
    while ($row = mysqli_fetch_assoc($result_galeri)) {
        $galeri[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard RT 05 - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... (CSS tetap sama, tidak diubah) ... */
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
            color: #1e293b;
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

        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--secondary);
            transform: translateY(-2px);
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

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .rt-header {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px 40px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .rt-header h2 {
            font-size: 42px;
            color: white;
            font-weight: 800;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .rt-header p {
            color: rgba(255,255,255,0.9);
            font-size: 18px;
        }

        .rt-location {
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
        }

        .sambutan {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px 40px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .sambutan h3 {
            font-size: 28px;
            color: white;
            margin-bottom: 10px;
        }

        .sambutan p {
            color: rgba(255,255,255,0.9);
            max-width: 600px;
        }

        .btn-more {
            background: var(--accent);
            color: white;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-more:hover {
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(36,158,148,0.5);
        }

        .layanan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .layanan-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.4s;
            text-decoration: none;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .layanan-card:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.25);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .layanan-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: var(--accent);
            border: 2px solid rgba(255,255,255,0.3);
        }

        .layanan-card h4 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .layanan-card p {
            font-size: 14px;
            opacity: 0.8;
        }

        .pengumuman-utama {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            color: white;
        }

        .section-title h3 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 30px;
            transition: 0.3s;
        }

        .section-title a:hover {
            background: var(--accent);
        }

        .pengumuman-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .pengumuman-item {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: 0.3s;
        }

        .pengumuman-item:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-3px);
        }

        .pengumuman-item h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .pengumuman-item p {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .pengumuman-date {
            color: var(--accent);
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .galeri-full {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 40px;
        }

        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .galeri-item {
            aspect-ratio: 1;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }

        .galeri-item:hover {
            transform: scale(1.05);
            border-color: var(--accent);
        }

        .galeri-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .footer {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 40px 20px;
            margin-top: 40px;
            text-align: center;
            color: white;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .footer h4 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .footer p {
            color: rgba(255,255,255,0.8);
            margin: 5px 0;
        }

        .footer .contact {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            font-size: 16px;
        }

        .footer .contact i {
            color: var(--accent);
            margin-right: 5px;
        }

        @media (max-width: 900px) {
            .layanan-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .pengumuman-grid {
                grid-template-columns: 1fr;
            }
            .galeri-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 600px) {
            .layanan-grid {
                grid-template-columns: 1fr;
            }
            .rt-header h2 {
                font-size: 32px;
            }
            .galeri-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar dengan link profil, Data KK, Galeri, dan Bantuan -->
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="active">Beranda</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php">Data KK</a>
            <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <small><?php echo ucfirst($user['role']); ?></small>
                </div>
            </a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <!-- Main container (konten dashboard) ... (sama seperti sebelumnya) -->
    <div class="container">
        <!-- Header RT -->
        <div class="rt-header">
            <div>
                <h2>RT 05</h2>
                <p>Kelurahan Sukamaju</p>
            </div>
            <div class="rt-location">
                <i class="fas fa-map-marker-alt"></i> Jl. Mawar No.5, Sukamaju
            </div>
        </div>

        <!-- Sambutan -->
        <div class="sambutan">
            <div>
                <h3>Selamat Datang di RT 05 Kelurahan Sukamaju</h3>
                <p>Bersama Membangun Lingkungan yang Nyaman dan Harmonis</p>
            </div>
            <a href="#" class="btn-more">Selengkapnya <i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Layanan Cards (3 card) -->
        <div class="layanan-grid">
            <a href="surat.php" class="layanan-card">
                <div class="layanan-icon"><i class="fas fa-file-alt"></i></div>
                <h4>Layanan Warga</h4>
                <p>Pengajuan Surat</p>
            </a>
            <a href="pengumuman.php" class="layanan-card">
                <div class="layanan-icon"><i class="fas fa-bullhorn"></i></div>
                <h4>Pengumuman</h4>
                <p>Informasi Terbaru</p>
            </a>
            <a href="pengaduan.php" class="layanan-card">
                <div class="layanan-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h4>Laporkan</h4>
                <p>Aduan Warga</p>
            </a>
        </div>

        <!-- Pengumuman Utama -->
        <div class="pengumuman-utama">
            <div class="section-title">
                <h3><i class="fas fa-bullhorn"></i> Pengumuman & Informasi</h3>
                <a href="pengumuman.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="pengumuman-grid">
                <?php if (!empty($pengumuman)): ?>
                    <?php foreach (array_slice($pengumuman, 0, 4) as $item): ?>
                    <div class="pengumuman-item">
                        <h4><?php echo htmlspecialchars($item['judul']); ?></h4>
                        <p><?php echo htmlspecialchars(substr($item['isi'], 0, 100)); ?>...</p>
                        <div class="pengumuman-date"><i class="far fa-clock"></i> <?php echo date('d M Y', strtotime($item['created_at'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pengumuman-item">
                        <h4>Rapat Koordinasi Bulanan</h4>
                        <p>Pembahasan program dan kegiatan RT. Akan diadakan di balai RT.</p>
                        <div class="pengumuman-date"><i class="far fa-clock"></i> 20 April 2024</div>
                    </div>
                    <div class="pengumuman-item">
                        <h4>Pembagian Sembako Warga</h4>
                        <p>Bantuan sembako untuk warga yang membutuhkan. Segera daftar!</p>
                        <div class="pengumuman-date"><i class="far fa-clock"></i> 15 April 2024</div>
                    </div>
                    <div class="pengumuman-item">
                        <h4>Lomba 17 Agustus</h4>
                        <p>Segera daftarkan diri Anda untuk berbagai lomba menarik.</p>
                        <div class="pengumuman-date"><i class="far fa-clock"></i> Segera</div>
                    </div>
                    <div class="pengumuman-item">
                        <h4>Kerja Bakti Lingkungan</h4>
                        <p>Warga RT 05 bergotong royong membersihkan lingkungan.</p>
                        <div class="pengumuman-date"><i class="far fa-clock"></i> 25 April 2024</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Galeri Warga (lebar penuh, menampilkan foto asli) -->
        <div class="galeri-full">
            <div class="section-title">
                <h3><i class="fas fa-images"></i> Galeri Warga</h3>
                <a href="galeri.php">Lihat Semua</a>
            </div>
            <div class="galeri-grid">
                <?php if (!empty($galeri)): ?>
                    <?php foreach ($galeri as $foto): ?>
                    <div class="galeri-item">
                        <img src="../<?php echo htmlspecialchars($foto['foto']); ?>" alt="<?php echo htmlspecialchars($foto['judul']); ?>">
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback jika belum ada foto -->
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                    <div class="galeri-item"><img src="https://via.placeholder.com/150" alt="Galeri"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <h4>RT 05 Kelurahan Sukamaju</h4>
        <p>Jalan Mawar No. 5, Sukamaju, Kec. Sukajajaya, Kota Indah</p>
        <div class="contact">
            <span><i class="fas fa-phone"></i> 0812-3456-7890</span>
            <span><i class="fas fa-envelope"></i> rt05@sukamaju.id</span>
        </div>
        <p style="margin-top:20px; opacity:0.6;">&copy; 2024 e-RT Digital - Sistem Pengaduan Warga</p>
    </footer>
</body>
</html>