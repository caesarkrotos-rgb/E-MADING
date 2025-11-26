<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Handle delete
if (isset($_POST['delete_artikel'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM artikel WHERE id = $artikel_id AND author_id = {$_SESSION['user_id']}");
        mysqli_commit($conn);
        header('Location: artikel_saya.php?success=deleted');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

// Function to get image path
function getImagePath($filename) {
    if (!$filename) return null;
    $paths = ['../uploads/' . $filename, '../uploads/gallery/' . $filename, '../uploads/profiles/' . $filename];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

$my_articles = mysqli_query($conn, "SELECT * FROM artikel WHERE author_id = {$_SESSION['user_id']} ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Saya - Guru</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            margin-top: 10px;
            font-size: 1.2rem;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .nav-item {
            margin: 5px 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-right: 3px solid white;
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .article-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
        }
        
        .article-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .article-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .article-content {
            padding: 20px;
        }
        
        .article-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .article-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .add-article-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-chalkboard-teacher" style="font-size: 2rem;"></i>
            <h3>Panel Guru</h3>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="tulis_artikel.php" class="nav-link">
                    <i class="fas fa-plus"></i>
                    Tulis Artikel
                </a>
            </li>
            <li class="nav-item">
                <a href="artikel.php" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    Semua Artikel
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="fas fa-list"></i>
                    Artikel Saya
                </a>
            </li>
            <li class="nav-item">
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    Profil
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="container">
        
        <div class="header">
            <h1><i class="fas fa-newspaper"></i> Artikel Saya</h1>
            <p>Kelola semua artikel yang telah Anda buat</p>
        </div>
        
        <a href="tulis_artikel.php" class="add-article-btn">
            <i class="fas fa-plus"></i> Tulis Artikel Baru
        </a>
        
        <?php if ($my_articles && mysqli_num_rows($my_articles) > 0): ?>
            <div class="articles-grid">
                <?php while($article = mysqli_fetch_assoc($my_articles)): ?>
                <div class="article-card">
                    <!-- Article Image -->
                    <?php 
                    $image_path = getImagePath($article['foto']);
                    if ($image_path): 
                    ?>
                        <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 250px; object-fit: contain; transition: transform 0.3s ease;">
                            <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                <?php if (!empty($article['kategori'])): ?>
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['kategori']); ?>
                                <?php else: ?>
                                    <i class="fas fa-newspaper"></i> Artikel
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Article Content -->
                    <div style="padding: 25px;">
                        <!-- Title -->
                        <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3;">
                            <?php echo htmlspecialchars($article['judul']); ?>
                        </h3>
                        
                        <!-- Meta Info -->
                        <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                            <span style="display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-calendar" style="color: #007bff;"></i> 
                                <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                            </span>
                            <span class="status-badge status-<?php echo $article['status']; ?>" style="padding: 4px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600;">
                                <?php echo $article['status'] == 'published' ? 'Dipublikasi' : ($article['status'] == 'rejected' ? 'Ditolak' : 'Draft'); ?>
                            </span>
                        </div>
                        
                        <!-- Content Preview -->
                        <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                            <?php echo htmlspecialchars(substr($article['konten'], 0, 120)) . '...'; ?>
                        </p>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php if($article['status'] == 'published'): ?>
                                <a href="../artikel_pengunjung.php?id=<?php echo $article['id']; ?>" target="_blank" style="background: #007bff; color: white; padding: 10px 15px; border-radius: 25px; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                            <?php endif; ?>
                            <a href="../edit_artikel.php?id=<?php echo $article['id']; ?>" style="background: #ffc107; color: #212529; padding: 10px 15px; border-radius: 25px; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus artikel ini?')">
                                <input type="hidden" name="artikel_id" value="<?php echo $article['id']; ?>">
                                <button type="submit" name="delete_artikel" style="background: #dc3545; color: white; padding: 10px 15px; border-radius: 25px; border: none; font-size: 0.9rem; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>Belum Ada Artikel</h3>
                <p>Anda belum membuat artikel apapun. <a href="tulis_artikel.php">Tulis artikel pertama Anda!</a></p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</body>
</html>