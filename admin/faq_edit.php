<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    header("Location: faq.php");
    exit();
}

$query = "SELECT * FROM faq WHERE id = $id";
$result = mysqli_query($conn, $query);
$faq = mysqli_fetch_assoc($result);
if (!$faq) {
    header("Location: faq.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $ikon = mysqli_real_escape_string($conn, $_POST['ikon']);
    $urutan = (int)$_POST['urutan'];
    $update = "UPDATE faq SET judul='$judul', isi='$isi', ikon='$ikon', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $update);
    header("Location: faq.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit FAQ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>/* similar to above */</style>
</head>
<body>
    <!-- same sidebar and main content structure -->
    <div class="sidebar">...</div>
    <div class="main-content">
        <div class="content-header"><h1>Edit FAQ</h1><a href="faq.php" class="btn-primary">Kembali</a></div>
        <form method="POST">
            <div class="form-group"><label>Judul</label><input type="text" name="judul" value="<?php echo htmlspecialchars($faq['judul']); ?>"></div>
            <div class="form-group"><label>Ikon</label><input type="text" name="ikon" value="<?php echo htmlspecialchars($faq['ikon']); ?>"></div>
            <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?php echo $faq['urutan']; ?>"></div>
            <div class="form-group"><label>Isi</label><textarea name="isi"><?php echo htmlspecialchars($faq['isi']); ?></textarea></div>
            <button type="submit" class="btn-primary">Simpan</button>
        </form>
    </div>
</body>
</html>