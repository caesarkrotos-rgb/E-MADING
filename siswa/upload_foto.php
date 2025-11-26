<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

$success = $error = '';

if ($_POST) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kategori = $_POST['kategori'];
    $user_id = $_SESSION['user_id'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 3 * 1024 * 1024; // 3MB untuk siswa
        
        if (in_array($_FILES['foto']['type'], $allowed_types) && $_FILES['foto']['size'] <= $max_size) {
            // Gunakan direktori uploads utama
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nama_file = time() . '_' . uniqid() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $nama_file)) {
                // Update foto profil jika kategori profil
                if ($kategori == 'profil') {
                    $update_profil = mysqli_query($conn, "UPDATE users SET foto_profil = '$nama_file' WHERE id = $user_id");
                }
                
                // Simpan ke galeri_foto
                $insert = mysqli_query($conn, "INSERT INTO galeri_foto (judul, deskripsi, nama_file, ukuran_file, tipe_file, uploaded_by, kategori) VALUES ('$judul', '$deskripsi', '$nama_file', {$_FILES['foto']['size']}, '{$_FILES['foto']['type']}', $user_id, '$kategori')");
                
                if ($insert) {
                    $success = "Foto berhasil diupload!";
                } else {
                    $error = "Gagal menyimpan data foto!";
                }
            } else {
                $error = "Gagal mengupload file!";
            }
        } else {
            $error = "File tidak valid! Gunakan JPG, PNG, GIF maksimal 3MB.";
        }
    } else {
        $error = "Pilih file foto terlebih dahulu!";
    }
}

// Ambil foto yang sudah diupload
$fotos = false;
try {
    $fotos = mysqli_query($conn, "SELECT * FROM galeri_foto WHERE uploaded_by = {$_SESSION['user_id']} AND status = 'active' ORDER BY created_at DESC");
} catch (Exception $e) {
    $fotos = mysqli_query($conn, "SELECT judul, foto as nama_file, 'artikel' as kategori, created_at FROM artikel WHERE author_id = {$_SESSION['user_id']} AND foto IS NOT NULL ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto - Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .back-btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; }
        .form-control:focus { outline: none; border-color: #007bff; }
        
        .btn-upload { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .photo-item { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s ease; }
        .photo-item:hover { transform: translateY(-5px); }
        .photo-item img { width: 100%; height: 200px; object-fit: cover; cursor: pointer; }
        .photo-info { padding: 15px; }
        .photo-info h4 { margin-bottom: 5px; color: #333; }
        .photo-info p { color: #666; font-size: 14px; }
        
        .preview { max-width: 200px; max-height: 200px; border-radius: 8px; margin-top: 10px; }
        
        .quota-info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <div class="card">
            <div class="header">
                <h2><i class="fas fa-camera"></i> Upload Foto Siswa</h2>
                <p>Upload foto profil atau galeri pribadi</p>
            </div>
            
            <div class="quota-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Info:</strong> Siswa dapat mengupload foto maksimal 3MB. 
                Foto akan ditampilkan di profil dan galeri pribadi.
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Foto</label>
                    <input type="text" id="judul" name="judul" class="form-control" required placeholder="Contoh: Foto Profil Saya">
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi (Opsional)</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" placeholder="Ceritakan tentang foto ini..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" class="form-control" required>
                        <option value="profil">Foto Profil</option>
                        <option value="galeri">Galeri Pribadi</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="foto">Pilih Foto</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*" onchange="previewImage(this)" required>
                    <small style="color: #666;">Format: JPG, PNG, GIF. Maksimal 3MB</small>
                    <div id="imagePreview" style="margin-top: 10px; display: none;">
                        <img id="preview" class="preview">
                        <br><small style="color: #007bff;">✓ Foto siap diupload</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-upload">
                    <i class="fas fa-upload"></i> Upload Foto
                </button>
            </form>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-images"></i> Galeri Foto Saya</h3>
            <?php if (mysqli_num_rows($fotos) > 0): ?>
                <div class="photo-grid">
                    <?php while ($foto = mysqli_fetch_assoc($fotos)): ?>
                        <div class="photo-item">
                            <img src="../uploads/<?php echo $foto['nama_file']; ?>" 
                                 alt="<?php echo htmlspecialchars($foto['judul'] ?? 'Foto'); ?>"
                                 onclick="viewFullImage('../uploads/<?php echo $foto['nama_file']; ?>', '<?php echo htmlspecialchars($foto['judul'] ?? 'Foto'); ?>')">
                            <div class="photo-info">
                                <h4><?php echo htmlspecialchars($foto['judul'] ?? 'Foto'); ?></h4>
                                <?php if (isset($foto['deskripsi']) && $foto['deskripsi']): ?>
                                    <p><?php echo htmlspecialchars($foto['deskripsi']); ?></p>
                                <?php endif; ?>
                                <small style="color: #999;">
                                    <i class="fas fa-tag"></i> <?php echo ucfirst($foto['kategori'] ?? 'foto'); ?> • 
                                    <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($foto['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-images fa-3x" style="margin-bottom: 20px;"></i>
                    <p>Belum ada foto yang diupload</p>
                    <small>Upload foto pertama Anda menggunakan form di atas</small>
                </div>
            <?php endif; ?>
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
        
        function viewFullImage(src, title) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.9); display: flex; align-items: center;
                justify-content: center; z-index: 9999; cursor: pointer;
            `;
            
            const img = document.createElement('img');
            img.src = src;
            img.style.cssText = `
                max-width: 90%; max-height: 90%; object-fit: contain;
                border-radius: 10px;
            `;
            
            modal.appendChild(img);
            document.body.appendChild(modal);
            
            modal.onclick = () => document.body.removeChild(modal);
        }
    </script>
</body>
</html>