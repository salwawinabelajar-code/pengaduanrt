<?php
// pengumuman.php - Halaman Pengumuman User dengan Modal Detail
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user
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
}

// Cek apakah tabel pengumuman ada
$table_check = "SHOW TABLES LIKE 'pengumuman'";
$table_result = mysqli_query($conn, $table_check);
$table_exists = mysqli_num_rows($table_result) > 0;

$result_important = null;
$result = null;
$total_data = 0;
$total_pages = 0;
$pengumuman_data = [];

if ($table_exists) {
    // Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Hitung total
    $count_query = "SELECT COUNT(*) as total FROM pengumuman";
    $count_result = mysqli_query($conn, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_data = $count_row['total'] ?? 0;
        $total_pages = ceil($total_data / $limit);
    }

    // Ambil data pengumuman
    $query = "SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $pengumuman_data[] = $row;
        }
    }

    // Ambil pengumuman penting
    $query_important = "SELECT * FROM pengumuman WHERE penting = 1 ORDER BY created_at DESC LIMIT 3";
    $result_important = mysqli_query($conn, $query_important);
    $pengumuman_penting = [];
    if ($result_important) {
        while ($row = mysqli_fetch_assoc($result_important)) {
            $pengumuman_penting[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        
        :root {
            --primary: #005461;
            --secondary: #249E94;
            --accent: #3BC1A8;
            --danger: #EF476F;
            --warning: #FFD166;
            --success: #06D6A0;
            --glass-bg: rgba(255,255,255,0.15);
            --glass-border: rgba(255,255,255,0.2);
            --shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #fff;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.85) 0%, rgba(36,158,148,0.75) 100%);
            backdrop-filter: blur(4px);
            z-index: -1;
        }
        
        .navbar {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
        }
        
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            background: var(--accent); width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 22px; box-shadow: var(--shadow);
        }
        .logo-text h1 { font-size: 22px; color: white; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        
        .nav-menu { display: flex; gap: 15px; flex-wrap: wrap; }
        .nav-menu a {
            color: white; text-decoration: none; font-weight: 500; padding: 8px 16px;
            border-radius: 30px; transition: 0.3s; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px); border: 1px solid var(--glass-border);
        }
        .nav-menu a:hover, .nav-menu a.active {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 20px; border: 2px solid white;
        }
        .user-info { color: white; }
        .user-info h4 { font-size: 16px; }
        .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid var(--glass-border);
            color: white; padding: 8px 16px; border-radius: 30px; text-decoration: none;
            font-size: 14px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 5px;
        }
        .logout-btn:hover { background: var(--danger); }
        
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        
        .page-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            padding: 15px 25px; background: var(--glass-bg); backdrop-filter: blur(12px);
            border-radius: 60px; border: 1px solid var(--glass-border);
        }
        .page-header-left { display: flex; align-items: center; gap: 15px; }
        .page-header-left i { font-size: 28px; color: var(--accent); filter: drop-shadow(0 0 6px rgba(59,193,168,0.4)); }
        .page-header-left h1 { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #fff, var(--accent)); background-clip: text; -webkit-background-clip: text; color: transparent; }
        
        .back-btn {
            background: rgba(255,255,255,0.15); border: 1px solid var(--glass-border);
            color: white; padding: 10px 24px; border-radius: 40px; text-decoration: none;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .back-btn:hover { background: var(--secondary); transform: translateY(-2px); }
        
        .section-title {
            display: flex; align-items: center; gap: 15px; margin-bottom: 25px;
            font-size: 28px; color: white; font-weight: 700;
        }
        .section-title i { color: var(--accent); }
        
        .important-cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px; margin-bottom: 50px;
        }
        
        .important-card {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 30px;
            padding: 30px; border: 1px solid var(--glass-border); position: relative;
            overflow: hidden; transition: 0.4s; cursor: pointer;
        }
        .important-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.2); border-color: var(--warning); }
        .important-card::before {
            content: 'PENTING'; position: absolute; top: 10px; right: -30px;
            background: var(--warning); color: var(--dark); padding: 6px 40px;
            font-size: 14px; font-weight: 800; transform: rotate(45deg);
        }
        .important-card h3 { color: white; margin-bottom: 20px; font-size: 22px; font-weight: 700; padding-right: 60px; }
        .important-card h3:hover { color: var(--accent); }
        .important-card p { color: rgba(255,255,255,0.9); margin-bottom: 20px; line-height: 1.6; font-size: 16px; }
        .important-date { color: var(--accent); font-size: 15px; display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        
        .announcement-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 40px;
        }
        @media (max-width: 768px) { .announcement-grid { grid-template-columns: 1fr; } }
        
        .announcement-card {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 28px;
            padding: 25px; border: 1px solid var(--glass-border); transition: 0.3s;
            display: flex; flex-direction: column; height: 100%; cursor: pointer;
        }
        .announcement-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.2); border-color: var(--accent); }
        .announcement-card h3 { font-size: 20px; font-weight: 700; color: white; margin-bottom: 15px; line-height: 1.4; }
        .announcement-card h3:hover { color: var(--accent); }
        .announcement-card .content { color: rgba(255,255,255,0.9); margin-bottom: 20px; line-height: 1.6; flex: 1; }
        .announcement-card .content p {
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
            overflow: hidden; text-overflow: ellipsis;
        }
        .announcement-card .date {
            color: var(--accent); font-size: 14px; display: flex; align-items: center;
            gap: 8px; border-top: 1px solid var(--glass-border); padding-top: 15px; margin-top: auto;
        }
        
        .read-more-btn {
            background: none; border: none; color: var(--accent); font-weight: 600;
            cursor: pointer; padding: 8px 0 0 0; display: inline-flex; align-items: center;
            gap: 5px; font-size: 14px; transition: 0.3s; margin-top: 10px; align-self: flex-start;
        }
        .read-more-btn:hover { color: white; gap: 8px; }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 1000;
            justify-content: center; align-items: center;
        }
        .modal.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        
        .modal-content {
            background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 32px;
            width: 90%; max-width: 800px; max-height: 85vh; overflow-y: auto;
            border: 1px solid var(--glass-border); box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: rgba(0,0,0,0.3); padding: 25px 30px; border-radius: 32px 32px 0 0;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--glass-border);
        }
        .modal-header h3 { font-size: 24px; font-weight: 700; color: white; }
        .close-modal {
            background: none; border: none; color: white; font-size: 28px; cursor: pointer;
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; transition: 0.3s;
        }
        .close-modal:hover { background: rgba(255,255,255,0.2); color: var(--danger); }
        .modal-body { padding: 30px; color: white; }
        .modal-body .detail-date { color: var(--accent); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-size: 16px; }
        .modal-body .detail-content { line-height: 1.8; font-size: 16px; white-space: pre-wrap; }
        
        .pagination {
            display: flex; justify-content: center; align-items: center; gap: 15px; margin: 40px 0;
        }
        .page-link {
            display: flex; align-items: center; justify-content: center; width: 45px; height: 45px;
            border-radius: 30px; background: rgba(255,255,255,0.1); color: white;
            text-decoration: none; font-weight: 600; border: 1px solid var(--glass-border);
            transition: 0.3s;
        }
        .page-link:hover, .page-link.active { background: var(--secondary); border-color: var(--secondary); }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }
        
        .empty-state {
            text-align: center; padding: 80px 20px; background: var(--glass-bg);
            backdrop-filter: blur(12px); border-radius: 30px; border: 1px solid var(--glass-border);
        }
        .empty-icon { font-size: 72px; color: rgba(255,255,255,0.3); margin-bottom: 25px; }
        .empty-state h3 { font-size: 28px; margin-bottom: 15px; }
        .empty-state p { color: rgba(255,255,255,0.8); margin-bottom: 15px; font-size: 18px; }
        
        .info-box {
            background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 28px;
            padding: 30px; border-left: 6px solid var(--accent); margin-top: 40px;
        }
        .info-title { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; font-size: 20px; font-weight: 600; }
        .info-content ul { list-style: none; }
        .info-content li {
            margin-bottom: 15px; padding-left: 30px; position: relative;
            color: rgba(255,255,255,0.9); font-size: 16px;
        }
        .info-content li::before { content: '✓'; position: absolute; left: 0; color: var(--accent); font-weight: bold; font-size: 18px; }
        
        .footer {
            background: rgba(0,0,0,0.4); backdrop-filter: blur(12px); border-radius: 50px 50px 0 0;
            padding: 30px 20px; margin-top: 50px; text-align: center; border-top: 1px solid var(--glass-border);
        }
        .footer-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: white; text-decoration: none; opacity: 0.8; transition: 0.2s; }
        .footer-links a:hover { opacity: 1; color: var(--accent); }
        
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; }
            .page-header { flex-direction: column; text-align: center; gap: 15px; }
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
            <a href="dashboard.php">Beranda</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="pengumuman.php" class="active">Pengumuman</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php">Data KK</a>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo isset($user['nama']) ? strtoupper(substr($user['nama'],0,1)) : 'U'; ?></div>
            <div class="user-info">
                <h4><?php echo isset($user['nama']) ? htmlspecialchars($user['nama']) : 'User'; ?></h4>
                <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
            </div>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-bullhorn"></i>
                <h1>Pengumuman RT</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>

        <?php if (!$table_exists): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-database"></i></div>
                <h3>Tabel Pengumuman Tidak Ditemukan</h3>
                <p>Database tidak berisi tabel 'pengumuman'. Hubungi administrator.</p>
            </div>
        <?php else: ?>

            <!-- Pengumuman Penting -->
            <?php if (!empty($pengumuman_penting)): ?>
            <div class="section-title">
                <i class="fas fa-exclamation-triangle"></i> Pengumuman Penting
            </div>
            <div class="important-cards" id="importantCards">
                <?php foreach ($pengumuman_penting as $important): ?>
                <div class="important-card" data-id="<?php echo $important['id']; ?>">
                    <h3><?php echo htmlspecialchars($important['judul']); ?></h3>
                    <p><?php echo strlen($important['isi']) > 150 ? substr(htmlspecialchars($important['isi']), 0, 150) . '...' : htmlspecialchars($important['isi']); ?></p>
                    <div class="important-date">
                        <i class="far fa-clock"></i> <?php echo date('d M Y H:i', strtotime($important['created_at'])); ?>
                    </div>
                    <button class="read-more-btn" data-id="<?php echo $important['id']; ?>">
                        <span>Baca Selengkapnya</span> <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Semua Pengumuman -->
            <div class="section-title">
                <i class="fas fa-newspaper"></i> Semua Pengumuman
            </div>

            <?php if (!empty($pengumuman_data)): ?>
                <div class="announcement-grid" id="announcementGrid">
                    <?php foreach ($pengumuman_data as $announcement): ?>
                    <div class="announcement-card" data-id="<?php echo $announcement['id']; ?>">
                        <h3><?php echo htmlspecialchars($announcement['judul']); ?></h3>
                        <div class="content">
                            <p><?php echo strlen($announcement['isi']) > 200 ? substr(htmlspecialchars($announcement['isi']), 0, 200) . '...' : htmlspecialchars($announcement['isi']); ?></p>
                        </div>
                        <div class="date">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($announcement['created_at'])); ?>
                        </div>
                        <button class="read-more-btn" data-id="<?php echo $announcement['id']; ?>">
                            <span>Baca Selengkapnya</span> <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page-2 && $i <= $page+2)): ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php elseif ($i == $page-3 || $i == $page+3): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-newspaper"></i></div>
                    <h3>Belum Ada Pengumuman</h3>
                    <p>Belum ada pengumuman yang diterbitkan oleh pengurus RT.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="info-box">
            <div class="info-title"><i class="fas fa-info-circle"></i> Informasi Penting</div>
            <div class="info-content">
                <ul>
                    <li>Pengumuman dengan tanda "PENTING" berisi informasi yang harus dibaca oleh semua warga</li>
                    <li>Pastikan membaca semua pengumuman untuk mendapatkan informasi terkini</li>
                    <li>Pengumuman biasanya diterbitkan setiap Senin sore</li>
                    <li>Informasi perubahan mendadak akan diumumkan melalui grup WhatsApp RT</li>
                    <li>Untuk pengumuman yang memerlukan konfirmasi, silakan hubungi ketua RT</li>
                </ul>
            </div>
        </div>
    </div>

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

    <!-- Modal Detail -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-date" id="modalDate"></div>
                <div class="detail-content" id="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        // Data pengumuman dalam bentuk JSON untuk JavaScript
        var announcements = <?php 
            $allData = array_merge($pengumuman_penting, $pengumuman_data);
            $jsonData = [];
            foreach ($allData as $item) {
                $jsonData[] = [
                    'id' => $item['id'],
                    'judul' => $item['judul'],
                    'isi' => nl2br(htmlspecialchars($item['isi'])),
                    'tanggal' => date('d M Y H:i', strtotime($item['created_at'])),
                    'tanggal_only' => date('d M Y', strtotime($item['created_at']))
                ];
            }
            echo json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>;
        
        // Buat map untuk akses cepat
        var announcementMap = {};
        for (var i = 0; i < announcements.length; i++) {
            announcementMap[announcements[i].id] = announcements[i];
        }
        
        function showDetail(id) {
            var data = announcementMap[id];
            if (data) {
                document.getElementById('modalTitle').innerHTML = data.judul;
                document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + data.tanggal;
                document.getElementById('modalContent').innerHTML = data.isi;
                document.getElementById('detailModal').classList.add('active');
            } else {
                // Fallback: ambil via AJAX jika perlu
                fetch('get_pengumuman.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('modalTitle').innerHTML = data.judul;
                            document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + data.tanggal;
                            document.getElementById('modalContent').innerHTML = data.isi;
                            document.getElementById('detailModal').classList.add('active');
                        } else {
                            alert('Gagal memuat pengumuman');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat memuat pengumuman');
                    });
            }
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        // Event listener untuk semua kartu dan tombol
        document.addEventListener('DOMContentLoaded', function() {
            // Kartu penting
            var importantCards = document.querySelectorAll('.important-card');
            importantCards.forEach(function(card) {
                card.addEventListener('click', function(e) {
                    // Jangan trigger jika klik tombol
                    if (e.target.classList.contains('read-more-btn') || e.target.closest('.read-more-btn')) {
                        return;
                    }
                    var id = this.getAttribute('data-id');
                    if (id) showDetail(id);
                });
            });
            
            // Kartu biasa
            var announcementCards = document.querySelectorAll('.announcement-card');
            announcementCards.forEach(function(card) {
                card.addEventListener('click', function(e) {
                    if (e.target.classList.contains('read-more-btn') || e.target.closest('.read-more-btn')) {
                        return;
                    }
                    var id = this.getAttribute('data-id');
                    if (id) showDetail(id);
                });
            });
            
            // Tombol "Baca Selengkapnya"
            var readMoreBtns = document.querySelectorAll('.read-more-btn');
            readMoreBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var id = this.getAttribute('data-id');
                    if (id) showDetail(id);
                });
            });
        });
        
        window.onclick = function(event) {
            var modal = document.getElementById('detailModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
<?php
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($result)) mysqli_free_result($result);
if (isset($result_important) && $result_important) mysqli_free_result($result_important);
mysqli_close($conn);
?>