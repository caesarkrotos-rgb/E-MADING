<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

if ($_POST) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = $_POST['status'];
    $author_id = $_SESSION['user_id'];
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['foto']['type'], $allowed_types) && $_FILES['foto']['size'] <= $max_size) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto = time() . '_' . uniqid() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto)) {
                // Gambar berhasil diupload
            } else {
                $error = "Gagal mengupload gambar!";
                $foto = null;
            }
        } else {
            $error = "File gambar tidak valid! Gunakan JPG, PNG, atau GIF maksimal 2MB.";
        }
    }
    
    $insert = mysqli_query($conn, "INSERT INTO artikel (judul, konten, kategori, foto, author_id, status, created_at) VALUES ('$judul', '$konten', '$kategori', '$foto', '$author_id', '$status', NOW())");
    
    if ($insert) {
        $artikel_id = mysqli_insert_id($conn);
        
        // Create notification for admin when article is published
        if ($status == 'published') {
            $admin_users = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
            while ($admin = mysqli_fetch_assoc($admin_users)) {
                $notif_title = "Artikel Baru Dipublikasi";
                $notif_message = "Penulis mendapat info jika artikel sudah disetuji/tayang: \"$judul\" oleh {$_SESSION['nama']}";
                mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) VALUES ({$admin['id']}, '$notif_title', '$notif_message', 'success')");
            }
            header('Location: dashboard.php?success=published');
            exit;
        } else {
            header('Location: dashboard.php?success=draft');
            exit;
        }
    } else {
        $error = "Gagal membuat artikel! Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Artikel - Guru</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #28a745;
        }
        
        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="../logout.php" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="header">
            <h2><i class="fas fa-plus"></i> Tulis Artikel Baru</h2>
            <p>Bagikan pengetahuan dan informasi kepada siswa</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <br><br>
                <a href="../public_unified.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🌐 Lihat di Portal</a>
                <a href="dashboard.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">📊 Dashboard</a>
                <a href="artikel_saya.php" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📰 Artikel Saya</a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="judul">Judul Artikel</label>
                <input type="text" id="judul" name="judul" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" class="form-control" required>
                    <option value="Prestasi">Prestasi</option>
                    <option value="Opini">Opini</option>
                    <option value="Kegiatan">Kegiatan</option>
                    <option value="Informasi sekolah">Informasi sekolah</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="konten">Konten Artikel</label>
                <textarea id="konten" name="konten" class="form-control" required placeholder="Tulis konten artikel di sini..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto (Opsional)</label>
                <input type="file" id="foto" name="foto" class="form-control" accept="image/*" onchange="previewImage(this)">
                <small style="color: #666;">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #ddd;">
                    <br><small style="color: #28a745;">✓ Gambar siap diupload</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="published" selected>Publikasi</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Simpan Artikel
            </button>
        </form>
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