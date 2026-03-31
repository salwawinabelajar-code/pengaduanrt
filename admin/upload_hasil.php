if (isset($_POST['upload_hasil'])) {
    $id = (int)$_POST['id'];
    // Upload file
    $target_dir = "../uploads/surat_hasil/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file = $_FILES['file_hasil'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'surat_' . time() . '_' . uniqid() . '.' . $ext;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $file_path = 'uploads/surat_hasil/' . $filename;
        mysqli_query($conn, "UPDATE pengajuan_surat SET file_hasil='$file_path', status='selesai' WHERE id=$id");
    }
    header("Location: surat.php");
    exit();
}