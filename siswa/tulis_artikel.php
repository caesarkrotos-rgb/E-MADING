<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

if ($_POST) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = 'draft';
    $author_id = $_SESSION['user_id'];
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../uploads/";
        $foto = time() . '_' . basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto);
    }
    
    $insert = mysqli_query($conn, "INSERT INTO artikel (judul, konten, kategori, foto, author_id, status, created_at) VALUES ('$judul', '$konten', '$kategori', '$foto', '$author_id', '$status', NOW())");
    
    if ($insert) {
        header('Location: dashboard.php?success=draft_saved');
        exit;
    } else {
        $error = "Gagal membuat artikel!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Artikel - Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
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
            border-color: #007bff;
        }
        
        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
        
        <div class="header">
            <h2><i class="fas fa-edit"></i> Tulis Artikel</h2>
            <p>Bagikan ide dan pengalaman Anda</p>
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i> 
            <strong>Info:</strong> Artikel yang Anda tulis akan disimpan sebagai draft dan menunggu verifikasi dari guru atau admin sebelum dipublikasi.
        </div>
        
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
                <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                <small style="color: #666;">Format: JPG, PNG, GIF. Maksimal 2MB</small>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Simpan Draft
            </button>
        </form>
        </div>
    </div>
</body>
</html>