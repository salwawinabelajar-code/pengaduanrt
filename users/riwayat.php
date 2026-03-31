<?php
// riwayat.php - Halaman Riwayat dengan Fitur Edit Modal & Hapus
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$success_message = '';
$error_message = '';

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
} else {
    die("Error preparing user query: " . mysqli_error($conn));
}

// ========== PROSES HAPUS PENGADUAN ==========
if (isset($_GET['hapus_pengaduan'])) {
    $id = (int)$_GET['hapus_pengaduan'];
    // Ambil foto untuk dihapus
    $stmt = mysqli_prepare($conn, "SELECT foto FROM pengaduan WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['foto'])) {
            $file_path = '../' . $row['foto'];
            if (file_exists($file_path)) unlink($file_path);
        }
    }
    mysqli_stmt_close($stmt);
    // Hapus data
    $stmt = mysqli_prepare($conn, "DELETE FROM pengaduan WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Pengaduan berhasil dihapus.";
    } else {
        $error_message = "Gagal menghapus pengaduan.";
    }
    mysqli_stmt_close($stmt);
    header("Location: riwayat.php?tab=pengaduan&success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// ========== PROSES HAPUS SURAT ==========
if (isset($_GET['hapus_surat'])) {
    $id = (int)$_GET['hapus_surat'];
    // Ambil file pendukung dan file hasil untuk dihapus
    $stmt = mysqli_prepare($conn, "SELECT file_pendukung, file_hasil FROM pengajuan_surat WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['file_pendukung'])) {
            $file_path = '../uploads/surat/' . $row['file_pendukung'];
            if (file_exists($file_path)) unlink($file_path);
        }
        if (!empty($row['file_hasil'])) {
            $file_path = '../' . $row['file_hasil'];
            if (file_exists($file_path)) unlink($file_path);
        }
    }
    mysqli_stmt_close($stmt);
    // Hapus data
    $stmt = mysqli_prepare($conn, "DELETE FROM pengajuan_surat WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Pengajuan surat berhasil dihapus.";
    } else {
        $error_message = "Gagal menghapus pengajuan surat.";
    }
    mysqli_stmt_close($stmt);
    header("Location: riwayat.php?tab=surat&success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// Tangkap pesan sukses/error dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Tentukan tab aktif
$active_tab = $_GET['tab'] ?? 'pengaduan';

// ========== LOGIKA PENGADUAN ==========
if ($active_tab == 'pengaduan') {
    // Filter dan pencarian pengaduan
    $filter_status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if (!empty($filter_status)) {
        $where_clause .= " AND status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $where_clause .= " AND (judul LIKE ? OR deskripsi LIKE ? OR lokasi LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }

    $count_query = "SELECT COUNT(*) as total FROM pengaduan $where_clause";
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($count_stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        }
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_data = $count_result ? mysqli_fetch_assoc($count_result) : ['total' => 0];
        $total_data = $count_data ? $count_data['total'] : 0;
        $total_pages = ceil($total_data / $limit);
        mysqli_stmt_close($count_stmt);
    }

    $query = "SELECT * FROM pengaduan $where_clause ORDER BY tanggal DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Statistik lengkap dengan ditolak
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengaduan WHERE user_id = ?";
    
    $stats_stmt = mysqli_prepare($conn, $stats_query);
    if ($stats_stmt) {
        mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
        mysqli_stmt_execute($stats_stmt);
        $stats_result = mysqli_stmt_get_result($stats_stmt);
        $stats = $stats_result ? mysqli_fetch_assoc($stats_result) : [];
        mysqli_stmt_close($stats_stmt);
    } else {
        $stats = [];
    }
}

// ========== LOGIKA SURAT ==========
else if ($active_tab == 'surat') {
    // Filter dan pencarian surat
    $filter_status_surat = $_GET['status_surat'] ?? '';
    $search_surat = $_GET['search_surat'] ?? '';
    $page_surat = isset($_GET['page_surat']) ? max(1, (int)$_GET['page_surat']) : 1;
    $limit_surat = 10;
    $offset_surat = ($page_surat - 1) * $limit_surat;

    $where_clause_surat = "WHERE user_id = ?";
    $params_surat = [$user_id];
    $types_surat = "i";
    
    if (!empty($filter_status_surat)) {
        $where_clause_surat .= " AND status = ?";
        $params_surat[] = $filter_status_surat;
        $types_surat .= "s";
    }
    
    if (!empty($search_surat)) {
        $where_clause_surat .= " AND (jenis_surat LIKE ? OR keperluan LIKE ?)";
        $search_term_surat = "%$search_surat%";
        $params_surat[] = $search_term_surat;
        $params_surat[] = $search_term_surat;
        $types_surat .= "ss";
    }

    $count_query_surat = "SELECT COUNT(*) as total FROM pengajuan_surat $where_clause_surat";
    $count_stmt_surat = mysqli_prepare($conn, $count_query_surat);
    if ($count_stmt_surat) {
        if (!empty($params_surat)) {
            mysqli_stmt_bind_param($count_stmt_surat, $types_surat, ...$params_surat);
        }
        mysqli_stmt_execute($count_stmt_surat);
        $count_result_surat = mysqli_stmt_get_result($count_stmt_surat);
        $count_data_surat = $count_result_surat ? mysqli_fetch_assoc($count_result_surat) : ['total' => 0];
        $total_data_surat = $count_data_surat ? $count_data_surat['total'] : 0;
        $total_pages_surat = ceil($total_data_surat / $limit_surat);
        mysqli_stmt_close($count_stmt_surat);
    }

    $query_surat = "SELECT * FROM pengajuan_surat $where_clause_surat ORDER BY tanggal_pengajuan DESC LIMIT ? OFFSET ?";
    $params_surat[] = $limit_surat;
    $params_surat[] = $offset_surat;
    $types_surat .= "ii";
    
    $stmt_surat = mysqli_prepare($conn, $query_surat);
    if ($stmt_surat) {
        mysqli_stmt_bind_param($stmt_surat, $types_surat, ...$params_surat);
        mysqli_stmt_execute($stmt_surat);
        $result_surat = mysqli_stmt_get_result($stmt_surat);
    }

    // Statistik lengkap dengan ditolak
    $stats_query_surat = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengajuan_surat WHERE user_id = ?";
    
    $stats_stmt_surat = mysqli_prepare($conn, $stats_query_surat);
    if ($stats_stmt_surat) {
        mysqli_stmt_bind_param($stats_stmt_surat, "i", $user_id);
        mysqli_stmt_execute($stats_stmt_surat);
        $stats_result_surat = mysqli_stmt_get_result($stats_stmt_surat);
        $stats_surat = $stats_result_surat ? mysqli_fetch_assoc($stats_result_surat) : [];
        mysqli_stmt_close($stats_stmt_surat);
    } else {
        $stats_surat = [];
    }
}

function safe_output($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - e-RT Digital</title>
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
            --info: #118AB2;
        }

        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
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

        .tab-navigation {
            display: flex;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            padding: 5px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .tab-btn {
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            background: none;
            border: none;
            font-size: 18px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .tab-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .tab-btn.active {
            background: var(--secondary);
            color: white;
            box-shadow: 0 5px 15px rgba(36,158,148,0.3);
        }

        .tab-btn .badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 14px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        @media (max-width: 1000px) {
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 600px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            transition: 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
            border-color: var(--accent);
        }

        .stat-icon {
            font-size: 42px;
            margin-bottom: 20px;
            display: inline-block;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: var(--accent);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .stat-card h3 {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 38px;
            font-weight: 800;
            color: white;
        }

        .filter-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .filter-title {
            font-size: 22px;
            color: white;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
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
            box-shadow: 0 0 0 3px rgba(59,193,168,0.2);
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }

        select.form-control option {
            background: var(--primary);
            color: white;
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
            gap: 10px;
        }

        .btn-search {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            box-shadow: 0 5px 15px rgba(36,158,148,0.3);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(36,158,148,0.5);
        }

        .btn-reset {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-reset:hover {
            background: rgba(255,255,255,0.2);
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

        .table-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 40px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        thead {
            background: rgba(0,0,0,0.2);
            color: white;
        }

        th {
            padding: 20px 25px;
            text-align: left;
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: rgba(255,255,255,0.1);
        }

        td {
            padding: 20px 25px;
            vertical-align: middle;
            font-size: 16px;
            color: rgba(255,255,255,0.9);
        }

        td small {
            color: rgba(255,255,255,0.6);
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-badge.status-baru, .status-badge.status-menunggu {
            background: rgba(255,209,102,0.2);
            color: #FFD166;
        }
        .status-badge.status-diproses {
            background: rgba(17,138,178,0.2);
            color: #118AB2;
        }
        .status-badge.status-selesai {
            background: rgba(6,214,160,0.2);
            color: #06D6A0;
        }
        .status-badge.status-ditolak {
            background: rgba(239,71,111,0.2);
            color: #EF476F;
        }

        .urgensi-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            background: rgba(0,0,0,0.2);
        }
        .urgensi-rendah { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .urgensi-sedang { background: rgba(255,209,102,0.2); color: #FFD166; }
        .urgensi-tinggi { background: rgba(239,71,111,0.2); color: #EF476F; }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 16px;
            border-radius: 30px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-view { background: rgba(17,138,178,0.2); color: #118AB2; }
        .btn-view:hover { background: rgba(17,138,178,0.4); }
        .btn-edit { background: rgba(255,209,102,0.2); color: #FFD166; }
        .btn-edit:hover { background: rgba(255,209,102,0.4); }
        .btn-delete { background: rgba(239,71,111,0.2); color: #EF476F; }
        .btn-delete:hover { background: rgba(239,71,111,0.4); }
        .btn-download { background: rgba(6,214,160,0.2); color: #06D6A0; }
        .btn-download:hover { background: rgba(6,214,160,0.4); }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 40px 0;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
            font-size: 16px;
        }

        .page-link:hover {
            background: var(--secondary);
            border-color: var(--secondary);
        }

        .page-link.active {
            background: var(--secondary);
            border-color: var(--secondary);
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon {
            font-size: 72px;
            color: rgba(255,255,255,0.3);
            margin-bottom: 25px;
        }

        .empty-state h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 700;
        }

        .empty-state p {
            color: rgba(255,255,255,0.8);
            margin-bottom: 25px;
            font-size: 18px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: rgba(0,0,0,0.2);
            color: white;
            padding: 30px;
            border-radius: 30px 30px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-header h3 {
            font-size: 24px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.3s;
        }

        .close-modal:hover {
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 30px;
            color: white;
        }

        .detail-group {
            margin-bottom: 20px;
        }

        .detail-group label {
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            display: block;
            font-size: 16px;
        }

        .detail-group p {
            color: white;
            line-height: 1.6;
            padding: 12px;
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            font-size: 16px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .photo-preview {
            margin-top: 15px;
        }

        .photo-preview img {
            max-width: 100%;
            border-radius: 15px;
            border: 2px solid var(--accent);
        }

        /* CSS tambahan untuk modal edit */
        .modal-body .form-group {
            margin-bottom: 20px;
        }
        .modal-body label {
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            display: block;
        }
        .modal-body .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .modal-body .form-control:focus {
            border-color: var(--accent);
            outline: none;
        }
        .modal-body .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-body p {
            margin-top: 10px;
            color: rgba(255,255,255,0.8);
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

        .footer-content {
            max-width: 1400px;
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

        @media (max-width: 900px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .tab-navigation {
                flex-direction: column;
            }
        }
        @media (max-width: 600px) {
            .action-buttons {
                flex-direction: column;
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
            <a href="riwayat.php" class="active">Riwayat</a>
            <a href="iuran.php">Iuran</a>
            <a href="surat.php">Surat</a>
            <a href="kk.php">Data KK</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo safe_output($user['nama'] ?? 'User'); ?></h4>
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
                <i class="fas fa-history"></i>
                <h1>Riwayat</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>

        <!-- Notifikasi -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo safe_output($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo safe_output($error_message); ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn <?php echo $active_tab == 'pengaduan' ? 'active' : ''; ?>" 
                    onclick="changeTab('pengaduan')">
                <i class="fas fa-comment-medical"></i> Pengaduan
                <?php if ($active_tab == 'pengaduan' && isset($stats['total'])): ?>
                    <span class="badge"><?php echo safe_output($stats['total']); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn <?php echo $active_tab == 'surat' ? 'active' : ''; ?>" 
                    onclick="changeTab('surat')">
                <i class="fas fa-envelope-open-text"></i> Surat
                <?php if ($active_tab == 'surat' && isset($stats_surat['total'])): ?>
                    <span class="badge"><?php echo safe_output($stats_surat['total']); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ========== TAB PENGADUAN ========== -->
        <?php if ($active_tab == 'pengaduan'): ?>
            <!-- Statistik Pengaduan (5 kartu) -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    <h3>Total Pengaduan</h3>
                    <div class="number"><?php echo safe_output($stats['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Pengaduan Baru</h3>
                    <div class="number"><?php echo safe_output($stats['baru'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Sedang Diproses</h3>
                    <div class="number"><?php echo safe_output($stats['diproses'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Selesai</h3>
                    <div class="number"><?php echo safe_output($stats['selesai'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <h3>Ditolak</h3>
                    <div class="number"><?php echo safe_output($stats['ditolak'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title"><i class="fas fa-filter"></i> Filter Pengaduan</div>
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="tab" value="pengaduan">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Cari Pengaduan</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan judul, deskripsi, atau lokasi..." value="<?php echo safe_output($search); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Filter Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="baru" <?php echo ($filter_status ?? '') == 'baru' ? 'selected' : ''; ?>>Baru</option>
                            <option value="diproses" <?php echo ($filter_status ?? '') == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="selesai" <?php echo ($filter_status ?? '') == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="ditolak" <?php echo ($filter_status ?? '') == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Terapkan</button>
                    <a href="riwayat.php?tab=pengaduan" class="btn btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </form>
            </div>

            <!-- Tabel Pengaduan -->
            <div class="table-container">
                <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Judul Pengaduan</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Urgensi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo safe_output($row['judul']); ?></strong>
                                        <br><small>Lokasi: <?php echo safe_output($row['lokasi'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo safe_output(ucfirst($row['kategori'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><span class="urgensi-badge urgensi-<?php echo $row['urgensi']; ?>"><?php echo ucfirst($row['urgensi']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewPengaduan(<?php echo $row['id']; ?>)"><i class="fas fa-eye"></i> Detail</button>
                                            <?php if ($row['status'] == 'baru'): ?>
                                                <button class="btn-action btn-edit" onclick="editPengaduan(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                                            <?php endif; ?>
                                            <a href="?hapus_pengaduan=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus pengaduan ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="?tab=pengaduan&page=<?php echo $page-1; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                        <?php for ($i=1; $i<=$total_pages; $i++): if ($i==1 || $i==$total_pages || ($i>=$page-2 && $i<=$page+2)): ?><a href="?tab=pengaduan&page=<?php echo $i; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php elseif($i==$page-3 || $i==$page+3): ?><span class="page-link disabled">...</span><?php endif; endfor; ?>
                        <?php if ($page < $total_pages): ?><a href="?tab=pengaduan&page=<?php echo $page+1; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <h3>Belum Ada Pengaduan</h3>
                        <p>Anda belum mengirimkan pengaduan apapun.</p>
                        <a href="pengaduan.php" class="btn btn-search" style="display:inline-flex;"><i class="fas fa-plus"></i> Buat Pengaduan Pertama</a>
                    </div>
                <?php endif; ?>
            </div>

        <!-- ========== TAB SURAT ========== -->
        <?php elseif ($active_tab == 'surat'): ?>
            <!-- Statistik Surat (5 kartu) -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Total Surat</h3>
                    <div class="number"><?php echo safe_output($stats_surat['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Menunggu</h3>
                    <div class="number"><?php echo safe_output($stats_surat['menunggu'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Diproses</h3>
                    <div class="number"><?php echo safe_output($stats_surat['diproses'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Selesai</h3>
                    <div class="number"><?php echo safe_output($stats_surat['selesai'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <h3>Ditolak</h3>
                    <div class="number"><?php echo safe_output($stats_surat['ditolak'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title"><i class="fas fa-filter"></i> Filter Surat</div>
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="tab" value="surat">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Cari Surat</label>
                        <input type="text" name="search_surat" class="form-control" placeholder="Cari berdasarkan jenis surat atau keperluan..." value="<?php echo safe_output($search_surat ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Filter Status</label>
                        <select name="status_surat" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo ($filter_status_surat ?? '') == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="diproses" <?php echo ($filter_status_surat ?? '') == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="selesai" <?php echo ($filter_status_surat ?? '') == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="ditolak" <?php echo ($filter_status_surat ?? '') == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Terapkan</button>
                    <a href="riwayat.php?tab=surat" class="btn btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </form>
            </div>

            <!-- Tabel Surat -->
            <div class="table-container">
                <?php if (isset($result_surat) && mysqli_num_rows($result_surat) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Jenis Surat</th>
                                    <th>Keperluan</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_surat = $offset_surat + 1; while ($row = mysqli_fetch_assoc($result_surat)): ?>
                                <tr>
                                    <td><?php echo $no_surat++; ?></td>
                                    <td>
                                        <strong><?php echo safe_output(strtoupper($row['jenis_surat'])); ?></strong>
                                        <br><small>No. Surat: <?php echo safe_output($row['nomor_surat'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo safe_output($row['keperluan']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo safe_output($row['keterangan'] ?? '-'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewSurat(<?php echo $row['id']; ?>)"><i class="fas fa-eye"></i> Detail</button>
                                            <?php if ($row['status'] == 'menunggu'): ?>
                                                <button class="btn-action btn-edit" onclick="editSurat(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                                            <?php endif; ?>
                                            <a href="?hapus_surat=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus pengajuan surat ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                            <?php if ($row['status'] == 'selesai' && !empty($row['file_hasil'])): ?>
                                                <a href="../<?php echo safe_output($row['file_hasil']); ?>" class="btn-action btn-download" download><i class="fas fa-download"></i> Unduh Hasil</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages_surat > 1): ?>
                    <div class="pagination">
                        <?php if ($page_surat > 1): ?><a href="?tab=surat&page_surat=<?php echo $page_surat-1; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                        <?php for ($i=1; $i<=$total_pages_surat; $i++): if ($i==1 || $i==$total_pages_surat || ($i>=$page_surat-2 && $i<=$page_surat+2)): ?><a href="?tab=surat&page_surat=<?php echo $i; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link <?php echo $i==$page_surat?'active':''; ?>"><?php echo $i; ?></a><?php elseif($i==$page_surat-3 || $i==$page_surat+3): ?><span class="page-link disabled">...</span><?php endif; endfor; ?>
                        <?php if ($page_surat < $total_pages_surat): ?><a href="?tab=surat&page_surat=<?php echo $page_surat+1; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-envelope"></i></div>
                        <h3>Belum Ada Pengajuan Surat</h3>
                        <p>Anda belum mengajukan surat apapun.</p>
                        <a href="surat.php" class="btn btn-search" style="display:inline-flex;"><i class="fas fa-plus"></i> Ajukan Surat Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detail Modal Pengaduan -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Detail Pengaduan</h3><button class="close-modal" onclick="closeModal()">&times;</button></div>
            <div class="modal-body" id="modalContent"></div>
        </div>
    </div>

    <!-- Detail Modal Surat -->
    <div id="detailModalSurat" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Detail Surat</h3><button class="close-modal" onclick="closeModalSurat()">&times;</button></div>
            <div class="modal-body" id="modalContentSurat"></div>
        </div>
    </div>

    <!-- Modal Edit Pengaduan -->
    <div id="editPengaduanModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Edit Pengaduan</h3>
                <button class="close-modal" onclick="closeEditPengaduanModal()">&times;</button>
            </div>
            <div class="modal-body" id="editPengaduanContent">
                <!-- Form akan diisi via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Edit Surat -->
    <div id="editSuratModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Edit Surat</h3>
                <button class="close-modal" onclick="closeEditSuratModal()">&times;</button>
            </div>
            <div class="modal-body" id="editSuratContent">
                <!-- Form akan diisi via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 e-RT Digital</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Bantuan</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Privasi</a>
                <a href="#"><i class="fas fa-file-alt"></i> Syarat</a>
            </div>
        </div>
    </footer>

    <script>
        function changeTab(tab) {
            window.location.href = `?tab=${tab}`;
        }

        function viewPengaduan(id) {
            fetch(`get_pengaduan.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let fotoHTML = data.data.foto ? 
                            `<div class="detail-group"><label><i class="fas fa-camera"></i> Foto Bukti:</label>
                                <div class="photo-preview"><img src="../${data.data.foto}" alt="Foto"></div></div>` : '';
                        document.getElementById('modalContent').innerHTML = `
                            <div class="pengaduan-detail">
                                <div class="detail-group"><label><i class="fas fa-heading"></i> Judul:</label><p>${data.data.judul}</p></div>
                                <div class="detail-group"><label><i class="fas fa-tag"></i> Kategori:</label><p>${data.data.kategori}</p></div>
                                <div class="detail-group"><label><i class="fas fa-map-marker-alt"></i> Lokasi:</label><p>${data.data.lokasi||'-'}</p></div>
                                <div class="detail-group"><label><i class="fas fa-align-left"></i> Deskripsi:</label><p>${data.data.deskripsi}</p></div>
                                ${fotoHTML}
                                <div class="detail-group"><label><i class="fas fa-tachometer-alt"></i> Urgensi:</label><p><span class="urgensi-badge urgensi-${data.data.urgensi}">${data.data.urgensi}</span></p></div>
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Status:</label><p><span class="status-badge status-${data.data.status}">${data.data.status}</span></p></div>
                                <div class="detail-group"><label><i class="far fa-clock"></i> Tanggal:</label><p>${new Date(data.data.tanggal).toLocaleString('id-ID')}</p></div>
                            </div>`;
                        document.getElementById('detailModal').style.display = 'flex';
                    } else alert('Gagal memuat detail');
                }).catch(e => alert('Error: ' + e));
        }

        function viewSurat(id) {
            fetch(`get_surat.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let filePendukungHTML = data.data.file_pendukung ? 
                            `<div class="detail-group"><label><i class="fas fa-paperclip"></i> File Pendukung:</label>
                                <p><a href="../uploads/surat/${data.data.file_pendukung}" class="btn-action btn-download" download style="display:inline-flex;"><i class="fas fa-download"></i> Unduh</a></p></div>` : '';
                        let fileHasilHTML = data.data.file_hasil ? 
                            `<div class="detail-group"><label><i class="fas fa-file-pdf"></i> File Hasil (Surat Jadi):</label>
                                <p><a href="../${data.data.file_hasil}" class="btn-action btn-download" download style="display:inline-flex;"><i class="fas fa-download"></i> Unduh Surat</a></p></div>` : '';
                        document.getElementById('modalContentSurat').innerHTML = `
                            <div class="pengaduan-detail">
                                <div class="detail-group"><label><i class="fas fa-envelope"></i> Jenis Surat:</label><p>${data.data.jenis_surat}</p></div>
                                <div class="detail-group"><label><i class="fas fa-hashtag"></i> No. Surat:</label><p>${data.data.nomor_surat || '-'}</p></div>
                                <div class="detail-group"><label><i class="fas fa-list-alt"></i> Keperluan:</label><p>${data.data.keperluan}</p></div>
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Keterangan:</label><p>${data.data.keterangan || '-'}</p></div>
                                ${filePendukungHTML}
                                ${fileHasilHTML}
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Status:</label><p><span class="status-badge status-${data.data.status}">${data.data.status}</span></p></div>
                                <div class="detail-group"><label><i class="far fa-clock"></i> Tanggal Pengajuan:</label><p>${new Date(data.data.tanggal_pengajuan).toLocaleString('id-ID')}</p></div>
                            </div>`;
                        document.getElementById('detailModalSurat').style.display = 'flex';
                    } else alert('Gagal memuat detail');
                }).catch(e => alert('Error: ' + e));
        }

        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
        function closeModalSurat() { document.getElementById('detailModalSurat').style.display = 'none'; }

        // ========== FUNGSI EDIT PENGADUAN ==========
        function editPengaduan(id) {
            fetch(`get_pengaduan.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let formHTML = `
                            <form id="formEditPengaduan" onsubmit="submitEditPengaduan(event, ${id})">
                                <div class="form-group">
                                    <label>Judul <span class="required">*</span></label>
                                    <input type="text" name="judul" class="form-control" value="${escapeHtml(data.data.judul)}" required>
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi <span class="required">*</span></label>
                                    <textarea name="deskripsi" class="form-control" required>${escapeHtml(data.data.deskripsi)}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Lokasi</label>
                                    <input type="text" name="lokasi" class="form-control" value="${escapeHtml(data.data.lokasi || '')}">
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="kategori" class="form-control">
                                        <option value="infrastruktur" ${data.data.kategori == 'infrastruktur' ? 'selected' : ''}>Infrastruktur</option>
                                        <option value="kebersihan" ${data.data.kategori == 'kebersihan' ? 'selected' : ''}>Kebersihan</option>
                                        <option value="keamanan" ${data.data.kategori == 'keamanan' ? 'selected' : ''}>Keamanan</option>
                                        <option value="sosial" ${data.data.kategori == 'sosial' ? 'selected' : ''}>Sosial</option>
                                        <option value="lainnya" ${data.data.kategori == 'lainnya' ? 'selected' : ''}>Lainnya</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Urgensi</label>
                                    <select name="urgensi" class="form-control">
                                        <option value="rendah" ${data.data.urgensi == 'rendah' ? 'selected' : ''}>Rendah</option>
                                        <option value="sedang" ${data.data.urgensi == 'sedang' ? 'selected' : ''}>Sedang</option>
                                        <option value="tinggi" ${data.data.urgensi == 'tinggi' ? 'selected' : ''}>Tinggi</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Foto Baru (kosongkan jika tidak ingin mengganti)</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                    ${data.data.foto ? `<p>Foto saat ini: <a href="../${data.data.foto}" target="_blank">Lihat</a></p>` : ''}
                                </div>
                                <div class="btn-group" style="display:flex; gap:10px; margin-top:20px;">
                                    <button type="submit" class="btn btn-search">Simpan</button>
                                    <button type="button" class="btn btn-reset" onclick="closeEditPengaduanModal()">Batal</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('editPengaduanContent').innerHTML = formHTML;
                        document.getElementById('editPengaduanModal').style.display = 'flex';
                    } else {
                        alert('Gagal memuat data');
                    }
                })
                .catch(e => alert('Error: ' + e));
        }

        function submitEditPengaduan(event, id) {
            event.preventDefault();
            const form = document.getElementById('formEditPengaduan');
            const formData = new FormData(form);
            formData.append('id', id);
            
            fetch('update_pengaduan.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Data berhasil diperbarui');
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e));
        }

        function closeEditPengaduanModal() {
            document.getElementById('editPengaduanModal').style.display = 'none';
        }

        // ========== FUNGSI EDIT SURAT ==========
        function editSurat(id) {
            fetch(`get_surat.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let formHTML = `
                            <form id="formEditSurat" onsubmit="submitEditSurat(event, ${id})">
                                <div class="form-group">
                                    <label>Jenis Surat <span class="required">*</span></label>
                                    <select name="jenis_surat" class="form-control" required>
                                        <option value="surat pengantar" ${data.data.jenis_surat == 'surat pengantar' ? 'selected' : ''}>Surat Pengantar</option>
                                        <option value="surat keterangan tidak mampu" ${data.data.jenis_surat == 'surat keterangan tidak mampu' ? 'selected' : ''}>Surat Keterangan Tidak Mampu</option>
                                        <option value="surat keterangan" ${data.data.jenis_surat == 'surat keterangan' ? 'selected' : ''}>Surat Keterangan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Keperluan <span class="required">*</span></label>
                                    <textarea name="keperluan" class="form-control" required>${escapeHtml(data.data.keperluan)}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan Tambahan</label>
                                    <textarea name="keterangan" class="form-control">${escapeHtml(data.data.keterangan || '')}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>File Pendukung Baru (kosongkan jika tidak ingin mengganti)</label>
                                    <input type="file" name="file_pendukung" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    ${data.data.file_pendukung ? `<p>File saat ini: <a href="../uploads/surat/${data.data.file_pendukung}" target="_blank">Lihat</a></p>` : ''}
                                </div>
                                <div class="btn-group" style="display:flex; gap:10px; margin-top:20px;">
                                    <button type="submit" class="btn btn-search">Simpan</button>
                                    <button type="button" class="btn btn-reset" onclick="closeEditSuratModal()">Batal</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('editSuratContent').innerHTML = formHTML;
                        document.getElementById('editSuratModal').style.display = 'flex';
                    } else {
                        alert('Gagal memuat data');
                    }
                })
                .catch(e => alert('Error: ' + e));
        }

        function submitEditSurat(event, id) {
            event.preventDefault();
            const form = document.getElementById('formEditSurat');
            const formData = new FormData(form);
            formData.append('id', id);
            
            fetch('update_surat.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Data berhasil diperbarui');
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e));
        }

        function closeEditSuratModal() {
            document.getElementById('editSuratModal').style.display = 'none';
        }

        // Helper untuk escape HTML
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            if (event.target == document.getElementById('detailModal')) closeModal();
            if (event.target == document.getElementById('detailModalSurat')) closeModalSurat();
            if (event.target == document.getElementById('editPengaduanModal')) closeEditPengaduanModal();
            if (event.target == document.getElementById('editSuratModal')) closeEditSuratModal();
        }
    </script>
</body>
</html>
<?php
// Clean up
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($stmt_surat)) mysqli_stmt_close($stmt_surat);
if (isset($result) && is_object($result)) mysqli_free_result($result);
if (isset($result_surat) && is_object($result_surat)) mysqli_free_result($result_surat);
mysqli_close($conn);
?>