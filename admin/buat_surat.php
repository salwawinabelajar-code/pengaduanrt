<?php
session_start();
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../vendor/autoload.php'); // Pastikan path sesuai

use Dompdf\Dompdf;
use Dompdf\Options;

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Ambil id pengajuan dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: surat.php");
    exit();
}

// Ambil data pengajuan beserta data pemohon
$query = "SELECT s.*, u.nama, u.alamat, u.email, u.no_hp 
          FROM pengajuan_surat s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
if (!$data) {
    header("Location: surat.php");
    exit();
}

// Proses generate PDF jika form disubmit
$message = '';
$error = '';

if (isset($_POST['generate'])) {
    $isi_surat = $_POST['isi_surat']; // Ambil isi surat dari textarea

    // Konfigurasi Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($isi_surat);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Simpan file PDF
    $filename = 'surat_' . time() . '_' . $id . '.pdf';
    $folder = '../uploads/surat_hasil/';
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    $filepath = $folder . $filename;
    file_put_contents($filepath, $dompdf->output());

    // Simpan path ke database dan ubah status menjadi selesai
    $db_path = 'uploads/surat_hasil/' . $filename;
    $update = "UPDATE pengajuan_surat SET file_hasil='$db_path', status='selesai' WHERE id=$id";
    if (mysqli_query($conn, $update)) {
        $message = "Surat berhasil dibuat dan status diubah menjadi selesai.";
        // Refresh data
        $data['file_hasil'] = $db_path;
        $data['status'] = 'selesai';
    } else {
        $error = "Gagal update database: " . mysqli_error($conn);
    }
}

// Template surat default (bisa disesuaikan)
$template = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Surat Pengantar</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        h2 { text-align: center; }
        .content { margin-top: 30px; }
        .footer { margin-top: 50px; }
        .signature { margin-top: 70px; }
    </style>
</head>
<body>
    <h2>SURAT PENGANTAR</h2>
    <p>Nomor: ...../...../...../.....</p>
    <p>Yang bertanda tangan di bawah ini, Ketua RT 03, menerangkan bahwa:</p>
    <table style='margin-left: 20px;'>
        <tr><td>Nama</td><td>: {nama}</td></tr>
        <tr><td>Alamat</td><td>: {alamat}</td></tr>
        <tr><td>No. HP</td><td>: {no_hp}</td></tr>
    </table>
    <p>Bahwa yang bersangkutan benar-benar warga RT 03 dan mengajukan surat dengan keperluan:</p>
    <p><strong>{keperluan}</strong></p>
    <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    <div class='footer'>
        <p>Jakarta, {tanggal}</p>
        <p>Ketua RT 03,</p>
        <div class='signature'>(____________________)</div>
    </div>
</body>
</html>";

// Ganti placeholder dengan data dari database
$template = str_replace('{nama}', htmlspecialchars($data['nama']), $template);
$template = str_replace('{alamat}', htmlspecialchars($data['alamat'] ?? '-'), $template);
$template = str_replace('{no_hp}', htmlspecialchars($data['no_hp'] ?? '-'), $template);
$template = str_replace('{keperluan}', htmlspecialchars($data['keperluan']), $template);
$template = str_replace('{tanggal}', date('d F Y'), $template);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Surat - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ====== CSS SAMA DENGAN HALAMAN LAIN (bisa salin dari surat.php) ====== */
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
            background: url('') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            color: #fff;
            display: flex;
            flex-direction: column;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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
            top: 0; z-index: 100;
        }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            background: var(--accent); width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 22px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .logo-text h1 { font-size: 22px; color: white; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-menu { display: flex; gap: 15px; }
        .nav-menu a {
            color: white; text-decoration: none; font-weight: 500; padding: 8px 16px;
            border-radius: 30px; transition: 0.3s; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);
        }
        .nav-menu a:hover, .nav-menu a.active {
            background: var(--secondary); transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-profile a { text-decoration: none; color: white; display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(145deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 20px; border: 2px solid white;
        }
        .user-info { color: white; }
        .user-info h4 { font-size: 16px; }
        .logout-btn {
            background: rgba(239,71,111,0.2); border: 1px solid rgba(255,255,255,0.2);
            color: white; padding: 8px 16px; border-radius: 30px; text-decoration: none;
            font-size: 14px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 5px;
        }
        .logout-btn:hover { background: var(--danger); }
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }
        .page-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            padding: 15px 25px; background: rgba(255,255,255,0.15); backdrop-filter: blur(12px);
            border-radius: 50px; border: 1px solid rgba(255,255,255,0.2);
        }
        .page-header-left { display: flex; align-items: center; gap: 15px; }
        .page-header-left i { font-size: 28px; color: var(--accent); }
        .page-header-left h1 { font-size: 28px; font-weight: 700; color: white; }
        .back-btn {
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
            color: white; padding: 10px 20px; border-radius: 40px; text-decoration: none;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .back-btn:hover { background: var(--secondary); border-color: var(--secondary); }
        .card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: var(--accent);
        }
        .info-value {
            flex: 1;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--accent);
        }
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
            min-height: 400px;
            font-family: monospace;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.4);
        }
        .btn-secondary {
            background: rgba(239,71,111,0.2);
            color: white;
            border: 1px solid rgba(239,71,111,0.3);
        }
        .btn-secondary:hover {
            background: var(--danger);
        }
        .message {
            padding: 12px 20px;
            border-radius: 30px;
            margin-bottom: 20px;
            background: rgba(6,214,160,0.2);
            border: 1px solid rgba(6,214,160,0.3);
            color: white;
        }
        .error {
            background: rgba(239,71,111,0.2);
            border-color: rgba(239,71,111,0.3);
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
        .footer p { margin: 8px 0; }
        .footer .registered { font-weight:500; }
        .footer .edit-link { color: var(--accent); text-decoration:none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h1>e-RT Digital Admin</h1></div>
        </div>
        <div class="nav-menu">
            <a href="index.php">Dashboard</a>
            <a href="pengaduan.php">Pengaduan</a>
            <a href="surat.php" class="active">Surat</a>
            <a href="iuran.php">Iuran</a>
            <a href="pengumuman.php">Pengumuman</a>
            <a href="kk.php">Data KK</a>
            <a href="pengaturan.php">Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <small>Admin</small>
                </div>
            </a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-file-pdf"></i>
                <h1>Buat Surat</h1>
            </div>
            <a href="surat.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:20px;">Data Pengajuan</h3>
            <div class="info-row">
                <div class="info-label">Nama Pemohon</div>
                <div class="info-value"><?php echo htmlspecialchars($data['nama']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Jenis Surat</div>
                <div class="info-value"><?php echo htmlspecialchars($data['jenis_surat']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Keperluan</div>
                <div class="info-value"><?php echo htmlspecialchars($data['keperluan']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat</div>
                <div class="info-value"><?php echo htmlspecialchars($data['alamat'] ?? '-'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($data['email']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">No. HP</div>
                <div class="info-value"><?php echo htmlspecialchars($data['no_hp'] ?? '-'); ?></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:20px;">Buat Surat</h3>
            <p style="margin-bottom:20px; color:rgba(255,255,255,0.8);">Anda dapat mengedit template surat di bawah ini. Setelah selesai, klik "Generate PDF".</p>
            <form method="POST">
                <div class="form-group">
                    <label>Isi Surat (HTML)</label>
                    <textarea name="isi_surat"><?php echo htmlspecialchars($template); ?></textarea>
                </div>
                <div class="btn-group">
                    <button type="submit" name="generate" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Generate PDF</button>
                    <a href="surat.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                </div>
            </form>
        </div>

        <?php if (!empty($data['file_hasil'])): ?>
        <div class="card">
            <h3 style="margin-bottom:20px;">Hasil Surat</h3>
            <p>Surat sudah dibuat: <a href="../<?php echo $data['file_hasil']; ?>" target="_blank" class="btn btn-primary" style="padding:8px 15px;">Lihat PDF</a></p>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p class="registered">Terdaftar<br><?php echo date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
        <p><a href="profil.php?edit=1" class="edit-link">Edit Profil</a></p>
        <p class="copyright">© 2024 e-RT Digital - Panel Admin</p>
    </footer>
</body>
</html>