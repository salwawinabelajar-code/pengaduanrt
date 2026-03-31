Berikut adalah halaman `landing.php` yang telah diperbaiki dengan navbar yang seluruh tautannya mengarah ke `auth/login.php`, serta tampilan halaman yang panjang ke bawah dengan penjelasan detail setiap fitur. Halaman ini menampilkan ringkasan fitur dalam bentuk kartu, kemudian diikuti dengan bagian penjelasan lengkap per fitur yang dibuat bergantian kiri-kanan agar lebih menarik dan informatif.

```php
<?php
// landing.php - Halaman depan publik e-RT Digital
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-RT Digital - Solusi Digital untuk RT Modern</title>
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
            background: linear-gradient(145deg, rgba(0,84,97,0.85) 0%, rgba(36,158,148,0.75) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }

        /* Navbar */
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
            flex-wrap: wrap;
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
            flex-wrap: wrap;
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

        .btn-login {
            background: linear-gradient(135deg, var(--secondary), var(--accent)) !important;
            border: none !important;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .hero {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 0;
        }

        .hero-content {
            max-width: 800px;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .btn-hero {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(36,158,148,0.5);
        }

        /* Features Overview Cards */
        .features-overview {
            padding: 80px 0;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 50px;
            color: rgba(255,255,255,0.9);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            padding: 0 20px;
        }

        .feature-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 35px 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.4s;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.25);
            border-color: var(--accent);
        }

        .feature-icon {
            font-size: 48px;
            color: var(--accent);
            margin-bottom: 20px;
            display: inline-block;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .feature-card p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
        }

        .feature-desc {
            list-style: none;
            text-align: left;
            margin: 15px 0;
            padding-left: 0;
        }

        .feature-desc li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.8);
        }

        .feature-desc i {
            color: var(--accent);
            width: 20px;
        }

        .feature-link {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px 25px;
            border-radius: 40px;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
            font-weight: 600;
            margin-top: 20px;
        }

        .feature-link:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(36,158,148,0.4);
        }

        /* Detailed Feature Sections */
        .detail-section {
            padding: 80px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .detail-container {
            display: flex;
            align-items: center;
            gap: 60px;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .detail-text {
            flex: 1;
            min-width: 300px;
        }

        .detail-icon {
            flex: 0 0 120px;
            text-align: center;
        }

        .detail-icon i {
            font-size: 80px;
            color: var(--accent);
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .detail-text h3 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .detail-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.9);
        }

        .detail-list {
            list-style: none;
            margin: 20px 0;
        }

        .detail-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
        }

        .detail-list i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .btn-detail {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            padding: 12px 30px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            margin-top: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-detail:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(36,158,148,0.4);
        }

        /* Alternate layout for even sections */
        .detail-section:nth-child(even) .detail-container {
            flex-direction: row-reverse;
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            text-align: center;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(8px);
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            padding: 40px 20px;
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
            .hero h1 {
                font-size: 2.5rem;
            }
            .detail-container {
                flex-direction: column !important;
                text-align: center;
            }
            .detail-icon {
                margin-bottom: 20px;
            }
            .detail-list li {
                justify-content: center;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
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
            <a href="auth/login.php">Beranda</a>
            <a href="auth/login.php">Fitur</a>
            <a href="auth/login.php">Tentang</a>
            <a href="auth/login.php">Kontak</a>
            <a href="auth/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Selamat Datang di e-RT Digital</h1>
                <p>Sistem informasi terpadu untuk memudahkan pengelolaan administrasi RT. Dengan e-RT Digital, warga dapat mengakses layanan administrasi secara online, cepat, dan transparan. Bergabunglah bersama kami untuk mewujudkan lingkungan yang lebih baik.</p>
                <a href="auth/register.php" class="btn-hero"><i class="fas fa-user-plus"></i> Daftar Sekarang</a>
            </div>
        </div>
    </section>

    <!-- Fitur Ringkasan (Cards) -->
    <section id="features" class="features-overview">
        <div class="container">
            <h2 class="section-title">Fitur Unggulan e-RT Digital</h2>
            <p class="section-subtitle">Kami hadirkan berbagai fitur modern untuk memudahkan warga dan pengurus RT dalam mengelola administrasi dan komunikasi.</p>
            <div class="features-grid">
                <!-- Pengaduan -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-comment-medical"></i></div>
                    <h3>Pengaduan Warga</h3>
                    <p>Laporkan masalah di lingkungan RT Anda dengan mudah dan cepat. Pantau status laporan secara real-time.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Laporan dengan foto bukti</li>
                        <li><i class="fas fa-check-circle"></i> Kategori pengaduan lengkap</li>
                        <li><i class="fas fa-check-circle"></i> Notifikasi status terbaru</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Surat -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <h3>Pengajuan Surat</h3>
                    <p>Ajukan surat pengantar, SKTM, dan surat keterangan lainnya tanpa harus datang ke kantor RT.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Berbagai jenis surat tersedia</li>
                        <li><i class="fas fa-check-circle"></i> Upload file pendukung</li>
                        <li><i class="fas fa-check-circle"></i> Surat siap diunduh dalam format PDF</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Iuran -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h3>Iuran Warga</h3>
                    <p>Bayar iuran rutin secara online, lihat riwayat pembayaran, dan dapatkan bukti bayar digital.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Pembayaran via transfer/bukti upload</li>
                        <li><i class="fas fa-check-circle"></i> Riwayat pembayaran lengkap</li>
                        <li><i class="fas fa-check-circle"></i> Grafik pemasukan iuran</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Pengumuman -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bullhorn"></i></div>
                    <h3>Pengumuman</h3>
                    <p>Dapatkan informasi terbaru dari pengurus RT, seperti kegiatan, rapat, atau pemberitahuan penting.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Pengumuman penting dengan label khusus</li>
                        <li><i class="fas fa-check-circle"></i> Baca selengkapnya dengan satu klik</li>
                        <li><i class="fas fa-check-circle"></i> Informasi terkini setiap saat</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Data KK -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-address-card"></i></div>
                    <h3>Data Kartu Keluarga</h3>
                    <p>Lihat data KK dan anggota keluarga Anda secara lengkap. Data dikelola oleh admin untuk keakuratan.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Informasi KK dan anggota</li>
                        <li><i class="fas fa-check-circle"></i> Pencarian berdasarkan NIK</li>
                        <li><i class="fas fa-check-circle"></i> Data selalu up-to-date</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Galeri -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-images"></i></div>
                    <h3>Galeri Warga</h3>
                    <p>Nikmati dokumentasi kegiatan warga, acara RT, dan momen-momen kebersamaan dalam galeri digital.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Foto-foto kegiatan warga</li>
                        <li><i class="fas fa-check-circle"></i> Tampilan grid yang menarik</li>
                        <li><i class="fas fa-check-circle"></i> Update foto secara berkala</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Riwayat -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-history"></i></div>
                    <h3>Riwayat Aktivitas</h3>
                    <p>Pantau semua pengaduan, pengajuan surat, dan pembayaran iuran Anda dalam satu tempat.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Tab pengaduan dan surat terpisah</li>
                        <li><i class="fas fa-check-circle"></i> Filter berdasarkan status</li>
                        <li><i class="fas fa-check-circle"></i> Lihat detail lengkap</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Bantuan -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-question-circle"></i></div>
                    <h3>Pusat Bantuan</h3>
                    <p>Temukan panduan dan tutorial lengkap tentang cara menggunakan semua fitur e-RT Digital.</p>
                    <ul class="feature-desc">
                        <li><i class="fas fa-check-circle"></i> Panduan langkah demi langkah</li>
                        <li><i class="fas fa-check-circle"></i> FAQ interaktif</li>
                        <li><i class="fas fa-check-circle"></i> Dapat diedit oleh admin</li>
                    </ul>
                    <a href="auth/login.php" class="feature-link">Lihat Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Detail Fitur (Penjelasan Lengkap) -->
    <div class="container">
        <h2 class="section-title" style="margin-top: 60px;">Penjelasan Lengkap Fitur</h2>
        <p class="section-subtitle">Setiap fitur dirancang untuk memberikan kemudahan dan transparansi bagi warga serta pengurus RT. Berikut penjelasan lebih mendalam.</p>
    </div>

    <!-- Detail 1: Pengaduan -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-comment-medical"></i>
            </div>
            <div class="detail-text">
                <h3>Pengaduan Warga</h3>
                <p>Fitur pengaduan memungkinkan warga melaporkan berbagai masalah lingkungan seperti sampah, lampu jalan mati, genangan air, atau kejadian lainnya. Setiap laporan dapat dilengkapi dengan foto sebagai bukti, dan akan langsung diteruskan ke pengurus RT. Warga bisa memantau status laporan (menunggu, diproses, selesai) secara real-time.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Kategori pengaduan lengkap (kebersihan, keamanan, infrastruktur, dll)</li>
                    <li><i class="fas fa-check-circle"></i> Upload foto hingga 3 file</li>
                    <li><i class="fas fa-check-circle"></i> Notifikasi via email atau WhatsApp (jika terdaftar)</li>
                    <li><i class="fas fa-check-circle"></i> Riwayat pengaduan dapat diakses kapan saja</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Coba Fitur Pengaduan <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 2: Pengajuan Surat -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="detail-text">
                <h3>Pengajuan Surat</h3>
                <p>Warga dapat mengajukan berbagai jenis surat keterangan secara online tanpa perlu datang ke kantor RT. Cukup pilih jenis surat, isi data yang diperlukan, dan unggah dokumen pendukung (jika ada). Surat akan diproses oleh pengurus dan dapat diunduh dalam format PDF setelah disetujui.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Jenis surat: Pengantar KTP, SKTM, Keterangan Domisili, Surat Usaha, dll</li>
                    <li><i class="fas fa-check-circle"></i> Upload KTP, KK, atau berkas pendukung</li>
                    <li><i class="fas fa-check-circle"></i> Status pengajuan (menunggu verifikasi, siap diambil, selesai)</li>
                    <li><i class="fas fa-check-circle"></i> Riwayat pengajuan surat dengan arsip PDF</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Ajukan Surat Sekarang <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 3: Iuran Warga -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="detail-text">
                <h3>Iuran Warga</h3>
                <p>Bayar iuran rutin (keamanan, kebersihan, dll) dengan mudah melalui fitur ini. Warga dapat memilih nominal, mengupload bukti transfer, dan melihat riwayat pembayaran. Pengurus RT dapat memantau pemasukan iuran secara transparan dan membuat laporan keuangan.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Pembayaran via transfer bank atau upload bukti tunai</li>
                    <li><i class="fas fa-check-circle"></i> Riwayat pembayaran lengkap dengan timestamp</li>
                    <li><i class="fas fa-check-circle"></i> Grafik pemasukan iuran per bulan untuk pengurus</li>
                    <li><i class="fas fa-check-circle"></i> Notifikasi jatuh tempo iuran</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Bayar Iuran <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 4: Pengumuman -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="detail-text">
                <h3>Pengumuman</h3>
                <p>Dapatkan informasi terbaru dari pengurus RT mengenai kegiatan, rapat warga, jadwal gotong royong, atau pemberitahuan penting lainnya. Pengumuman dapat diberi label prioritas (penting, mendesak) agar lebih mudah dipantau.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Pengumuman dengan judul, isi, dan lampiran file</li>
                    <li><i class="fas fa-check-circle"></i> Kategori pengumuman (umum, keuangan, acara, dll)</li>
                    <li><i class="fas fa-check-circle"></i> Label "Penting" untuk pengumuman prioritas</li>
                    <li><i class="fas fa-check-circle"></i> Arsip pengumuman lama tetap tersedia</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Lihat Pengumuman <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 5: Data Kartu Keluarga -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-address-card"></i>
            </div>
            <div class="detail-text">
                <h3>Data Kartu Keluarga</h3>
                <p>Setiap warga dapat melihat data Kartu Keluarga (KK) dan anggota keluarga yang terdaftar. Data ini dikelola oleh pengurus RT sehingga akurat dan terpusat. Fitur ini memudahkan pengurusan administrasi kependudukan.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Tampilan KK dengan detail kepala keluarga dan anggota</li>
                    <li><i class="fas fa-check-circle"></i> Pencarian warga berdasarkan NIK atau nama</li>
                    <li><i class="fas fa-check-circle"></i> Riwayat perubahan data (mutasi penduduk)</li>
                    <li><i class="fas fa-check-circle"></i> Hanya dapat diakses oleh warga yang bersangkutan dan pengurus RT</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Lihat Data KK <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 6: Galeri Warga -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-images"></i>
            </div>
            <div class="detail-text">
                <h3>Galeri Warga</h3>
                <p>Kumpulkan momen kebersamaan warga melalui galeri foto digital. Pengurus RT dapat mengunggah foto kegiatan seperti kerja bakti, perayaan hari besar, rapat RT, dan lainnya. Warga dapat melihat dan mengunduh foto-foto tersebut.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Tampilan galeri dengan grid yang menarik</li>
                    <li><i class="fas fa-check-circle"></i> Fitur like dan komentar (opsional)</li>
                    <li><i class="fas fa-check-circle"></i> Kategori galeri (acara, kebersihan, olahraga, dll)</li>
                    <li><i class="fas fa-check-circle"></i> Upload foto dengan deskripsi</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Lihat Galeri <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 7: Riwayat Aktivitas -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-history"></i>
            </div>
            <div class="detail-text">
                <h3>Riwayat Aktivitas</h3>
                <p>Semua aktivitas warga seperti pengaduan, pengajuan surat, dan pembayaran iuran tercatat dalam satu dashboard riwayat. Fitur ini memudahkan warga melacak status dan dokumen yang pernah diajukan.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Tampilan tab untuk setiap jenis aktivitas</li>
                    <li><i class="fas fa-check-circle"></i> Filter berdasarkan status (selesai, diproses, ditolak)</li>
                    <li><i class="fas fa-check-circle"></i> Detail lengkap termasuk waktu dan bukti</li>
                    <li><i class="fas fa-check-circle"></i> Unduh dokumen surat atau bukti pembayaran</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Lihat Riwayat <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Detail 8: Pusat Bantuan -->
    <section class="detail-section">
        <div class="container detail-container">
            <div class="detail-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="detail-text">
                <h3>Pusat Bantuan</h3>
                <p>Pusat bantuan berisi panduan langkah demi langkah, video tutorial, dan FAQ untuk membantu warga menggunakan seluruh fitur e-RT Digital. Pengurus RT juga dapat mengedit konten bantuan sesuai kebutuhan.</p>
                <ul class="detail-list">
                    <li><i class="fas fa-check-circle"></i> Panduan teks dan video untuk setiap fitur</li>
                    <li><i class="fas fa-check-circle"></i> FAQ interaktif dengan pencarian</li>
                    <li><i class="fas fa-check-circle"></i> Formulir kontak untuk bantuan lebih lanjut</li>
                    <li><i class="fas fa-check-circle"></i> Dapat diperbarui oleh admin secara dinamis</li>
                </ul>
                <a href="auth/login.php" class="btn-detail">Buka Pusat Bantuan <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Memudahkan Administrasi RT Anda?</h2>
            <p>Bergabunglah bersama ribuan warga yang telah merasakan kemudahan layanan digital RT. Daftar sekarang dan nikmati semua fitur unggulan e-RT Digital.</p>
            <a href="auth/register.php" class="btn-hero"><i class="fas fa-user-plus"></i> Daftar Sekarang, Gratis!</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 e-RT Digital. Dikembangkan untuk kemajuan lingkungan RT.</p>
            <div class="footer-links">
                <a href="auth/login.php"><i class="fas fa-question-circle"></i> Bantuan</a>
                <a href="auth/login.php"><i class="fas fa-shield-alt"></i> Privasi</a>
                <a href="auth/login.php"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>
</body>
</html>
``` 