<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

$create_table = "CREATE TABLE IF NOT EXISTS galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255) NOT NULL,
    tanggal DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

$query = "SELECT * FROM galeri ORDER BY tanggal DESC, id DESC";
$result = mysqli_query($conn, $query);
if (!$result) die("Query error: " . mysqli_error($conn));

$galeri = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Galeri Warga - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, rgba(0, 84, 97, 0.8) 0%, rgba(36, 158, 148, 0.7) 100%);
            backdrop-filter: blur(4px);
            z-index: -1;
        }

        /* Navbar */
        .navbar {
            background: rgba(10, 10, 26, 0.6);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: var(--shadow);
        }

        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(120deg, #fff, var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile > a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 16px 6px 8px;
            border-radius: 50px;
            transition: 0.3s;
        }

        .user-profile > a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
        }

        .user-info h4 {
            font-size: 15px;
            font-weight: 600;
        }

        .user-info small {
            font-size: 12px;
            opacity: 0.8;
        }

        .logout-btn {
            background: rgba(239, 71, 111, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: var(--danger);
            transform: translateY(-2px);
        }

        /* Container - full width */
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 30px 5%;
            flex: 1;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 16px 32px;
            border: 1px solid var(--glass-border);
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-header-left i {
            font-size: 32px;
            color: var(--accent);
            filter: drop-shadow(0 0 6px rgba(59, 193, 168, 0.4));
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 10px 24px;
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
            transform: translateY(-2px);
        }

        /* Gallery Grid - responsive columns */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        /* Card - vertical layout */
        .gallery-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }

        .gallery-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.2);
        }

        .card-image {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 20px;
        }

        .gallery-card:hover .card-overlay {
            opacity: 1;
        }

        .preview-icon {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transform: translateY(20px);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .gallery-card:hover .preview-icon {
            transform: translateY(0);
        }

        .card-content {
            padding: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-content h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }

        .date {
            font-size: 12px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .desc-preview {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0;
            margin-bottom: 15px;
            width: fit-content;
        }

        .read-more-btn:hover {
            text-decoration: underline;
        }

        .download-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            margin-top: auto;
        }

        .download-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            max-width: 800px;
            width: 90%;
            padding: 25px;
            border: 1px solid var(--glass-border);
            position: relative;
            cursor: default;
        }

        .modal-image {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modal-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .modal-download {
            background: var(--accent);
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .modal-download:hover {
            background: var(--secondary);
        }

        .modal-date {
            font-size: 13px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .modal-desc {
            font-size: 15px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }

        .close-modal:hover {
            color: var(--danger);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid var(--glass-border);
            margin-top: auto;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-links {
            display: flex;
            gap: 25px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .gallery-grid {
                grid-template-columns: 1fr;
            }
            .modal-header {
                flex-direction: column;
                align-items: flex-start;
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
                <i class="fas fa-images"></i>
                <h1>Galeri Warga</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if (empty($galeri)): ?>
            <div style="text-align:center; padding:60px 20px; background:var(--glass-bg); backdrop-filter:blur(8px); border-radius:40px;">
                <i class="fas fa-camera" style="font-size:64px; color:var(--accent); margin-bottom:20px; display:block;"></i>
                <p style="font-size:18px;">Belum ada foto di galeri.</p>
                <p style="font-size:14px; margin-top:10px;">Tunggu update dari pengurus RT ya!</p>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($galeri as $item): 
                    $relative_path = str_replace('uploads/', '', $item['foto']);
                    $short_desc = strlen($item['deskripsi']) > 120 ? substr($item['deskripsi'], 0, 120) . '...' : $item['deskripsi'];
                ?>
                <div class="gallery-card" data-id="<?php echo $item['id']; ?>" data-path="<?php echo htmlspecialchars($relative_path); ?>" data-title="<?php echo htmlspecialchars($item['judul']); ?>" data-desc="<?php echo htmlspecialchars($item['deskripsi']); ?>" data-date="<?php echo date('d M Y', strtotime($item['tanggal'])); ?>" data-img="<?php echo htmlspecialchars($item['foto']); ?>">
                    <div class="card-image">
                        <img src="../<?php echo htmlspecialchars($item['foto']); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" loading="lazy">
                        <div class="card-overlay">
                            <div class="preview-icon" onclick="openModal(this)"><i class="fas fa-expand"></i> Lihat Detail</div>
                        </div>
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($item['judul']); ?></h3>
                        <div class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['tanggal'])); ?></div>
                        <p class="desc-preview"><?php echo nl2br(htmlspecialchars($short_desc)); ?></p>
                        <?php if (strlen($item['deskripsi']) > 120): ?>
                            <button class="read-more-btn" onclick="openModalFromButton(this)">Selengkapnya <i class="fas fa-chevron-right"></i></button>
                        <?php endif; ?>
                        <a href="compress_image.php?path=<?php echo urlencode($relative_path); ?>&download=1" class="download-btn" download><i class="fas fa-download"></i> Unduh</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal untuk deskripsi lengkap -->
    <div id="descModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <img id="modalImage" class="modal-image" src="" alt="">
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
                <a href="#" id="modalDownload" class="modal-download" download><i class="fas fa-download"></i> Unduh</a>
            </div>
            <div class="modal-date" id="modalDate"></div>
            <div class="modal-desc" id="modalDesc"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 e-RT Digital. Dikembangkan untuk kemajuan lingkungan RT.</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Bantuan</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Privasi</a>
                <a href="#"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>

    <script>
        function openModal(trigger) {
            const card = trigger.closest('.gallery-card');
            const title = card.dataset.title;
            const date = card.dataset.date;
            const desc = card.dataset.desc;
            const path = card.dataset.path;
            const imgSrc = "../" + card.dataset.img;
            
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + date;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalImage').src = imgSrc;
            document.getElementById('modalDownload').href = `compress_image.php?path=${encodeURIComponent(path)}&download=1`;
            document.getElementById('descModal').classList.add('active');
        }

        function openModalFromButton(btn) {
            const card = btn.closest('.gallery-card');
            const title = card.dataset.title;
            const date = card.dataset.date;
            const desc = card.dataset.desc;
            const path = card.dataset.path;
            const imgSrc = "../" + card.dataset.img;
            
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + date;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalImage').src = imgSrc;
            document.getElementById('modalDownload').href = `compress_image.php?path=${encodeURIComponent(path)}&download=1`;
            document.getElementById('descModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('descModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('descModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>