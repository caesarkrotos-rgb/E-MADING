<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['guru', 'siswa'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get article data
$query = "SELECT * FROM artikel WHERE id = $article_id AND author_id = $user_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: ' . ($role == 'guru' ? 'guru/' : 'siswa/') . 'dashboard.php');
    exit;
}

$article = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = $_POST['status'];
    
    // Handle file upload
    $foto = $article['foto']; // Keep existing photo by default
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'uploads/';
        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . basename($_FILES['foto']['name']);
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
            $foto = $new_filename;
        }
    }
    
    $update_query = "UPDATE artikel SET judul = '$judul', konten = '$konten', kategori = '$kategori', status = '$status', foto = '$foto' WHERE id = $article_id AND author_id = $user_id";
    
    if (mysqli_query($conn, $update_query)) {
        $redirect_url = ($role == 'guru' ? 'guru/' : 'siswa/') . 'dashboard.php?success=updated';
        header('Location: ' . $redirect_url);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Artikel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input, textarea, select { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 200px; resize: vertical; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; margin-right: 10px; }
        .current-image { max-width: 200px; margin: 10px 0; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-edit"></i> Edit Artikel</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Judul Artikel *</label>
                    <input type="text" name="judul" value="<?php echo htmlspecialchars($article['judul']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori">
                        <option value="">Pilih Kategori</option>
                        <option value="Pengumuman" <?php echo $article['kategori'] == 'Pengumuman' ? 'selected' : ''; ?>>Pengumuman</option>
                        <option value="Kegiatan" <?php echo $article['kategori'] == 'Kegiatan' ? 'selected' : ''; ?>>Kegiatan</option>
                        <option value="Prestasi" <?php echo $article['kategori'] == 'Prestasi' ? 'selected' : ''; ?>>Prestasi</option>
                        <option value="Pendidikan" <?php echo $article['kategori'] == 'Pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                        <option value="Opini" <?php echo $article['kategori'] == 'Opini' ? 'selected' : ''; ?>>Opini</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Konten Artikel *</label>
                    <textarea name="konten" required><?php echo htmlspecialchars($article['konten']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Foto Artikel</label>
                    <?php if ($article['foto']): ?>
                        <div>
                            <p>Foto saat ini:</p>
                            <img src="uploads/<?php echo htmlspecialchars($article['foto']); ?>" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto" accept="image/*">
                    <small>Kosongkan jika tidak ingin mengubah foto</small>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?php echo $article['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <?php if ($role == 'guru'): ?>
                            <option value="published" <?php echo $article['status'] == 'published' ? 'selected' : ''; ?>>Publish</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div style="text-align: right;">
                    <a href="<?php echo ($role == 'guru' ? 'guru/' : 'siswa/') . 'dashboard.php'; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Artikel
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>