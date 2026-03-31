<?php
// users/bantuan.php - Halaman Bantuan dan Tutorial
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan - e-RT Digital</title>
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

        .bantuan-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .bantuan-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .bantuan-card h2 {
            font-size: 26px;
            margin-bottom: 20px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bantuan-card h2 i {
            font-size: 28px;
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-list li {
            margin-bottom: 15px;
            padding-left: 35px;
            position: relative;
            font-size: 16px;
            line-height: 1.6;
            color: rgba(255,255,255,0.9);
        }

        .step-list li:before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            width: 24px;
            height: 24px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: bold;
        }

        .step-list li:nth-child(1):before { content: '1'; }
        .step-list li:nth-child(2):before { content: '2'; }
        .step-list li:nth-child(3):before { content: '3'; }
        .step-list li:nth-child(4):before { content: '4'; }
        .step-list li:nth-child(5):before { content: '5'; }
        .step-list li:nth-child(6):before { content: '6'; }

        .note {
            margin-top: 20px;
            padding: 15px 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            border-left: 5px solid var(--accent);
            color: rgba(255,255,255,0.9);
            font-size: 15px;
        }

        .note i {
            color: var(--accent);
            margin-right: 8px;
        }

        .btn-link {
            display: inline-block;
            margin-top: 15px;
            background: var(--secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
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

        .footer p {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .bantuan-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital</h1></div>
        </div>
        <div class="nav-menu">
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

    <div class="container">
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-question-circle"></i>
                <h1>Pusat Bantuan</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="bantuan-section">
            <!-- 1. Cara Membuat Pengaduan -->
            <div class="bantuan-card">
                <h2><i class="fas fa-comment-medical"></i> Cara Membuat Pengaduan</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Pengaduan"</strong> di navbar.</li>
                    <li>Isi formulir dengan judul pengaduan yang jelas (contoh: Jalan berlubang di depan rumah No.15).</li>
                    <li>Pilih kategori pengaduan yang sesuai (Kebersihan, Keamanan, Infrastruktur, dll).</li>
                    <li>Masukkan lokasi kejadian secara detail (RT, RW, alamat).</li>
                    <li>Tulis deskripsi lengkap masalah Anda (minimal 20 karakter).</li>
                    <li>Jika ada, upload foto bukti (format JPG, PNG, GIF, maks 20MB).</li>
                    <li>Tentukan tingkat urgensi (Rendah, Sedang, Tinggi).</li>
                    <li>Klik tombol <strong>"Kirim Pengaduan"</strong>.</li>
                </ul>
                <div class="note">
                    <i class="fas fa-info-circle"></i> Setelah dikirim, Anda akan mendapatkan nomor tiket. Status pengaduan dapat dilacak di menu <strong>"Riwayat"</strong>.
                </div>
                <a href="pengaduan.php" class="btn-link"><i class="fas fa-arrow-right"></i> Buat Pengaduan Sekarang</a>
            </div>

            <!-- 2. Cara Mengajukan Surat -->
            <div class="bantuan-card">
                <h2><i class="fas fa-envelope-open-text"></i> Cara Mengajukan Surat</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Surat"</strong> di navbar.</li>
                    <li>Pilih jenis surat yang Anda butuhkan (Surat Pengantar, SKTM, Surat Keterangan, dll).</li>
                    <li>Jelaskan keperluan surat secara detail (misal: untuk pembuatan KTP, pendaftaran sekolah, dll).</li>
                    <li>Tambahkan keterangan tambahan jika diperlukan.</li>
                    <li>Lampirkan file pendukung (KTP, KK, dll) jika diperlukan (opsional).</li>
                    <li>Klik tombol <strong>"Kirim Pengajuan Surat"</strong>.</li>
                </ul>
                <div class="note">
                    <i class="fas fa-info-circle"></i> Surat akan diproses dalam 1-3 hari kerja. Status pengajuan dapat dicek di menu <strong>"Riwayat"</strong> tab Surat.
                </div>
                <a href="surat.php" class="btn-link"><i class="fas fa-arrow-right"></i> Ajukan Surat Sekarang</a>
            </div>

            <!-- 3. Cara Melihat Riwayat dan Status -->
            <div class="bantuan-card">
                <h2><i class="fas fa-history"></i> Cara Melihat Riwayat & Status</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Riwayat"</strong> di navbar.</li>
                    <li>Anda akan melihat dua tab: <strong>Pengaduan</strong> dan <strong>Surat</strong>.</li>
                    <li>Pilih tab yang ingin Anda lihat.</li>
                    <li>Setiap pengaduan/surat ditampilkan dengan status (Baru, Diproses, Selesai, Ditolak).</li>
                    <li>Klik tombol <strong>"Detail"</strong> untuk melihat informasi lengkap.</li>
                    <li>Untuk surat yang sudah selesai, Anda dapat mengunduh file surat (jika ada).</li>
                </ul>
                <a href="riwayat.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Riwayat</a>
            </div>

            <!-- 4. Cara Membayar Iuran -->
            <div class="bantuan-card">
                <h2><i class="fas fa-money-bill-wave"></i> Cara Membayar Iuran</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Iuran"</strong> di navbar.</li>
                    <li>Anda akan melihat daftar iuran yang harus dibayar (per periode).</li>
                    <li>Pada baris iuran dengan status <strong>"Belum Bayar"</strong>, klik tombol <strong>"Bayar"</strong>.</li>
                    <li>Masukkan jumlah yang harus dibayar (biasanya sudah terisi otomatis).</li>
                    <li>Pilih metode pembayaran (Transfer, Tunai, E-Wallet).</li>
                    <li>Upload bukti pembayaran (jika metode transfer).</li>
                    <li>Klik <strong>"Konfirmasi Pembayaran"</strong>.</li>
                </ul>
                <div class="note">
                    <i class="fas fa-info-circle"></i> Status akan berubah menjadi "Diproses" dan admin akan memverifikasi pembayaran.
                </div>
                <a href="iuran.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Iuran Saya</a>
            </div>

            <!-- 5. Cara Melihat Pengumuman -->
            <div class="bantuan-card">
                <h2><i class="fas fa-bullhorn"></i> Cara Melihat Pengumuman</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Pengumuman"</strong> di navbar.</li>
                    <li>Anda akan melihat daftar pengumuman terbaru.</li>
                    <li>Pengumuman penting biasanya ditandai dengan label <strong>"PENTING"</strong>.</li>
                    <li>Klik <strong>"Baca Selengkapnya"</strong> untuk melihat isi lengkap.</li>
                </ul>
                <a href="pengumuman.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Pengumuman</a>
            </div>

            <!-- 6. Cara Mengelola Data KK -->
            <div class="bantuan-card">
                <h2><i class="fas fa-address-card"></i> Cara Melihat Data KK</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Data KK"</strong> di navbar.</li>
                    <li>Anda akan melihat daftar Kartu Keluarga yang terdaftar.</li>
                    <li>Gunakan fitur pencarian NIK untuk mencari anggota keluarga tertentu.</li>
                    <li>Klik tombol <strong>"Reset"</strong> untuk kembali ke daftar semua KK.</li>
                </ul>
                <div class="note">
                    <i class="fas fa-info-circle"></i> Data KK hanya dapat ditambah/diubah oleh admin. Jika ada perubahan data, hubungi pengurus RT.
                </div>
                <a href="kk.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Data KK</a>
            </div>

            <!-- 7. Cara Melihat Galeri -->
            <div class="bantuan-card">
                <h2><i class="fas fa-images"></i> Cara Melihat Galeri Warga</h2>
                <ul class="step-list">
                    <li>Klik menu <strong>"Galeri"</strong> di navbar.</li>
                    <li>Anda akan melihat foto-foto kegiatan warga yang diupload oleh admin.</li>
                    <li>Klik pada foto untuk melihat lebih besar (jika diimplementasikan).</li>
                </ul>
                <a href="galeri.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Galeri</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 e-RT Digital - Sistem Pengaduan Warga</p>
    </footer>
</body>
</html>