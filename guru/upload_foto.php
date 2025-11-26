<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

$success = $error = '';

if ($_POST && isset($_FILES['foto'])) {
    // Validasi dan sanitasi input
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori = $_POST['kategori'] ?? '';
    $user_id = (int)$_SESSION['user_id'];
    $file = $_FILES['foto'];
    
    // Validasi kategori yang diizinkan
    $allowed_categories = ['profil', 'galeri', 'artikel'];
    if (!in_array($kategori, $allowed_categories)) {
        $error = "Kategori tidak valid!";
    } elseif (empty($judul) || strlen($judul) > 255) {
        $error = "Judul harus diisi dan maksimal 255 karakter!";
    } elseif ($file['error'] == 0) {
        // Validasi file yang lebih ketat
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB untuk keamanan
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            $error = "File harus berupa gambar JPG, PNG, atau GIF!";
        } elseif ($file['size'] > $max_size) {
            $error = "Ukuran file maksimal 2MB!";
        } else {
            // Buat direktori upload yang aman
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    $error = "Gagal membuat direktori upload!";
                }
            }
            
            if (empty($error)) {
                // Generate nama file yang aman dengan random bytes
                $nama_file = bin2hex(random_bytes(16)) . '.' . $file_extension;
                $target_path = $target_dir . $nama_file;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Gunakan prepared statement untuk keamanan
                    if ($kategori == 'profil') {
                        $stmt = mysqli_prepare($conn, "UPDATE users SET foto_profil = ? WHERE id = ?");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "si", $nama_file, $user_id);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                    
                    // Simpan ke galeri_foto dengan prepared statement
                    $stmt = mysqli_prepare($conn, "INSERT INTO galeri_foto (judul, deskripsi, nama_file, ukuran_file, tipe_file, uploaded_by, kategori) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "sssisis", $judul, $deskripsi, $nama_file, $file['size'], $mime_type, $user_id, $kategori);
                        if (mysqli_stmt_execute($stmt)) {
                            $success = "Foto berhasil diupload dan disimpan!";
                        } else {
                            $error = "Gagal menyimpan data foto ke database!";
                            unlink($target_path); // Hapus file jika gagal simpan ke DB
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = "Gagal menyiapkan query database!";
                        unlink($target_path);
                    }
                } else {
                    $error = "Gagal mengupload file!";
                }
            }
        }
    } else {
        $upload_errors = [
            1 => 'File terlalu besar',
            2 => 'File terlalu besar',
            3 => 'Upload tidak lengkap',
            4 => 'Tidak ada file yang dipilih',
            6 => 'Folder temporary tidak ditemukan',
            7 => 'Gagal menulis file',
            8 => 'Upload dihentikan'
        ];
        $error = $upload_errors[$file['error']] ?? "Error upload tidak dikenal";
    }
}

// Ambil foto dengan prepared statement
$fotos = false;
$stmt = mysqli_prepare($conn, "SELECT judul, deskripsi, nama_file, kategori, created_at FROM galeri_foto WHERE uploaded_by = ? AND status = 'active' ORDER BY created_at DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $fotos = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto - Guru</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .back-btn { background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; }
        .form-control:focus { outline: none; border-color: #28a745; }
        
        .btn-upload { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .photo-item { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .photo-item img { width: 100%; height: 200px; object-fit: cover; }
        .photo-info { padding: 15px; }
        .photo-info h4 { margin-bottom: 5px; color: #333; }
        .photo-info p { color: #666; font-size: 14px; }
        
        .preview { max-width: 200px; max-height: 200px; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <div class="card">
            <div class="header">
                <h2><i class="fas fa-camera"></i> Upload Foto</h2>
                <p>Upload foto profil atau galeri</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Foto</label>
                    <input type="text" id="judul" name="judul" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" class="form-control" required>
                        <option value="profil">Foto Profil</option>
                        <option value="galeri">Galeri</option>
                        <option value="artikel">Untuk Artikel</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="foto">Pilih Foto</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*" onchange="previewImage(this)" required>
                    <small style="color: #666;">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                    <div id="imagePreview" style="margin-top: 10px; display: none;">
                        <img id="preview" class="preview">
                    </div>
                </div>
                
                <button type="submit" class="btn-upload">
                    <i class="fas fa-upload"></i> Upload Foto
                </button>
            </form>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-images"></i> Foto Saya</h3>
            <div class="photo-grid">
                <?php if ($fotos && mysqli_num_rows($fotos) > 0): ?>
                    <?php while ($foto = mysqli_fetch_assoc($fotos)): ?>
                        <div class="photo-item">
                            <img src="../uploads/<?php echo htmlspecialchars($foto['nama_file'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($foto['judul'] ?? 'Foto', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="photo-info">
                                <h4><?php echo htmlspecialchars($foto['judul'] ?? 'Foto', ENT_QUOTES, 'UTF-8'); ?></h4>
                                <?php if (!empty($foto['deskripsi'])): ?>
                                    <p><?php echo htmlspecialchars($foto['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <small style="color: #999;">
                                    <?php echo htmlspecialchars(ucfirst($foto['kategori'] ?? 'foto'), ENT_QUOTES, 'UTF-8'); ?> • 
                                    <?php echo htmlspecialchars(date('d M Y', strtotime($foto['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">Belum ada foto yang diupload</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewDiv = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>